<?php
require_once __DIR__ . '/middleware/role_check.php';
require_role(['super_admin', 'analyst', 'viewer']);
require_once __DIR__ . '/models/db.php';
require_once __DIR__ . '/models/report_model.php';
require_once __DIR__ . '/models/date_filter.php';
require_once __DIR__ . '/models/insight_engine.php';

$range = getReportDateRange();
$data = getTrafficReportData($pdo, $range['start_date'], $range['end_date']);

$totalSessions = array_sum($data['sessions_by_day']);
$topBrowser = array_keys($data['browser_counts'], max($data['browser_counts']))[0] ?? 'N/A';
$topPage = $data['top_pages'][0]['page'] ?? 'N/A';

$previousRange = getPreviousDateRange($range['start_date'], $range['end_date']);
$previousData = getTrafficReportData($pdo, $previousRange['start_date'], $previousRange['end_date']);
$prevSessions = array_sum($previousData['sessions_by_day']);

$trafficInsight = generateTrafficInsight($totalSessions, $prevSessions);

$status = 'Healthy';
$statusClass = 'status-healthy';
$statusNote = 'Traffic volume is stable and site activity appears normal.';

if ($totalSessions < 10) {
    $status = 'Needs Attention';
    $statusClass = 'status-attention';
    $statusNote = 'Traffic volume is extremely low and may indicate limited activity or tracking issues.';
} elseif ($totalSessions < 50) {
    $status = 'Watch';
    $statusClass = 'status-watch';
    $statusNote = 'Traffic exists but is relatively low and should be monitored.';
}

$sessionLabels = json_encode(array_keys($data['sessions_by_day']));
$sessionValues = json_encode(array_values($data['sessions_by_day']));

include __DIR__ . '/views/header.php';
include __DIR__ . '/views/navbar.php';
include __DIR__ . '/views/report_styles.php';
?>


<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>

<div class="report-page">
    <h1>Traffic Snapshot</h1>
    <p class="subtitle">Published traffic snapshot for the selected date range.</p>

    <form method="GET" class="filter-form" id="traffic-filter-form">
        <label>Start Date:</label>
        <input type="date" name="start_date" id="traffic-start-date" value="<?php echo htmlspecialchars($range['start_date'], ENT_QUOTES, 'UTF-8'); ?>">

        <label>End Date:</label>
        <input type="date" name="end_date" id="traffic-end-date" value="<?php echo htmlspecialchars($range['end_date'], ENT_QUOTES, 'UTF-8'); ?>">

        <button type="submit">Apply</button>
    </form>


    <p><strong>Date Range:</strong> <?php echo htmlspecialchars($range['label'], ENT_QUOTES, 'UTF-8'); ?></p>

    <?php if (in_array($_SESSION['role'] ?? '', ['super_admin', 'analyst'], true)): ?>
        <div class="notes-card" style="margin-top: 24px;">
            <h2>Generate Saved Report</h2>
            <form method="POST" action="/save_export.php?report=traffic&start_date=<?php echo urlencode($range['start_date']); ?>&end_date=<?php echo urlencode($range['end_date']); ?>">
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
                    <a href="/export.php?report=traffic&start_date=<?php echo urlencode($range['start_date']); ?>&end_date=<?php echo urlencode($range['end_date']); ?>" 
                    class="prepare-snapshot-btn btn-outline" 
                    target="_blank">
                        Open PDF
                    </a>
                </div>
            </form>
        </div>
    <?php else: ?>
        <div style="margin-top: 24px;">
            <a href="/export.php?report=traffic&start_date=<?php echo urlencode($range['start_date']); ?>&end_date=<?php echo urlencode($range['end_date']); ?>" 
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
            <h3>Total Sessions</h3>
            <div class="metric"><?php echo htmlspecialchars((string)$totalSessions, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>

        <div class="summary-card">
            <h3>Top Page</h3>
            <div class="metric"><?php echo htmlspecialchars((string)$topPage, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
    </div>

    <div class="chart-grid">
        <div class="chart-card wide">
            <h2>Sessions per Day</h2>
            <div class="chart-wrap"><canvas id="trafficSessionsChart"></canvas></div>
        </div>
    </div>

    <div class="table-card">
        <h2>Top Pages</h2>
        <table>
            <thead>
                <tr>
                    <th>Page</th>
                    <th>Sessions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data['top_pages'] as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string)$row['page'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$row['session_count'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="notes-card">
        <h2>Insight</h2>
        <p>Traffic is primarily driven by visits to <?php echo htmlspecialchars($topPage, ENT_QUOTES, 'UTF-8'); ?> with <?php echo htmlspecialchars($topBrowser, ENT_QUOTES, 'UTF-8'); ?> being the most common browser.</p>
    </div>
	<div class="notes-card">
    <h2>System Insight</h2>
    <p>
<?php echo htmlspecialchars($trafficInsight ?? 'No automated insight available for this period.', ENT_QUOTES, 'UTF-8'); ?>
</p>

</div>


</div>

<script>
new Chart(document.getElementById('trafficSessionsChart'), {
    type: 'line',
    data: {
        labels: <?= $sessionLabels ?>,
        datasets: [{ label: 'Sessions', data: <?= $sessionValues ?>, borderWidth: 2, tension: 0.2 }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});
</script>

<?php include __DIR__ . '/views/footer.php'; ?>
