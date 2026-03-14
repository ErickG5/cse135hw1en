<?php
declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  header('Content-Type: text/plain; charset=utf-8');
  echo "OK - log endpoint is running. Use POST with JSON.\n";
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  header('Access-Control-Allow-Origin: https://test.cse135hw1en.site');
  header('Access-Control-Allow-Methods: POST, OPTIONS');
  header('Access-Control-Allow-Headers: Content-Type');
  http_response_code(204);
  exit;
}

// ...your existing POST logic...



// /log endpoint: accept JSON from collector.js and insert into MySQL

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Allow: POST');
  http_response_code(405);
  exit;
}

$raw = file_get_contents('php://input');
if ($raw === false || trim($raw) === '') {
  http_response_code(400);
  echo "Empty body";
  exit;
}

$data = json_decode($raw, true);
if (!is_array($data)) {
  http_response_code(400);
  echo "Invalid JSON";
  exit;
}

// Accept either session_id (preferred) or session (fallback)
$session_id = null;
if (isset($data['session_id']) && is_string($data['session_id'])) {
  $session_id = $data['session_id'];
} elseif (isset($data['session']) && is_string($data['session'])) {
  $session_id = $data['session'];
}

// collector.js uses "type" (pageview, capability, activity, page_exit, error, etc.)
$event_type = (isset($data['type']) && is_string($data['type'])) ? $data['type'] : 'unknown';

// collector.js uses "url"
$page = (isset($data['url']) && is_string($data['url'])) ? $data['url'] : null;

if (!$session_id) {
  http_response_code(400);
  echo "Missing session_id";
  exit;
}

try {
  $pdo = new PDO(
    "mysql:host=127.0.0.1;dbname=analytics;charset=utf8mb4",
    "erick",
    "StrongPass123!",
    [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_EMULATE_PREPARES => false,
    ]
  );

  $stmt = $pdo->prepare(
    "INSERT INTO events (session_id, event_type, page, payload)
     VALUES (:sid, :etype, :page, CAST(:payload AS JSON))"
  );

  $stmt->execute([
    ':sid' => $session_id,
    ':etype' => $event_type,
    ':page' => $page,
    ':payload' => json_encode($data, JSON_UNESCAPED_SLASHES),
  ]);

  http_response_code(204);
  exit;

} catch (Throwable $e) {
  error_log('LOG INSERT ERROR: ' . $e->getMessage());
  http_response_code(500);
  exit;
}
