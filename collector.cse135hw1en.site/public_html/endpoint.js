const express = require('express');
const mysql = require('mysql2/promise');
const crypto = require('crypto');
const fs = require('fs');
const path = require('path');

const app = express();
const PORT = process.env.PORT || 3005;
const LOG_FILE = path.join(__dirname, 'analytics.jsonl');

app.set('trust proxy', true);

// =========================================================================
// 1. DATABASE CONNECTION POOL
// =========================================================================
const pool = mysql.createPool({
  host: process.env.DB_HOST || 'localhost',
  user: process.env.DB_USER || 'analytics_user',
  password: process.env.DB_PASSWORD || 'your_password',
  database: process.env.DB_NAME || 'analytics',
  waitForConnections: true,
  connectionLimit: 10,
  enableKeepAlive: true,
  keepAliveInitialDelay: 0
});

// =========================================================================
// 2. CONSTANTS
// =========================================================================
const ALLOWED_ORIGINS = [
  'https://cse135hw1en.site',
  'https://collector.cse135hw1en.site',
  'https://test.cse135hw1en.site'
];

const KNOWN_EVENT_TYPES = new Set([
  'pageview',
  'initial',
  'activity_batch',
  'error',
  'page_exit',
  'capability',
  'event'
]);

const MAX_PAYLOAD_BYTES = 50 * 1024;
const MAX_STRING_LENGTH = 10000;
const MAX_URL_LENGTH = 2048;
const MAX_PAGE_LENGTH = 255;
const MAX_EVENT_TYPE_LENGTH = 32;
const MAX_SESSION_ID_LENGTH = 64;

// =========================================================================
// 3. CORS
// =========================================================================
app.use((req, res, next) => {
  const origin = req.headers.origin;
  if (origin && ALLOWED_ORIGINS.includes(origin)) {
    res.header('Access-Control-Allow-Origin', origin);
  }

  res.header('Access-Control-Allow-Methods', 'POST, OPTIONS');
  res.header('Access-Control-Allow-Headers', 'Content-Type');
  res.header('Access-Control-Max-Age', '86400');

  if (req.method === 'OPTIONS') {
    return res.sendStatus(204);
  }

  next();
});

// =========================================================================
// 4. BODY PARSING
// =========================================================================
app.use(express.json({ limit: '50kb' }));

// =========================================================================
// 5. HELPERS
// =========================================================================
function sanitizeString(value, maxLen = MAX_STRING_LENGTH) {
  if (value === null || value === undefined) return value;

  return String(value)
    .replace(/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/g, '')
    .slice(0, maxLen);
}

function sanitizeSessionId(value) {
  if (!value) return null;

  return String(value)
    .replace(/[^a-zA-Z0-9_-]/g, '')
    .slice(0, MAX_SESSION_ID_LENGTH);
}

function sanitizeEventType(value) {
  if (!value) return null;

  const clean = String(value)
    .replace(/[^a-zA-Z0-9_-]/g, '')
    .slice(0, MAX_EVENT_TYPE_LENGTH);

  return clean || null;
}

function isValidHttpUrl(url) {
  try {
    const parsed = new URL(url);
    return parsed.protocol === 'http:' || parsed.protocol === 'https:';
  } catch {
    return false;
  }
}

function generateFallbackSessionId() {
  return crypto.randomBytes(16).toString('hex').slice(0, MAX_SESSION_ID_LENGTH);
}

function getDailySalt() {
  return Math.floor(Date.now() / (24 * 60 * 60 * 1000)).toString();
}

function generateUserHash(ip, userAgent) {
  return crypto
    .createHash('sha256')
    .update(`${ip || ''}|${userAgent || ''}|${getDailySalt()}`)
    .digest('hex')
    .slice(0, 16);
}

