<?php
require_once __DIR__ . '/middleware/role_check.php';
require_role(['super_admin', 'analyst', 'viewer']);
require_once __DIR__ . '/models/db.php';
require_once __DIR__ . '/models/report_model.php';
require_once __DIR__ . '/models/date_filter.php';

$range = getReportDateRange();
$data = getErrorReportData($pdo, $range['start_date'], $range['end_date']);

$totalErrors = array_sum($data['errors_by_day']);
$topErrorPage = $data['top_error_pages'][0]['page'] ?? 'N/A';

$status = 'Healthy';
$statusClass = 'status-healthy';
$statusNote = 'No significant error volume detected.';

if ($totalErrors > 20) {
    $status = 'Needs Attention';
    $statusClass = 'status-attention';
    $statusNote = 'Error volume is high and should be investigated.';
} elseif ($totalErrors > 0) {
    $status = 'Watch';
    $statusClass = 'status-watch';
    $statusNote = 'Some errors detected but not at critical levels.';
}

$errorTrendLabels = json_encode(array_keys($data['errors_by_day']));
$errorTrendValues = json_encode(array_values($data['errors_by_day']));

include __DIR__ . '/views/header.php';
include __DIR__ . '/views/navbar.php';
include __DIR__ . '/views/report_styles.php';
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>

<div class="report-page">
    <h1>Error Snapshot</h1>
    <p class="subtitle">Published error snapshot for the selected date range.</p>

    <form method="GET" class="filter-form" id="errors-filter-form">
        <label>Start Date:</label>
        <input type="date" name="start_date" id="errors-start-date" value="<?php echo htmlspecialchars($range['start_date'], ENT_QUOTES, 'UTF-8'); ?>">

        <label>End Date:</label>
        <input type="date" name="end_date" id="errors-end-date" value="<?php echo htmlspecialchars($range['end_date'], ENT_QUOTES, 'UTF-8'); ?>">

        <button type="submit">Apply</button>
    </form>

    <p><strong>Date Range:</strong> <?php echo htmlspecialchars($range['label'], ENT_QUOTES, 'UTF-8'); ?></p>

    <?php if (in_array($_SESSION['role'] ?? '', ['super_admin', 'analyst'], true)): ?>
        <div class="notes-card" style="margin-top: 24px;">
            <h2>Generate Saved Report</h2>
            <form method="POST" action="/save_export.php?report=errors&start_date=<?php echo urlencode($range['start_date']); ?>&end_date=<?php echo urlencode($range['end_date']); ?>">
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
                    <a href="/export.php?report=errors&start_date=<?php echo urlencode($range['start_date']); ?>&end_date=<?php echo urlencode($range['end_date']); ?>" 
                    class="prepare-snapshot-btn btn-outline" 
                    target="_blank">
                        Open PDF
                    </a>
                </div>
            </form>
        </div>
    <?php else: ?>
        <div style="margin-top: 24px;">
            <a href="/export.php?report=errors&start_date=<?php echo urlencode($range['start_date']); ?>&end_date=<?php echo urlencode($range['end_date']); ?>" 
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
            <h3>Total Errors</h3>
            <div class="metric"><?php echo htmlspecialchars((string)$totalErrors, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>

        <div class="summary-card">
            <h3>Top Error Page</h3>
            <div class="metric"><?php echo htmlspecialchars($topErrorPage, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
    </div>

    <div class="chart-grid">
        <div class="chart-card wide">
            <h2>Errors per Day</h2>
            <div class="chart-wrap"><canvas id="errorChart"></canvas></div>
        </div>
    </div>

    <div class="notes-card">
        <h2>Insight</h2>
        <p>Errors are primarily occurring on <?php echo htmlspecialchars($topErrorPage, ENT_QUOTES, 'UTF-8'); ?>.</p>
    </div>
</div>

<script>
new Chart(document.getElementById('errorChart'), {
    type: 'line',
    data: {
        labels: <?= $errorTrendLabels ?>,
        datasets: [{ label: 'Errors', data: <?= $errorTrendValues ?>, borderWidth: 2, tension: 0.2 }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});
</script>

<?php include __DIR__ . '/views/footer.php'; ?>
