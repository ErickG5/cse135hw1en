<?php
require_once __DIR__ . '/middleware/role_check.php';
require_role(['super_admin', 'analyst', 'viewer']);
require_once __DIR__ . '/models/db.php';
require_once __DIR__ . '/models/report_model.php';
require_once __DIR__ . '/models/date_filter.php';

$range = getReportDateRange();
$data = getPerformanceReportData($pdo, $range['start_date'], $range['end_date']);

$averageLoad = 0;
$slowestPage = 'N/A';

if (!empty($data['slow_pages'])) {
    $slowestPage = $data['slow_pages'][0]['page'] ?? 'N/A';
}

if (!empty($data['avg_load_by_day'])) {
    $values = array_filter($data['avg_load_by_day'], fn($v) => (float)$v > 0);
    if ($values) {
        $averageLoad = round(array_sum($values) / count($values), 2);
    }
}

$status = 'Healthy';
$statusClass = 'status-healthy';
$statusNote = 'Page load time is within healthy limits.';

if ($averageLoad > 1000) {
    $status = 'Needs Attention';
    $statusClass = 'status-attention';
    $statusNote = 'Load times exceed one second and could harm user experience.';
} elseif ($averageLoad >= 600) {
    $status = 'Watch';
    $statusClass = 'status-watch';
    $statusNote = 'Performance is acceptable but slower pages should be monitored.';
}

$bucketLabels = json_encode(array_keys($data['load_buckets']));
$bucketValues = json_encode(array_values($data['load_buckets']));

include __DIR__ . '/views/header.php';
include __DIR__ . '/views/navbar.php';
include __DIR__ . '/views/report_styles.php';
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>

<div class="report-page">
    <h1>Performance Snapshot</h1>
    <p class="subtitle">Published performance snapshot for the selected date range.</p>

    <form method="GET" class="filter-form" id="performance-filter-form">
        <label>Start Date:</label>
        <input type="date" name="start_date" id="performance-start-date" value="<?php echo htmlspecialchars($range['start_date'], ENT_QUOTES, 'UTF-8'); ?>">

        <label>End Date:</label>
        <input type="date" name="end_date" id="performance-end-date" value="<?php echo htmlspecialchars($range['end_date'], ENT_QUOTES, 'UTF-8'); ?>">

        <button type="submit">Apply</button>
    </form>

    <p><strong>Date Range:</strong> <?php echo htmlspecialchars($range['label'], ENT_QUOTES, 'UTF-8'); ?></p>

    <?php if (in_array($_SESSION['role'] ?? '', ['super_admin', 'analyst'], true)): ?>
        <div class="notes-card" style="margin-top: 24px;">
            <h2>Generate Saved Report</h2>
            <form method="POST" action="/save_export.php?report=performance&start_date=<?php echo urlencode($range['start_date']); ?>&end_date=<?php echo urlencode($range['end_date']); ?>">
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
                    <a href="/export.php?report=performance&start_date=<?php echo urlencode($range['start_date']); ?>&end_date=<?php echo urlencode($range['end_date']); ?>" 
                    class="prepare-snapshot-btn btn-outline" 
                    target="_blank">
                        Open PDF
                    </a>
                </div>
            </form>
        </div>
    <?php else: ?>
        <div style="margin-top: 24px;">
            <a href="/export.php?report=performance&start_date=<?php echo urlencode($range['start_date']); ?>&end_date=<?php echo urlencode($range['end_date']); ?>" 
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
            <h3>Average Load</h3>
            <div class="metric"><?php echo htmlspecialchars((string)$averageLoad, ENT_QUOTES, 'UTF-8'); ?> ms</div>
        </div>

        <div class="summary-card">
            <h3>Slowest Page</h3>
            <div class="metric"><?php echo htmlspecialchars($slowestPage, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
    </div>

    <div class="chart-grid">
        <div class="chart-card wide">
            <h2>Load Time Distribution</h2>
            <div class="chart-wrap"><canvas id="perfChart"></canvas></div>
        </div>
    </div>

    <div class="notes-card">
        <h2>Insight</h2>
        <p>Most page loads fall within acceptable ranges. The slowest page currently is <?php echo htmlspecialchars($slowestPage, ENT_QUOTES, 'UTF-8'); ?>.</p>
    </div>
</div>

<script>
new Chart(document.getElementById('perfChart'), {
    type: 'bar',
    data: {
        labels: <?= $bucketLabels ?>,
        datasets: [{ label: 'Loads', data: <?= $bucketValues ?>, borderWidth: 1 }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});
</script>

<?php include __DIR__ . '/views/footer.php'; ?>
