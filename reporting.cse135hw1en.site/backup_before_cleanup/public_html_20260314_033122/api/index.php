<?php
// api.php - REST API for reporting.cse135hw1en.site

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://test.cse135hw1en.site, https://reporting.cse135hw1en.site');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}


$host     = getenv('API_DB_HOST') ?: '127.0.0.1';
$dbname   = getenv('API_DB_NAME') ?: 'analytics';
$username = getenv('API_DB_USER');
$password = getenv('API_DB_PASS');

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    error_log("API DB connection failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit();
}

// ============= ROUTE PARSING =============
$path     = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path     = preg_replace('#^/api#', '', $path);
$segments = explode('/', trim($path, '/'));

$resource = $segments[0] ?? '';
$id       = isset($segments[1]) && $segments[1] !== '' ? $segments[1] : null;
$method   = $_SERVER['REQUEST_METHOD'];

// ============= ROUTER =============
switch ($resource) {
    case 'static':
        handleStaticRoutes($pdo, $method, $id);
        break;
    case 'performance':
        handlePerformanceRoutes($pdo, $method, $id);
        break;
    case 'activity':
        handleActivityRoutes($pdo, $method, $id);
        break;
    case 'sessions':
        handleSessionsRoutes($pdo, $method, $id);
        break;
    case 'events':
        handleEventsRoutes($pdo, $method, $id);
        break;
    default:
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => "Endpoint '/$resource' not found"]);
        break;
}

// ============================================================
//  SHARED HELPERS
// ============================================================

function decodePayload(array &$row): void {
    $row['payload'] = json_decode($row['payload'] ?? '{}', true) ?? [];
}

function readJsonBody(): ?array {
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') return null;
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : null;
}

function fetchEventById(PDO $pdo, $id): ?array {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function deleteById(PDO $pdo, $id): void {
    $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => "Entry $id not found"]);
        return;
    }

    echo json_encode(['success' => true, 'message' => "Entry $id deleted successfully"]);
}

function updateById(PDO $pdo, $id): void {
    $body = readJsonBody();
    if ($body === null) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid or empty JSON body']);
        return;
    }

    $fields  = [];
    $params  = [];

    if (isset($body['event_type'])) {
        $fields[]             = 'event_type = ?';
        $params[]             = $body['event_type'];
    }
    if (isset($body['page'])) {
        $fields[]  = 'page = ?';
        $params[]  = $body['page'];
    }
    if (isset($body['payload'])) {
        $fields[]  = 'payload = CAST(? AS JSON)';
        $params[]  = json_encode($body['payload'], JSON_UNESCAPED_SLASHES);
    } else {
        $fields[]  = 'payload = CAST(? AS JSON)';
        $params[]  = json_encode($body, JSON_UNESCAPED_SLASHES);
    }

    if (empty($fields)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Nothing to update']);
        return;
    }

    $params[] = $id;
    $sql      = 'UPDATE events SET ' . implode(', ', $fields) . ' WHERE id = ?';
    $stmt     = $pdo->prepare($sql);
    $stmt->execute($params);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => "Entry $id not found"]);
        return;
    }

    echo json_encode(['success' => true, 'message' => "Entry $id updated successfully"]);
}

