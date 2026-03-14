<?php
require_once __DIR__ . '/middleware/role_check.php';
require_role(['super_admin']);

require_once __DIR__ . '/models/analytics_model.php';

$type = $_GET['type'] ?? '';

if ($type !== '') {
    $events = getRecentEventsByType($pdo, $type);
} else {
    $events = getRecentEvents($pdo);
}

include __DIR__ . '/views/header.php';
include __DIR__ . '/views/navbar.php';
include __DIR__ . '/views/report_styles.php';
?>

<style>
    .payload-box {
        max-width: 520px;
        max-height: 260px;
        overflow: auto;
        background: rgba(15, 23, 42, 0.72);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 10px;
        padding: 12px;
        scrollbar-width: thin;
        scrollbar-color: rgba(79,124,255,0.6) rgba(15,23,42,0.4);
    }

    .payload-box::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    .payload-box::-webkit-scrollbar-track {
        background: rgba(15, 23, 42, 0.4);
        border-radius: 10px;
    }

    .payload-box::-webkit-scrollbar-thumb {
        background: rgba(79,124,255,0.55);
        border-radius: 10px;
        transition: background 150ms ease;
    }

    .payload-box::-webkit-scrollbar-thumb:hover {
        background: rgba(79,124,255,0.8);
    }

    .payload-pre {
        margin: 0;
        white-space: pre-wrap;
        word-break: break-word;
        color: #e5e7eb;
        font-size: 0.8rem;
        line-height: 1.5;
        font-family: 'DM Mono', monospace;
    }
</style>

<div class="report-page">
    <h1>Reports</h1>
    <p class="subtitle">Showing the 100 most recent analytics events.</p>

    <div class="quick-range-bar">
        <a href="/reports.php" class="<?php echo $type === '' ? 'quick-range-btn active' : 'quick-range-btn'; ?>">
            All
        </a>

        <a href="/reports.php?type=pageview" class="<?php echo $type === 'pageview' ? 'quick-range-btn active' : 'quick-range-btn'; ?>">
            Pageviews
        </a>

        <a href="/reports.php?type=capability" class="<?php echo $type === 'capability' ? 'quick-range-btn active' : 'quick-range-btn'; ?>">
            Capability
        </a>

        <a href="/reports.php?type=activity_batch" class="<?php echo $type === 'activity_batch' ? 'quick-range-btn active' : 'quick-range-btn'; ?>">
            Activity
        </a>

        <a href="/reports.php?type=error" class="<?php echo $type === 'error' ? 'quick-range-btn active' : 'quick-range-btn'; ?>">
            Errors
        </a>

        <a href="/reports.php?type=page_exit" class="<?php echo $type === 'page_exit' ? 'quick-range-btn active' : 'quick-range-btn'; ?>">
            Page Exit
        </a>
    </div>

    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Created At</th>
                    <th>Session ID</th>
                    <th>Event Type</th>
                    <th>Page</th>
                    <th>Payload</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($events as $event): ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string)$event['id'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$event['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$event['session_id'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$event['event_type'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)($event['page'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <div class="payload-box">
                                <pre class="payload-pre"><?php
                                    $decodedPayload = json_decode((string)$event['payload'], true);
                                    $prettyPayload = $decodedPayload !== null
                                        ? json_encode($decodedPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                                        : (string)$event['payload'];

                                    echo htmlspecialchars($prettyPayload, ENT_QUOTES, 'UTF-8');
                                ?></pre>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/views/footer.php'; ?>