function deepSanitize(value, depth = 0) {
  if (depth > 10) return null;

  if (value === null || value === undefined) return value;

  if (typeof value === 'string') {
    return sanitizeString(value);
  }

  if (typeof value === 'number') {
    return Number.isFinite(value) ? value : null;
  }

  if (typeof value === 'boolean') {
    return value;
  }

  if (Array.isArray(value)) {
    return value.map(item => deepSanitize(item, depth + 1));
  }

  if (typeof value === 'object') {
    const out = {};
    for (const [key, val] of Object.entries(value)) {
      const cleanKey = sanitizeString(key, 100);
      out[cleanKey] = deepSanitize(val, depth + 1);
    }
    return out;
  }

  return null;
}

function validateAndNormalizePayload(payload, req) {
  if (!payload || typeof payload !== 'object') {
    return { ok: false, status: 400, error: 'Body must be a JSON object.' };
  }

  const raw = JSON.stringify(payload);
  if (Buffer.byteLength(raw, 'utf8') > MAX_PAYLOAD_BYTES) {
    return { ok: false, status: 413, error: 'Payload too large.' };
  }

  if (!payload.url || !isValidHttpUrl(payload.url)) {
    return { ok: false, status: 400, error: 'Missing or invalid url.' };
  }

  const eventType = sanitizeEventType(payload.type);
  if (!eventType) {
    return { ok: false, status: 400, error: 'Missing or invalid type.' };
  }

  // Allow your known event types, but don't hard-break future small custom event types.
  if (!KNOWN_EVENT_TYPES.has(eventType) && eventType.length > MAX_EVENT_TYPE_LENGTH) {
    return { ok: false, status: 400, error: 'Invalid event type.' };
  }

  const normalized = deepSanitize(payload);

  const sessionId =
    sanitizeSessionId(payload.session_id) ||
    sanitizeSessionId(payload.session) ||
    generateFallbackSessionId();

  const serverTimestamp = new Date().toISOString();
  const clientIp = req.ip || req.socket.remoteAddress || '';
  const userAgent =
    normalized?.technographics?.userAgent ||
    normalized?.userAgent ||
    req.headers['user-agent'] ||
    '';

  const userHash = generateUserHash(clientIp, userAgent);

  normalized.session_id = sessionId;
  if (!normalized.session) {
    normalized.session = sessionId;
  }

  normalized.type = eventType;
  normalized.url = sanitizeString(payload.url, MAX_URL_LENGTH);
  normalized.serverTimestamp = serverTimestamp;
  normalized.userHash = userHash;

  return {
    ok: true,
    data: {
      sessionId,
      eventType,
      page: sanitizeString(payload.url, MAX_PAGE_LENGTH),
      payload: normalized,
      serverTimestamp,
      clientIp,
      userHash
    }
  };
}

// =========================================================================
// 6. MAIN COLLECTION ENDPOINT
// =========================================================================
app.post('/collect', async (req, res) => {
  try {
    const result = validateAndNormalizePayload(req.body, req);

    if (!result.ok) {
      return res.status(result.status).json({ error: result.error });
    }

    const { sessionId, eventType, page, payload, serverTimestamp, clientIp, userHash } = result.data;

    // Primary write: keep compatibility with your existing PHP analytics stack.
    await pool.execute(
      `
        INSERT INTO events (session_id, event_type, page, payload)
        VALUES (?, ?, ?, ?)
      `,
      [sessionId, eventType, page, JSON.stringify(payload)]
    );

    // Secondary write: JSONL log for debugging / replay / offline inspection.
    const logLine = JSON.stringify({
      ...payload,
      ip: clientIp
    }) + '\n';

    fs.appendFile(LOG_FILE, logLine, err => {
      if (err) {
        console.warn('[collect] Failed to append analytics.jsonl:', err.message);
      }
    });

    console.log(`[${serverTimestamp}] ${eventType} - ${page} - ${userHash}`);

    return res.sendStatus(204);
  } catch (err) {
    console.error('[collect] Unexpected error:', err);
    return res.sendStatus(500);
  }
});

// =========================================================================
// 7. STATIC FILES
// =========================================================================
app.use(express.static(__dirname));

// =========================================================================
// 8. START SERVER
// =========================================================================
app.listen(PORT, () => {
  console.log(`Analytics endpoint listening on port ${PORT}`);
  console.log(`JSONL log file: ${LOG_FILE}`);
});