// ============================================================
//  /api/static  — rows where event_type IN ('pageview','capability')
//  These hold the technographic / static snapshot sent by collector.js
// ============================================================
function handleStaticRoutes(PDO $pdo, string $method, ?string $id): void {
    switch ($method) {
        case 'GET':
            try {
                if ($id) {
                    $row = fetchEventById($pdo, $id);
                    if (!$row) {
                        http_response_code(404);
                        echo json_encode(['success' => false, 'error' => 'Entry not found']);
                        return;
                    }
                    decodePayload($row);
                    echo json_encode(['success' => true, 'data' => $row], JSON_PRETTY_PRINT);
                } else {
                    $stmt = $pdo->query("
                        SELECT id, session_id, event_type,
                               page AS url,
                               DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') AS created_at,
                               payload
                        FROM   events
                        WHERE  event_type IN ('pageview', 'capability')
                        ORDER  BY id DESC
                        LIMIT  100
                    ");
                    $rows = $stmt->fetchAll();

                    $out = [];
                    foreach ($rows as $row) {
                        $p   = json_decode($row['payload'] ?? '{}', true) ?? [];
                        $tech = $p['technographics'] ?? [];
                        $tim  = $p['timing']         ?? [];

                        $out[] = [
                            'id'              => $row['id'],
                            'session_id'      => $row['session_id'],
                            'event_type'      => $row['event_type'],
                            'url'             => $row['url'],
                            'created_at'      => $row['created_at'],
                            'user_agent'      => $tech['userAgent']      ?? $p['userAgent']      ?? null,
                            'language'        => $tech['language']       ?? $p['language']       ?? null,
                            'cookies_enabled' => $tech['cookiesEnabled'] ?? $p['cookiesEnabled'] ?? null,
                            'js_enabled'      => $tech['jsEnabled']      ?? $p['jsEnabled']      ?? null,
                            'images_enabled'  => $tech['imagesEnabled']  ?? $p['imagesEnabled']  ?? null,
                            'css_enabled'     => $tech['cssEnabled']     ?? $p['cssEnabled']     ?? null,
                            'screen_width'    => $tech['screenWidth']    ?? $p['screenWidth']    ?? null,
                            'screen_height'   => $tech['screenHeight']   ?? $p['screenHeight']   ?? null,
                            'viewport_width'  => $tech['viewportWidth']  ?? $p['viewportWidth']  ?? null,
                            'viewport_height' => $tech['viewportHeight'] ?? $p['viewportHeight'] ?? null,
                            'connection_type' => $tech['connectionType'] ?? $p['connectionType'] ?? null,
                            'ttfb'            => $tim['ttfb']            ?? null,
                            'load_time'       => $tim['loadEvent']       ?? null,
                        ];
                    }

                    echo json_encode([
                        'success' => true,
                        'count'   => count($out),
                        'data'    => $out,
                    ], JSON_PRETTY_PRINT);
                }
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            break;

        // ── POST /api/static   → insert a new static/capability event
        case 'POST':
            if ($id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Do not supply an ID when POSTing']);
                return;
            }
            insertEvent($pdo, 'pageview');
            break;

        // ── PUT /api/static/{id}    → update an existing row
        case 'PUT':
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'ID required for PUT']);
                return;
            }
            try { updateById($pdo, $id); }
            catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            break;

        // ── DELETE /api/static/{id} → delete a row
        case 'DELETE':
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'ID required for DELETE']);
                return;
            }
            try { deleteById($pdo, $id); }
            catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            break;
    }
}

// ============================================================
//  /api/performance  — rows whose payload contains timing data
//  collector.js sends these as event_type = 'pageview' with a
//  'timing' key inside the payload.
// ============================================================
function handlePerformanceRoutes(PDO $pdo, string $method, ?string $id): void {
    switch ($method) {
        case 'GET':
            try {
                if ($id) {
                    $row = fetchEventById($pdo, $id);
                    if (!$row) {
                        http_response_code(404);
                        echo json_encode(['success' => false, 'error' => 'Entry not found']);
                        return;
                    }
                    decodePayload($row);
                    echo json_encode(['success' => true, 'data' => $row], JSON_PRETTY_PRINT);
                } else {
                    $stmt = $pdo->query("
                        SELECT id, session_id, event_type,
                               page AS url,
                               DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') AS created_at,
                               payload
                        FROM   events
                        WHERE  JSON_CONTAINS_PATH(payload, 'one', '$.timing')
                        ORDER  BY id DESC
                        LIMIT  50
                    ");
                    $rows = $stmt->fetchAll();

                    $out = [];
                    foreach ($rows as $row) {
                        $p   = json_decode($row['payload'] ?? '{}', true) ?? [];
                        $out[] = [
                            'id'         => $row['id'],
                            'session_id' => $row['session_id'],
                            'url'        => $row['url'],
                            'created_at' => $row['created_at'],
                            'timing'     => $p['timing']     ?? null,
                            'resources'  => $p['resources']  ?? null,
                            'vitals'     => $p['vitals']     ?? null,
                        ];
                    }

                    echo json_encode([
                        'success' => true,
                        'count'   => count($out),
                        'data'    => $out,
                    ], JSON_PRETTY_PRINT);
                }
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            break;

        case 'POST':
            if ($id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Do not supply an ID when POSTing']);
                return;
            }
            insertEvent($pdo, 'pageview');
            break;

        case 'PUT':
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'ID required for PUT']);
                return;
            }
            try { updateById($pdo, $id); }
            catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            break;

        case 'DELETE':
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'ID required for DELETE']);
                return;
            }
            try { deleteById($pdo, $id); }
            catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            break;
    }
}

