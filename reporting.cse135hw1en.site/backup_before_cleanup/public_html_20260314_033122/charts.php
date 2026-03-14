<?php
require_once __DIR__ . '/middleware/role_check.php';
require_role(['super_admin', 'analyst']);

require_once __DIR__ . '/models/db.php';
require_once __DIR__ . '/models/report_model.php';
require_once __DIR__ . '/models/date_filter.php';

$range = getReportDateRange();

$trafficData = getTrafficReportData($pdo, $range['start_date'], $range['end_date']);
$performanceData = getPerformanceReportData($pdo, $range['start_date'], $range['end_date']);
$behaviorData = getBehaviorReportData($pdo, $range['start_date'], $range['end_date']);
$errorData = getErrorReportData($pdo, $range['start_date'], $range['end_date']);

$totalSessions = array_sum($trafficData['sessions_by_day']);
$totalErrors = array_sum($errorData['errors_by_day']);
$totalEvents = array_sum($behaviorData['event_counts']);

$averageLoad = 0;
if (!empty($performanceData['avg_load_by_day'])) {
    $nonZeroLoads = array_filter($performanceData['avg_load_by_day'], fn($v) => (float)$v > 0);
    if (!empty($nonZeroLoads)) {
        $averageLoad = round(array_sum($nonZeroLoads) / count($nonZeroLoads), 2);
    }
}

$topBrowser = 'N/A';
if (!empty($trafficData['browser_counts'])) {
    $topBrowser = array_keys($trafficData['browser_counts'], max($trafficData['browser_counts']))[0] ?? 'N/A';
}

$topBehaviorEvent = 'N/A';
if (!empty($behaviorData['event_counts'])) {
    $topBehaviorEvent = array_keys($behaviorData['event_counts'], max($behaviorData['event_counts']))[0] ?? 'N/A';
}

$topErrorPage = $errorData['top_error_pages'][0]['page'] ?? 'N/A';

$trafficLabels = json_encode(array_keys($trafficData['sessions_by_day']));
$trafficValues = json_encode(array_values($trafficData['sessions_by_day']));

$browserLabels = json_encode(array_keys($trafficData['browser_counts']));
$browserValues = json_encode(array_values($trafficData['browser_counts']));

$performanceLabels = json_encode(array_keys($performanceData['load_buckets']));
$performanceValues = json_encode(array_values($performanceData['load_buckets']));

$behaviorLabels = json_encode(array_keys($behaviorData['event_counts']));
$behaviorValues = json_encode(array_values($behaviorData['event_counts']));

$errorLabels = json_encode(array_keys($errorData['errors_by_day']));
$errorValues = json_encode(array_values($errorData['errors_by_day']));

include __DIR__ . '/views/header.php';
include __DIR__ . '/views/navbar.php';
include __DIR__ . '/views/report_styles.php';
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>

