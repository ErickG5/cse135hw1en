<?php
require_once __DIR__ . '/db.php';

function getRecentEvents(PDO $pdo): array {
    $stmt = $pdo->query("
        SELECT id, created_at, session_id, event_type, page, payload
        FROM events
        ORDER BY created_at DESC
        LIMIT 100
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getEventTypeCounts(PDO $pdo): array {
    $stmt = $pdo->query("
        SELECT event_type, COUNT(*) AS total
        FROM events
        GROUP BY event_type
        ORDER BY total DESC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