// ============================================================
//  /api/activity  — rows where event_type IN ('event','error',
//                   'activity','page_exit') — the interaction stream
// ============================================================
function handleActivityRoutes(PDO $pdo, string $method, ?string $id): void {
    switch ($method) {
        case 'GET':
            try {
                if ($id) {
                    $row = fetchEventById($pdo, $id);
                    if (!$row) {
                        http_response_code(404);
                        echo json_encode(['success' => false, 'error' => 'Entry not found']);
                        return;
                    }
                    decodePayload($row);
                    echo json_encode(['success' => true, 'data' => $row], JSON_PRETTY_PRINT);
                } else {
                    $stmt = $pdo->query("
                        SELECT id, session_id, event_type,
                               page AS url,
                               DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') AS created_at,
                               payload
                        FROM   events
                        WHERE  event_type IN ('event', 'error', 'activity', 'page_exit')
                        ORDER  BY id DESC
                        LIMIT  100
                    ");
                    $rows = $stmt->fetchAll();

                    $out = [];
                    foreach ($rows as $row) {
                        $p = json_decode($row['payload'] ?? '{}', true) ?? [];
                        $entry = [
                            'id'         => $row['id'],
                            'session_id' => $row['session_id'],
                            'event_type' => $row['event_type'],
                            'url'        => $row['url'],
                            'created_at' => $row['created_at'],
                        ];

                        if ($row['event_type'] === 'error') {
                            $entry['error'] = $p['error'] ?? $p;
                        } else {
                            $entry['event'] = $p['event']    ?? null;
                            $entry['data']  = $p['data']     ?? $p;
                        }
                        $out[] = $entry;
                    }

                    echo json_encode([
                        'success' => true,
                        'count'   => count($out),
                        'data'    => $out,
                    ], JSON_PRETTY_PRINT);
                }
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            break;

        case 'POST':
            if ($id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Do not supply an ID when POSTing']);
                return;
            }
            insertEvent($pdo, 'event');
            break;

        case 'PUT':
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'ID required for PUT']);
                return;
            }
            try { updateById($pdo, $id); }
            catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            break;

        case 'DELETE':
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'ID required for DELETE']);
                return;
            }
            try { deleteById($pdo, $id); }
            catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            break;
    }
}

