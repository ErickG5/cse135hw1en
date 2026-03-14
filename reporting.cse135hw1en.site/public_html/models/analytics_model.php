<?php
require_once __DIR__ . '/db.php';

function getRecentEvents(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT id, created_at, session_id, event_type, page, payload
        FROM events
        ORDER BY id DESC
        LIMIT 100
    ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getRecentEventsByType(PDO $pdo, string $type): array
{
    $stmt = $pdo->prepare("
        SELECT id, created_at, session_id, event_type, page, payload
        FROM events
        WHERE event_type = ?
        ORDER BY id DESC
        LIMIT 100
    ");

    $stmt->execute([$type]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