<div class="report-page">
    <h1>Analytics Overview</h1>
    <p class="subtitle">High-level analytics summary for the selected date range.</p>

    <div class="quick-range-bar">
        <a href="?range=7"><button type="button" class="<?php echo activeRangeClass($range['range'], '7'); ?>">Last 7 Days</button></a>
        <a href="?range=14"><button type="button" class="<?php echo activeRangeClass($range['range'], '14'); ?>">Last 14 Days</button></a>
        <a href="?range=30"><button type="button" class="<?php echo activeRangeClass($range['range'], '30'); ?>">Last 30 Days</button></a>
        <a href="?"><button type="button" class="<?php echo $range['range'] === '' ? 'quick-range-btn active' : 'quick-range-btn'; ?>">Custom</button></a>
    </div>

    <form method="GET" class="filter-form">
        <label>Start Date:</label>
        <input type="date" name="start_date" value="<?php echo htmlspecialchars($range['start_date'], ENT_QUOTES, 'UTF-8'); ?>">

        <label>End Date:</label>
        <input type="date" name="end_date" value="<?php echo htmlspecialchars($range['end_date'], ENT_QUOTES, 'UTF-8'); ?>">

        <button type="submit">Apply</button>
    </form>

    <p><strong>Date Range:</strong> <?php echo htmlspecialchars($range['label'], ENT_QUOTES, 'UTF-8'); ?></p>

    <div class="summary-grid">
        <div class="summary-card">
            <h3>Total Sessions</h3>
            <div class="metric"><?php echo htmlspecialchars((string)$totalSessions, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>

        <div class="summary-card">
            <h3>Average Load</h3>
            <div class="metric"><?php echo htmlspecialchars((string)$averageLoad, ENT_QUOTES, 'UTF-8'); ?> ms</div>
        </div>

        <div class="summary-card">
            <h3>Total Events</h3>
            <div class="metric"><?php echo htmlspecialchars((string)$totalEvents, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>

        <div class="summary-card">
            <h3>Total Errors</h3>
            <div class="metric"><?php echo htmlspecialchars((string)$totalErrors, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
    </div>

    <div class="summary-grid">
        <div class="summary-card">
            <h3>Top Browser</h3>
            <div class="metric"><?php echo htmlspecialchars((string)$topBrowser, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>

        <div class="summary-card">
            <h3>Top Event Type</h3>
            <div class="metric"><?php echo htmlspecialchars((string)$topBehaviorEvent, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>

        <div class="summary-card">
            <h3>Top Error Page</h3>
            <div class="metric"><?php echo htmlspecialchars((string)$topErrorPage, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>

        <div class="summary-card">
            <h3>Overview Scope</h3>
            <div class="metric">All Sections</div>
        </div>
    </div>

    <div class="chart-grid">
        <div class="chart-card">
            <h2>Traffic: Sessions per Day</h2>
            <div class="chart-wrap"><canvas id="trafficChart"></canvas></div>
        </div>

        <div class="chart-card">
            <h2>Traffic: Browser Distribution</h2>
            <div class="chart-wrap"><canvas id="browserChart"></canvas></div>
        </div>

        <div class="chart-card">
            <h2>Performance: Load Time Distribution</h2>
            <div class="chart-wrap"><canvas id="performanceChart"></canvas></div>
        </div>

        <div class="chart-card">
            <h2>Behavior: Event Activity</h2>
            <div class="chart-wrap"><canvas id="behaviorChart"></canvas></div>
        </div>

        <div class="chart-card wide">
            <h2>Errors: Errors per Day</h2>
            <div class="chart-wrap"><canvas id="errorsChart"></canvas></div>
        </div>
    </div>

    <div class="notes-card">
        <h2>Overview Insight</h2>
        <p>
            This overview page combines traffic, performance, behavior, and error analytics for the selected period.
            Use it to spot large changes quickly, then drill into the dedicated section pages for deeper analysis and snapshot generation.
        </p>
    </div>
</div>

<script>
new Chart(document.getElementById('trafficChart'), {
    type: 'line',
    data: {
        labels: <?= $trafficLabels ?>,
        datasets: [{
            label: 'Sessions',
            data: <?= $trafficValues ?>,
            borderWidth: 2,
            tension: 0.2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});

new Chart(document.getElementById('browserChart'), {
    type: 'pie',
    data: {
        labels: <?= $browserLabels ?>,
        datasets: [{
            data: <?= $browserValues ?>,
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});

new Chart(document.getElementById('performanceChart'), {
    type: 'bar',
    data: {
        labels: <?= $performanceLabels ?>,
        datasets: [{
            label: 'Page Loads',
            data: <?= $performanceValues ?>,
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});

new Chart(document.getElementById('behaviorChart'), {
    type: 'bar',
    data: {
        labels: <?= $behaviorLabels ?>,
        datasets: [{
            label: 'Events',
            data: <?= $behaviorValues ?>,
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});

new Chart(document.getElementById('errorsChart'), {
    type: 'line',
    data: {
        labels: <?= $errorLabels ?>,
        datasets: [{
            label: 'Errors',
            data: <?= $errorValues ?>,
            borderWidth: 2,
            tension: 0.2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});
</script>

<?php include __DIR__ . '/views/footer.php'; ?>
