<?php
require_once __DIR__ . '/middleware/role_check.php';
require_role(['super_admin', 'analyst']);
require_section('errors');
require_once __DIR__ . '/models/db.php';
require_once __DIR__ . '/models/report_model.php';
require_once __DIR__ . '/models/date_filter.php';

$range = getReportDateRange();
$data = getErrorReportData($pdo, $range['start_date'], $range['end_date']);

$totalErrors = array_sum($data['errors_by_day']);
$topErrorPage = $data['top_error_pages'][0]['page'] ?? 'N/A';
$topErrorType = $data['top_error_types'][0]['error_type'] ?? 'unknown';

$previousRange = getPreviousDateRange($range['start_date'], $range['end_date']);
$previousData = getErrorReportData($pdo, $previousRange['start_date'], $previousRange['end_date']);
$previousTotalErrors = array_sum($previousData['errors_by_day']);

$errorsDelta = formatDelta($totalErrors, $previousTotalErrors);

$errorTrendLabels = json_encode(array_keys($data['errors_by_day']));
$errorTrendValues = json_encode(array_values($data['errors_by_day']));
$errorPageLabels = json_encode(array_map(fn($r) => $r['page'], $data['top_error_pages']));
$errorPageValues = json_encode(array_map(fn($r) => (int)$r['error_count'], $data['top_error_pages']));
$errorTypeLabels = json_encode(array_map(fn($r) => $r['error_type'] ?? 'unknown', $data['top_error_types']));
$errorTypeValues = json_encode(array_map(fn($r) => (int)$r['error_count'], $data['top_error_types']));

include __DIR__ . '/views/header.php';
include __DIR__ . '/views/navbar.php';
include __DIR__ . '/views/report_styles.php';
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>

<div class="report-page">
    <h1>Error Report</h1>
    <p class="subtitle">Error analytics for the selected date range.</p>

    <div class="quick-range-bar">
        <a href="?range=7"><button type="button" class="<?php echo activeRangeClass($range['range'], '7'); ?>">Last 7 Days</button></a>
        <a href="?range=14"><button type="button" class="<?php echo activeRangeClass($range['range'], '14'); ?>">Last 14 Days</button></a>
        <a href="?range=30"><button type="button" class="<?php echo activeRangeClass($range['range'], '30'); ?>">Last 30 Days</button></a>
        <a href="?"><button type="button" class="<?php echo $range['range'] === '' ? 'quick-range-btn active' : 'quick-range-btn'; ?>">Custom</button></a>
    </div>

    <form method="GET" class="filter-form" id="errors-filter-form">
        <label>Start Date:</label>
        <input type="date" name="start_date" id="errors-start-date" value="<?php echo htmlspecialchars($range['start_date'], ENT_QUOTES, 'UTF-8'); ?>">

        <label>End Date:</label>
        <input type="date" name="end_date" id="errors-end-date" value="<?php echo htmlspecialchars($range['end_date'], ENT_QUOTES, 'UTF-8'); ?>">

        <button type="submit">Apply</button>
    </form>

    <p><strong>Date Range:</strong> <?php echo htmlspecialchars($range['label'], ENT_QUOTES, 'UTF-8'); ?></p>
    <div style="margin-bottom: 10px; margin-top: 10px;">
        <a href="/saved_errors.php?start_date=<?php echo urlencode($range['start_date']); ?>&end_date=<?php echo urlencode($range['end_date']); ?>" class="prepare-snapshot-btn">
            Prepare Report Snapshot
        </a>
    </div>



    <div class="summary-grid">
        <div class="summary-card">
            <h3>Total Errors</h3>
            <div class="metric"><?php echo htmlspecialchars((string)$totalErrors, ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="kpi-delta <?php echo htmlspecialchars($errorsDelta['class'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo htmlspecialchars($errorsDelta['text'], ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <div class="kpi-subtext">vs previous period</div>
        </div>

        <div class="summary-card">
            <h3>Top Error Page</h3>
            <div class="metric"><?php echo htmlspecialchars((string)$topErrorPage, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>

        <div class="summary-card">
            <h3>Top Error Type</h3>
            <div class="metric"><?php echo htmlspecialchars((string)$topErrorType, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
    </div>

    <div class="chart-grid">
        <div class="chart-card">
            <h2>Errors per Day</h2>
            <div class="chart-wrap"><canvas id="errorTrendChart"></canvas></div>
        </div>

        <div class="chart-card">
            <h2>Top Error Types</h2>
            <div class="chart-wrap"><canvas id="errorTypeChart"></canvas></div>
        </div>

        <div class="chart-card wide">
            <h2>Top Error Pages</h2>
            <div class="chart-wrap"><canvas id="errorPageChart"></canvas></div>
        </div>
    </div>

    <div class="table-card">
        <h2>Top Error Pages Table</h2>
        <table>
            <thead>
                <tr>
                    <th>Page</th>
                    <th>Error Count</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data['top_error_pages'] as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string)$row['page'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$row['error_count'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="table-card">
        <h2>Top Error Types Table</h2>
        <table>
            <thead>
                <tr>
                    <th>Error Type</th>
                    <th>Error Count</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data['top_error_types'] as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string)($row['error_type'] ?? 'unknown'), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$row['error_count'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="notes-card">
        <h2>Key Insight</h2>
        <p>Error analysis should prioritize recurring failures and where they occur. The current top error page is <?php echo htmlspecialchars($topErrorPage, ENT_QUOTES, 'UTF-8'); ?> and the most common error type is <?php echo htmlspecialchars($topErrorType, ENT_QUOTES, 'UTF-8'); ?>.</p>
    </div>
</div>

<script>
new Chart(document.getElementById('errorTrendChart'), {
    type: 'line',
    data: {
        labels: <?= $errorTrendLabels ?>,
        datasets: [{ label: 'Errors', data: <?= $errorTrendValues ?>, borderWidth: 2, tension: 0.2 }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});

new Chart(document.getElementById('errorTypeChart'), {
    type: 'bar',
    data: {
        labels: <?= $errorTypeLabels ?>,
        datasets: [{ label: 'Errors', data: <?= $errorTypeValues ?>, borderWidth: 1 }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});

new Chart(document.getElementById('errorPageChart'), {
    type: 'bar',
    data: {
        labels: <?= $errorPageLabels ?>,
        datasets: [{ label: 'Errors', data: <?= $errorPageValues ?>, borderWidth: 1 }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});

(function () {
    const form = document.getElementById('errors-filter-form');
    const start = document.getElementById('errors-start-date');
    const end = document.getElementById('errors-end-date');

    function maybeSubmit() {
        if (start.value && end.value) {
            form.submit();
        }
    }

    start.addEventListener('change', maybeSubmit);
    end.addEventListener('change', maybeSubmit);
})();
</script>

<?php include __DIR__ . '/views/footer.php'; ?>
