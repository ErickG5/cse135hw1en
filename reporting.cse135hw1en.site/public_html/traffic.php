<?php
require_once __DIR__ . '/middleware/role_check.php';
require_role(['super_admin', 'analyst']);
require_section('traffic');
require_once __DIR__ . '/models/db.php';
require_once __DIR__ . '/models/report_model.php';
require_once __DIR__ . '/models/date_filter.php';



$range = getReportDateRange();
$data = getTrafficReportData($pdo, $range['start_date'], $range['end_date']);
$kpis = getTrafficKpis($pdo, $range['start_date'], $range['end_date']);

$previousRange = getPreviousDateRange($range['start_date'], $range['end_date']);
$previousKpis = getTrafficKpis($pdo, $previousRange['start_date'], $previousRange['end_date']);

$sessionsDelta = formatDelta($kpis['total_sessions'], $previousKpis['total_sessions']);
$eventsDelta = formatDelta($kpis['total_events'], $previousKpis['total_events']);
$pagesDelta = formatDelta($kpis['unique_pages'], $previousKpis['unique_pages']);

$topBrowser = array_keys($data['browser_counts'], max($data['browser_counts']))[0] ?? 'N/A';
$topPage = $data['top_pages'][0]['page'] ?? 'N/A';

$sessionLabels = json_encode(array_keys($data['sessions_by_day']));
$sessionValues = json_encode(array_values($data['sessions_by_day']));
$browserLabels = json_encode(array_keys($data['browser_counts']));
$browserValues = json_encode(array_values($data['browser_counts']));
$topPagesLabels = json_encode(array_map(fn($r) => $r['page'], $data['top_pages']));
$topPagesValues = json_encode(array_map(fn($r) => (int)$r['session_count'], $data['top_pages']));

include __DIR__ . '/views/header.php';
include __DIR__ . '/views/navbar.php';
include __DIR__ . '/views/report_styles.php';
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>

<div class="report-page">
    <h1>Traffic Report</h1>
    <p class="subtitle">Traffic analytics for the selected date range.</p>

    <div class="quick-range-bar">
        <a href="?range=7"><button type="button" class="<?php echo activeRangeClass($range['range'], '7'); ?>">Last 7 Days</button></a>
        <a href="?range=14"><button type="button" class="<?php echo activeRangeClass($range['range'], '14'); ?>">Last 14 Days</button></a>
        <a href="?range=30"><button type="button" class="<?php echo activeRangeClass($range['range'], '30'); ?>">Last 30 Days</button></a>
        <a href="?"><button type="button" class="<?php echo $range['range'] === '' ? 'quick-range-btn active' : 'quick-range-btn'; ?>">Custom</button></a>
    </div>

    <form method="GET" class="filter-form" id="traffic-filter-form">
        <label>Start Date:</label>
        <input type="date" name="start_date" id="traffic-start-date" value="<?php echo htmlspecialchars($range['start_date'], ENT_QUOTES, 'UTF-8'); ?>">

        <label>End Date:</label>
        <input type="date" name="end_date" id="traffic-end-date" value="<?php echo htmlspecialchars($range['end_date'], ENT_QUOTES, 'UTF-8'); ?>">

        <button type="submit">Apply</button>
    </form>

    <p><strong>Date Range:</strong> <?php echo htmlspecialchars($range['label'], ENT_QUOTES, 'UTF-8'); ?></p>
    <div style="margin-bottom: 10px; margin-top: 10px;">
        <a href="/saved_traffic.php?start_date=<?php echo urlencode($range['start_date']); ?>&end_date=<?php echo urlencode($range['end_date']); ?>" class="prepare-snapshot-btn">
            Prepare Report Snapshot
        </a>
    </div>


    <div class="summary-grid">
        <div class="summary-card">
            <h3>Total Sessions</h3>
            <div class="metric"><?php echo htmlspecialchars((string)$kpis['total_sessions'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="kpi-delta <?php echo htmlspecialchars($sessionsDelta['class'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo htmlspecialchars($sessionsDelta['text'], ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <div class="kpi-subtext">vs previous period</div>
        </div>

        <div class="summary-card">
            <h3>Total Events</h3>
            <div class="metric"><?php echo htmlspecialchars((string)$kpis['total_events'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="kpi-delta <?php echo htmlspecialchars($eventsDelta['class'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo htmlspecialchars($eventsDelta['text'], ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <div class="kpi-subtext">vs previous period</div>
        </div>

        <div class="summary-card">
            <h3>Unique Pages</h3>
            <div class="metric"><?php echo htmlspecialchars((string)$kpis['unique_pages'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="kpi-delta <?php echo htmlspecialchars($pagesDelta['class'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo htmlspecialchars($pagesDelta['text'], ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <div class="kpi-subtext">vs previous period</div>
        </div>
    </div>

    <div class="summary-grid">
        <div class="summary-card">
            <h3>Top Browser</h3>
            <div class="metric"><?php echo htmlspecialchars((string)$topBrowser, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>

        <div class="summary-card">
            <h3>Top Page</h3>
            <div class="metric"><?php echo htmlspecialchars((string)$topPage, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>

        <div class="summary-card">
            <h3>Report Focus</h3>
            <div class="metric">Traffic</div>
            <div class="kpi-subtext">sessions, browsers, top pages</div>
        </div>
    </div>

    <div class="chart-grid">
        <div class="chart-card">
            <h2>Sessions per Day</h2>
            <div class="chart-wrap"><canvas id="sessionsChart"></canvas></div>
        </div>

        <div class="chart-card">
            <h2>Browser Distribution</h2>
            <div class="chart-wrap"><canvas id="browserChart"></canvas></div>
        </div>

        <div class="chart-card wide">
            <h2>Top Pages by Sessions</h2>
            <div class="chart-wrap"><canvas id="topPagesChart"></canvas></div>
        </div>
    </div>

    <div class="table-card">
        <h2>Top Pages Table</h2>
        <table>
            <thead>
                <tr>
                    <th>Page</th>
                    <th>Session Count</th>
                    <th>Event Count</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data['top_pages'] as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string)$row['page'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$row['session_count'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$row['event_count'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="notes-card">
        <h2>Key Insight</h2>
        <p>Traffic is best understood through sessions trend, browser usage, and top-page concentration. The current top page is <?php echo htmlspecialchars($topPage, ENT_QUOTES, 'UTF-8'); ?> and the most common browser is <?php echo htmlspecialchars($topBrowser, ENT_QUOTES, 'UTF-8'); ?>.</p>
    </div>
</div>

<script>
new Chart(document.getElementById('sessionsChart'), {
    type: 'line',
    data: {
        labels: <?= $sessionLabels ?>,
        datasets: [{ label: 'Sessions', data: <?= $sessionValues ?>, borderWidth: 2, tension: 0.2 }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});

new Chart(document.getElementById('browserChart'), {
    type: 'pie',
    data: {
        labels: <?= $browserLabels ?>,
        datasets: [{ data: <?= $browserValues ?>, borderWidth: 1 }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});

new Chart(document.getElementById('topPagesChart'), {
    type: 'bar',
    data: {
        labels: <?= $topPagesLabels ?>,
        datasets: [{ label: 'Sessions', data: <?= $topPagesValues ?>, borderWidth: 1 }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});

(function () {
    const form = document.getElementById('traffic-filter-form');
    const start = document.getElementById('traffic-start-date');
    const end = document.getElementById('traffic-end-date');

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
