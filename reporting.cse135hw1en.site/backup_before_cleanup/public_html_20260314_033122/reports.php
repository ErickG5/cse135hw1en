
<?php
require_once __DIR__ . '/middleware/role_check.php';
require_role(['super_admin']);

require_once __DIR__ . '/models/analytics_model.php';

$events = getRecentEvents($pdo);

include __DIR__ . '/views/header.php';
include __DIR__ . '/views/navbar.php';
include __DIR__ . '/views/report_styles.php';
?>

<div class="report-page">
    <h1>Reports</h1>
    <p class="subtitle">Showing the 100 most recent analytics events.</p>

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
                            <pre style="white-space: pre-wrap; word-break: break-word; max-width: 500px; margin: 0;"><?php echo htmlspecialchars((string)$event['payload'], ENT_QUOTES, 'UTF-8'); ?></pre>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/views/footer.php'; ?>
