<?php
require_once __DIR__ . '/middleware/role_check.php';
require_role(['super_admin', 'analyst', 'viewer']);
require_once __DIR__ . '/models/db.php';
require_once __DIR__ . '/models/report_model.php';
require_once __DIR__ . '/models/date_filter.php';

$range = getReportDateRange();
$data = getBehaviorReportData($pdo, $range['start_date'], $range['end_date']);

$totalEvents = array_sum($data['event_counts']);
$topPage = $data['top_pages'][0]['page'] ?? 'N/A';

$status = 'Healthy';
$statusClass = 'status-healthy';
$statusNote = 'User interaction indicates healthy engagement.';

if ($totalEvents < 20) {
    $status = 'Watch';
    $statusClass = 'status-watch';
    $statusNote = 'Engagement levels are relatively low.';
}

$eventLabels = json_encode(array_keys($data['event_counts']));
$eventValues = json_encode(array_values($data['event_counts']));

include __DIR__ . '/views/header.php';
include __DIR__ . '/views/navbar.php';
include __DIR__ . '/views/report_styles.php';
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>

<div class="report-page">
    <h1>Behavior Snapshot</h1>
    <p class="subtitle">Published behavior snapshot for the selected date range.</p>

    <form method="GET" class="filter-form" id="behavior-filter-form">
        <label>Start Date:</label>
        <input type="date" name="start_date" id="behavior-start-date" value="<?php echo htmlspecialchars($range['start_date'], ENT_QUOTES, 'UTF-8'); ?>">

        <label>End Date:</label>
        <input type="date" name="end_date" id="behavior-end-date" value="<?php echo htmlspecialchars($range['end_date'], ENT_QUOTES, 'UTF-8'); ?>">

        <button type="submit">Apply</button>
    </form>


    <p><strong>Date Range:</strong> <?php echo htmlspecialchars($range['label'], ENT_QUOTES, 'UTF-8'); ?></p>

    <?php if (in_array($_SESSION['role'] ?? '', ['super_admin', 'analyst'], true)): ?>
        <div class="notes-card" style="margin-top: 24px;">
            <h2>Generate Saved Report</h2>
            <form method="POST" action="/save_export.php?report=behavior&start_date=<?php echo urlencode($range['start_date']); ?>&end_date=<?php echo urlencode($range['end_date']); ?>">
                <div style="margin-bottom: 16px;">
                    <label for="analyst_comment" class="analyst-comment-label">Analyst Comment</label>
                    <textarea
                        id="analyst_comment"
                        name="analyst_comment"
                        rows="5"
                        class="analyst-comment-textarea"
                        placeholder="Add explanatory text for this saved report..."></textarea>
                </div>
                
                <div class="button-group">
                    <button type="submit" class="prepare-snapshot-btn btn-primary">Generate Report</button>
                    <a href="/export.php?report=behavior&start_date=<?php echo urlencode($range['start_date']); ?>&end_date=<?php echo urlencode($range['end_date']); ?>" 
                    class="prepare-snapshot-btn btn-outline" 
                    target="_blank">
                        Open PDF
                    </a>
                </div>
            </form>
        </div>
    <?php else: ?>
        <div style="margin-top: 24px;">
            <a href="/export.php?report=behavior&start_date=<?php echo urlencode($range['start_date']); ?>&end_date=<?php echo urlencode($range['end_date']); ?>" 
            class="prepare-snapshot-btn" 
            target="_blank">
                Open PDF
            </a>
        </div>
    <?php endif; ?>

    <div class="summary-grid">
        <div class="summary-card">
            <h3>Status</h3>
            <div class="metric <?php echo $statusClass; ?>">
    <span class="status-dot <?php
        echo $statusClass === 'status-healthy' ? 'healthy' :
             ($statusClass === 'status-watch' ? 'watch' : 'attention');
    ?>"></span>
    <?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>
</div>

            <div class="status-note"><?php echo htmlspecialchars($statusNote, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>

        <div class="summary-card">
            <h3>Total Events</h3>
            <div class="metric"><?php echo htmlspecialchars((string)$totalEvents, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>

        <div class="summary-card">
            <h3>Top Page</h3>
            <div class="metric"><?php echo htmlspecialchars($topPage, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
    </div>

    <div class="chart-grid">
        <div class="chart-card wide">
            <h2>Event Activity Mix</h2>
            <div class="chart-wrap"><canvas id="behaviorChart"></canvas></div>
        </div>
    </div>

    <div class="notes-card">
        <h2>Insight</h2>
        <p>The majority of interaction activity occurs on <?php echo htmlspecialchars($topPage, ENT_QUOTES, 'UTF-8'); ?>.</p>
    </div>
</div>

<script>
new Chart(document.getElementById('behaviorChart'), {
    type: 'bar',
    data: {
        labels: <?= $eventLabels ?>,
        datasets: [{ label: 'Events', data: <?= $eventValues ?> }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});
</script>

<?php include __DIR__ . '/views/footer.php'; ?>