// ============================================================
//  /api/sessions  — group rows by session_id for session-level view
//  Only GET makes sense here; sessions are created implicitly.
// ============================================================
function handleSessionsRoutes(PDO $pdo, string $method, ?string $id): void {
    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Only GET is supported on /api/sessions']);
        return;
    }

    try {
        if ($id) {
            $stmt = $pdo->prepare("
                SELECT id, event_type,
                       page AS url,
                       DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') AS created_at,
                       payload
                FROM   events
                WHERE  session_id = ?
                ORDER  BY id ASC
            ");
            $stmt->execute([$id]);
            $rows = $stmt->fetchAll();

            foreach ($rows as &$row) {
                decodePayload($row);
            }

            echo json_encode([
                'success'     => true,
                'session_id'  => $id,
                'event_count' => count($rows),
                'data'        => $rows,
            ], JSON_PRETTY_PRINT);

        } else {
            $stmt = $pdo->query("
                SELECT
                    session_id,
                    COUNT(*)                                                   AS event_count,
                    COUNT(DISTINCT page)                                       AS pages_visited,
                    MIN(id)                                                    AS first_event_id,
                    MAX(id)                                                    AS last_event_id,
                    DATE_FORMAT(MIN(created_at), '%Y-%m-%d %H:%i:%s')         AS session_start,
                    DATE_FORMAT(MAX(created_at), '%Y-%m-%d %H:%i:%s')         AS session_end,
                    SUM(event_type = 'error')                                  AS error_count
                FROM   events
                GROUP  BY session_id
                ORDER  BY last_event_id DESC
                LIMIT  50
            ");
            $rows = $stmt->fetchAll();

            echo json_encode([
                'success' => true,
                'count'   => count($rows),
                'data'    => $rows,
            ], JSON_PRETTY_PRINT);
        }

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// ============================================================
//  /api/events  — generic route: full access to the events table
//  Useful for Postman testing and the grader.
// ============================================================
function handleEventsRoutes(PDO $pdo, string $method, ?string $id): void {
    switch ($method) {
        case 'GET':
            try {
                if ($id) {
                    $row = fetchEventById($pdo, $id);
                    if (!$row) {
                        http_response_code(404);
                        echo json_encode(['success' => false, 'error' => 'Entry not found']);
                        return;
                    }
                    decodePayload($row);
                    echo json_encode(['success' => true, 'data' => $row], JSON_PRETTY_PRINT);
                } else {
                    $stmt = $pdo->query("
                        SELECT id, session_id, event_type,
                               page AS url,
                               DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') AS created_at
                        FROM   events
                        ORDER  BY id DESC
                        LIMIT  200
                    ");
                    $rows = $stmt->fetchAll();
                    echo json_encode([
                        'success' => true,
                        'count'   => count($rows),
                        'data'    => $rows,
                    ], JSON_PRETTY_PRINT);
                }
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            break;

        case 'POST':
            if ($id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Do not supply an ID when POSTing']);
                return;
            }
            try { insertEvent($pdo, 'unknown'); }
            catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            break;

        case 'PUT':
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'ID required for PUT']);
                return;
            }
            try { updateById($pdo, $id); }
            catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            break;

        case 'DELETE':
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'ID required for DELETE']);
                return;
            }
            try { deleteById($pdo, $id); }
            catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            break;
    }
}

// ============================================================
//  insertEvent — shared POST handler
//  Reads the JSON body and inserts a new row into `events`.
//  $defaultType is used only when the body omits "type".
// ============================================================
function insertEvent(PDO $pdo, string $defaultType): void {
    $body = readJsonBody();
    if ($body === null) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid or empty JSON body']);
        return;
    }

    // Mirror the same field extraction logic as /log
    $session_id = $body['session_id'] ?? $body['session'] ?? null;
    if (!$session_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing session_id']);
        return;
    }

    $event_type = $body['type'] ?? $body['event_type'] ?? $defaultType;
    $page       = $body['url']  ?? $body['page']       ?? null;

    $stmt = $pdo->prepare(
        "INSERT INTO events (session_id, event_type, page, payload)
         VALUES (:sid, :etype, :page, CAST(:payload AS JSON))"
    );
    $stmt->execute([
        ':sid'     => $session_id,
        ':etype'   => $event_type,
        ':page'    => $page,
        ':payload' => json_encode($body, JSON_UNESCAPED_SLASHES),
    ]);

    $newId = $pdo->lastInsertId();
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Entry created',
        'id'      => $newId,
    ]);
}
