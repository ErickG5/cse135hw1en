<?php
require_once __DIR__ . '/middleware/role_check.php';
require_role(['super_admin', 'analyst']);
require_section('performance');
require_once __DIR__ . '/models/db.php';
require_once __DIR__ . '/models/report_model.php';
require_once __DIR__ . '/models/date_filter.php';

$range = getReportDateRange();
$data = getPerformanceReportData($pdo, $range['start_date'], $range['end_date']);

$averageLoad = 0;
$sampleCount = 0;
$slowestPage = 'N/A';

if (!empty($data['slow_pages'])) {
    $slowestPage = $data['slow_pages'][0]['page'] ?? 'N/A';
    $sampleCount = array_sum(array_map(fn($r) => (int)$r['samples'], $data['slow_pages']));
}

if (!empty($data['avg_load_by_day'])) {
    $nonZero = array_filter($data['avg_load_by_day'], fn($v) => (float)$v > 0);
    if (count($nonZero) > 0) {
        $averageLoad = round(array_sum($nonZero) / count($nonZero), 2);
    }
}

$previousRange = getPreviousDateRange($range['start_date'], $range['end_date']);
$previousData = getPerformanceReportData($pdo, $previousRange['start_date'], $previousRange['end_date']);

$previousAverageLoad = 0;
$previousSampleCount = 0;

if (!empty($previousData['slow_pages'])) {
    $previousSampleCount = array_sum(array_map(fn($r) => (int)$r['samples'], $previousData['slow_pages']));
}

if (!empty($previousData['avg_load_by_day'])) {
    $nonZeroPrev = array_filter($previousData['avg_load_by_day'], fn($v) => (float)$v > 0);
    if (count($nonZeroPrev) > 0) {
        $previousAverageLoad = round(array_sum($nonZeroPrev) / count($nonZeroPrev), 2);
    }
}

$averageDelta = formatDelta($averageLoad, $previousAverageLoad);
$samplesDelta = formatDelta($sampleCount, $previousSampleCount);

$bucketLabels = json_encode(array_keys($data['load_buckets']));
$bucketValues = json_encode(array_values($data['load_buckets']));
$slowPageLabels = json_encode(array_map(fn($r) => $r['page'], $data['slow_pages']));
$slowPageValues = json_encode(array_map(fn($r) => (float)$r['avg_load_ms'], $data['slow_pages']));
$trendLabels = json_encode(array_keys($data['avg_load_by_day']));
$trendValues = json_encode(array_values($data['avg_load_by_day']));

include __DIR__ . '/views/header.php';
include __DIR__ . '/views/navbar.php';
include __DIR__ . '/views/report_styles.php';
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>

<div class="report-page">
    <h1>Performance Report</h1>
    <p class="subtitle">Performance analytics for the selected date range.</p>

    <div class="quick-range-bar">
        <a href="?range=7"><button type="button" class="<?php echo activeRangeClass($range['range'], '7'); ?>">Last 7 Days</button></a>
        <a href="?range=14"><button type="button" class="<?php echo activeRangeClass($range['range'], '14'); ?>">Last 14 Days</button></a>
        <a href="?range=30"><button type="button" class="<?php echo activeRangeClass($range['range'], '30'); ?>">Last 30 Days</button></a>
        <a href="?"><button type="button" class="<?php echo $range['range'] === '' ? 'quick-range-btn active' : 'quick-range-btn'; ?>">Custom</button></a>
    </div>

    <form method="GET" class="filter-form" id="performance-filter-form">
        <label>Start Date:</label>
        <input type="date" name="start_date" id="performance-start-date" value="<?php echo htmlspecialchars($range['start_date'], ENT_QUOTES, 'UTF-8'); ?>">

        <label>End Date:</label>
        <input type="date" name="end_date" id="performance-end-date" value="<?php echo htmlspecialchars($range['end_date'], ENT_QUOTES, 'UTF-8'); ?>">

        <button type="submit">Apply</button>
    </form>

    <p><strong>Date Range:</strong> <?php echo htmlspecialchars($range['label'], ENT_QUOTES, 'UTF-8'); ?></p>
    <div style="margin-bottom: 10px; margin-top: 10px;">
        <a href="/saved_performance.php?start_date=<?php echo urlencode($range['start_date']); ?>&end_date=<?php echo urlencode($range['end_date']); ?>" class="prepare-snapshot-btn">
            Prepare Report Snapshot
        </a>
    </div>



    <div class="summary-grid">
        <div class="summary-card">
            <h3>Average Load Time</h3>
            <div class="metric"><?php echo htmlspecialchars((string)$averageLoad, ENT_QUOTES, 'UTF-8'); ?> ms</div>
            <div class="kpi-delta <?php echo htmlspecialchars($averageDelta['class'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo htmlspecialchars($averageDelta['text'], ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <div class="kpi-subtext">vs previous period</div>
        </div>

        <div class="summary-card">
            <h3>Tracked Samples</h3>
            <div class="metric"><?php echo htmlspecialchars((string)$sampleCount, ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="kpi-delta <?php echo htmlspecialchars($samplesDelta['class'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo htmlspecialchars($samplesDelta['text'], ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <div class="kpi-subtext">vs previous period</div>
        </div>

        <div class="summary-card">
            <h3>Slowest Page</h3>
            <div class="metric"><?php echo htmlspecialchars((string)$slowestPage, ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="kpi-subtext">highest average load</div>
        </div>
    </div>

    <div class="chart-grid">
        <div class="chart-card">
            <h2>Load Time Distribution</h2>
            <div class="chart-wrap"><canvas id="loadBucketChart"></canvas></div>
        </div>

        <div class="chart-card">
            <h2>Daily Average Load Time</h2>
            <div class="chart-wrap"><canvas id="loadTrendChart"></canvas></div>
        </div>

        <div class="chart-card wide">
            <h2>Slowest Pages</h2>
            <div class="chart-wrap"><canvas id="slowPagesChart"></canvas></div>
        </div>
    </div>

    <div class="table-card">
        <h2>Slowest Pages Table</h2>
        <table>
            <thead>
                <tr>
                    <th>Page</th>
                    <th>Average Load (ms)</th>
                    <th>Samples</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data['slow_pages'] as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string)$row['page'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$row['avg_load_ms'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$row['samples'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="notes-card">
        <h2>Key Insight</h2>
        <p>Performance is best judged by both distribution and trend. The current slowest page is <?php echo htmlspecialchars($slowestPage, ENT_QUOTES, 'UTF-8'); ?>, which is the strongest candidate for optimization.</p>
    </div>
</div>

<script>
new Chart(document.getElementById('loadBucketChart'), {
    type: 'bar',
    data: {
        labels: <?= $bucketLabels ?>,
        datasets: [{ label: 'Page Loads', data: <?= $bucketValues ?>, borderWidth: 1 }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});

new Chart(document.getElementById('loadTrendChart'), {
    type: 'line',
    data: {
        labels: <?= $trendLabels ?>,
        datasets: [{ label: 'Average Load (ms)', data: <?= $trendValues ?>, borderWidth: 2, tension: 0.2 }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});

new Chart(document.getElementById('slowPagesChart'), {
    type: 'bar',
    data: {
        labels: <?= $slowPageLabels ?>,
        datasets: [{ label: 'Average Load (ms)', data: <?= $slowPageValues ?>, borderWidth: 1 }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});

(function () {
    const form = document.getElementById('performance-filter-form');
    const start = document.getElementById('performance-start-date');
    const end = document.getElementById('performance-end-date');

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
