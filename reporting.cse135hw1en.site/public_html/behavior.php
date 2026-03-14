<?php
require_once __DIR__ . '/middleware/role_check.php';
require_role(['super_admin', 'analyst']);
require_section('behavior');
require_once __DIR__ . '/models/db.php';
require_once __DIR__ . '/models/report_model.php';
require_once __DIR__ . '/models/date_filter.php';

$range = getReportDateRange();
$data = getBehaviorReportData($pdo, $range['start_date'], $range['end_date']);

$totalEvents = array_sum($data['event_counts']);
$mostActivePage = $data['top_pages'][0]['page'] ?? 'N/A';
$mostCommonEvent = 'N/A';

if (!empty($data['event_counts'])) {
    $mostCommonEvent = array_keys($data['event_counts'], max($data['event_counts']))[0] ?? 'N/A';
}

$totalSessionsAnalyzed = array_sum($data['session_buckets']);

$previousRange = getPreviousDateRange($range['start_date'], $range['end_date']);
$previousData = getBehaviorReportData($pdo, $previousRange['start_date'], $previousRange['end_date']);
$previousTotalEvents = array_sum($previousData['event_counts']);
$previousSessionsAnalyzed = array_sum($previousData['session_buckets']);

$eventsDelta = formatDelta($totalEvents, $previousTotalEvents);
$sessionsDelta = formatDelta($totalSessionsAnalyzed, $previousSessionsAnalyzed);

$eventLabels = json_encode(array_keys($data['event_counts']));
$eventValues = json_encode(array_values($data['event_counts']));
$topPageLabels = json_encode(array_map(fn($r) => $r['page'], $data['top_pages']));
$topPageValues = json_encode(array_map(fn($r) => (int)$r['event_count'], $data['top_pages']));
$sessionBucketLabels = json_encode(array_keys($data['session_buckets']));
$sessionBucketValues = json_encode(array_values($data['session_buckets']));

include __DIR__ . '/views/header.php';
include __DIR__ . '/views/navbar.php';
include __DIR__ . '/views/report_styles.php';
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>

<div class="report-page">
    <h1>Behavior Report</h1>
    <p class="subtitle">Behavior analytics for the selected date range.</p>

    <div class="quick-range-bar">
        <a href="?range=7"><button type="button" class="<?php echo activeRangeClass($range['range'], '7'); ?>">Last 7 Days</button></a>
        <a href="?range=14"><button type="button" class="<?php echo activeRangeClass($range['range'], '14'); ?>">Last 14 Days</button></a>
        <a href="?range=30"><button type="button" class="<?php echo activeRangeClass($range['range'], '30'); ?>">Last 30 Days</button></a>
        <a href="?"><button type="button" class="<?php echo $range['range'] === '' ? 'quick-range-btn active' : 'quick-range-btn'; ?>">Custom</button></a>
    </div>

    <form method="GET" class="filter-form" id="behavior-filter-form">
        <label>Start Date:</label>
        <input type="date" name="start_date" id="behavior-start-date" value="<?php echo htmlspecialchars($range['start_date'], ENT_QUOTES, 'UTF-8'); ?>">

        <label>End Date:</label>
        <input type="date" name="end_date" id="behavior-end-date" value="<?php echo htmlspecialchars($range['end_date'], ENT_QUOTES, 'UTF-8'); ?>">

        <button type="submit">Apply</button>
    </form>

    <p><strong>Date Range:</strong> <?php echo htmlspecialchars($range['label'], ENT_QUOTES, 'UTF-8'); ?></p>
    <div style="margin-bottom: 10px; margin-top: 10px;">
        <a href="/saved_behavior.php?start_date=<?php echo urlencode($range['start_date']); ?>&end_date=<?php echo urlencode($range['end_date']); ?>" class="prepare-snapshot-btn">
            Prepare Report Snapshot
        </a>
    </div>



    <div class="summary-grid">
        <div class="summary-card">
            <h3>Total Interaction Events</h3>
            <div class="metric"><?php echo htmlspecialchars((string)$totalEvents, ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="kpi-delta <?php echo htmlspecialchars($eventsDelta['class'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo htmlspecialchars($eventsDelta['text'], ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <div class="kpi-subtext">vs previous period</div>
        </div>

        <div class="summary-card">
            <h3>Sessions Analyzed</h3>
            <div class="metric"><?php echo htmlspecialchars((string)$totalSessionsAnalyzed, ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="kpi-delta <?php echo htmlspecialchars($sessionsDelta['class'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo htmlspecialchars($sessionsDelta['text'], ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <div class="kpi-subtext">vs previous period</div>
        </div>

        <div class="summary-card">
            <h3>Most Active Page</h3>
            <div class="metric"><?php echo htmlspecialchars((string)$mostActivePage, ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="kpi-subtext"><?php echo htmlspecialchars((string)$mostCommonEvent, ENT_QUOTES, 'UTF-8'); ?> is the top event</div>
        </div>
    </div>

    <div class="chart-grid">
        <div class="chart-card">
            <h2>Event Activity Mix</h2>
            <div class="chart-wrap"><canvas id="eventMixChart"></canvas></div>
        </div>

        <div class="chart-card">
            <h2>Pages per Session</h2>
            <div class="chart-wrap"><canvas id="sessionDepthChart"></canvas></div>
        </div>

        <div class="chart-card wide">
            <h2>Top Interaction Pages</h2>
            <div class="chart-wrap"><canvas id="topBehaviorPagesChart"></canvas></div>
        </div>
    </div>

    <div class="table-card">
        <h2>Top Interaction Pages Table</h2>
        <table>
            <thead>
                <tr>
                    <th>Page</th>
                    <th>Event Count</th>
                    <th>Session Count</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data['top_pages'] as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string)$row['page'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$row['event_count'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$row['session_count'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="notes-card">
        <h2>Key Insight</h2>
        <p>Behavior is best understood through interaction mix, session depth, and page-level engagement. The most active page is <?php echo htmlspecialchars($mostActivePage, ENT_QUOTES, 'UTF-8'); ?> and the most common event type is <?php echo htmlspecialchars($mostCommonEvent, ENT_QUOTES, 'UTF-8'); ?>.</p>
    </div>
</div>

<script>
new Chart(document.getElementById('eventMixChart'), {
    type: 'bar',
    data: {
        labels: <?= $eventLabels ?>,
        datasets: [{ label: 'Events', data: <?= $eventValues ?>, borderWidth: 1 }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});

new Chart(document.getElementById('sessionDepthChart'), {
    type: 'bar',
    data: {
        labels: <?= $sessionBucketLabels ?>,
        datasets: [{ label: 'Sessions', data: <?= $sessionBucketValues ?>, borderWidth: 1 }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});

new Chart(document.getElementById('topBehaviorPagesChart'), {
    type: 'bar',
    data: {
        labels: <?= $topPageLabels ?>,
        datasets: [{ label: 'Behavior Events', data: <?= $topPageValues ?>, borderWidth: 1 }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});

(function () {
    const form = document.getElementById('behavior-filter-form');
    const start = document.getElementById('behavior-start-date');
    const end = document.getElementById('behavior-end-date');

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
