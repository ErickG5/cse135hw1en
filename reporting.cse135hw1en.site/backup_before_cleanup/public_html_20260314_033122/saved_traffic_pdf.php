<?php
require_once __DIR__ . '/middleware/role_check.php';
require_role(['super_admin', 'analyst', 'viewer']);
require_once __DIR__ . '/models/db.php';
require_once __DIR__ . '/models/report_model.php';
require_once __DIR__ . '/models/chart_image_helper.php';
require_once __DIR__ . '/models/date_filter.php';
require_once __DIR__ . '/models/insight_engine.php';

$range = getReportDateRange();
$data = getTrafficReportData($pdo, $range['start_date'], $range['end_date']);

$totalSessions = array_sum($data['sessions_by_day']);
$topPage = $data['top_pages'][0]['page'] ?? 'N/A';

$previousRange = getPreviousDateRange($range['start_date'], $range['end_date']);
$previousData = getTrafficReportData($pdo, $previousRange['start_date'], $previousRange['end_date']);
$prevSessions = array_sum($previousData['sessions_by_day']);

$trafficInsight = generateTrafficInsight($totalSessions, $prevSessions);

$status = 'Healthy';
$statusNote = 'Traffic volume is stable and site activity appears normal.';

if ($totalSessions < 10) {
    $status = 'Needs Attention';
    $statusNote = 'Traffic volume is extremely low and may indicate limited activity or tracking issues.';
} elseif ($totalSessions < 50) {
    $status = 'Watch';
    $statusNote = 'Traffic exists but is relatively low and should be monitored.';
}

$chartImageDataUri = null;

try {
    $chartImageUrl = generateLineChartPng(
        array_keys($data['sessions_by_day']),
        array_values($data['sessions_by_day']),
        'traffic_sessions',
        'Sessions'
    );

    $chartImagePath = __DIR__ . $chartImageUrl;

    if (file_exists($chartImagePath)) {
        $imageContents = file_get_contents($chartImagePath);
        if ($imageContents !== false) {
            $chartImageDataUri = 'data:image/png;base64,' . base64_encode($imageContents);
        }
    }
} catch (Throwable $e) {
    $chartImageDataUri = null;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Traffic Snapshot PDF</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #222;
            margin: 32px;
        }
        h1 { margin-bottom: 6px; }
        h2 { margin-top: 24px; margin-bottom: 10px; font-size: 16px; }
        .meta {
            color: #555;
            margin-bottom: 20px;
            line-height: 1.5;
        }
        .summary-box {
            border: 1px solid #ccc;
            padding: 12px;
            margin-bottom: 20px;
            background: #f8f8f8;
        }
        .summary-box p {
            margin: 4px 0;
        }
        .chart-box {
            border: 1px solid #ccc;
            padding: 12px;
            background: #fff;
            margin-bottom: 20px;
            text-align: center;
        }
        .chart-box img {
            width: 100%;
            max-width: 700px;
            height: auto;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 8px;
        }
        th, td {
            border: 1px solid #bbb;
            padding: 8px;
            text-align: left;
            vertical-align: top;
        }
        th { background: #eee; }
    </style>
</head>
<body>

<h1>Traffic Snapshot</h1>
<p class="meta">
    Generated on <?php echo htmlspecialchars(date('Y-m-d H:i:s'), ENT_QUOTES, 'UTF-8'); ?><br>
    Date Range: <?php echo htmlspecialchars($range['label'], ENT_QUOTES, 'UTF-8'); ?>
</p>

<div class="summary-box">
    <h2>Summary</h2>
    <p><strong>Status:</strong> <?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?></p>
    <p><strong>Status Note:</strong> <?php echo htmlspecialchars($statusNote, ENT_QUOTES, 'UTF-8'); ?></p>
    <p><strong>Total Sessions:</strong> <?php echo htmlspecialchars((string)$totalSessions, ENT_QUOTES, 'UTF-8'); ?></p>
    <p><strong>Top Page:</strong> <?php echo htmlspecialchars((string)$topPage, ENT_QUOTES, 'UTF-8'); ?></p>
</div>

<h2>Sessions Trend</h2>
<div class="chart-box">
    <?php if ($chartImageDataUri !== null): ?>
        <img src="<?php echo $chartImageDataUri; ?>" alt="Traffic Sessions Chart">
    <?php else: ?>
        <p>Chart image could not be generated.</p>
    <?php endif; ?>
</div>

<h2>Top Pages</h2>
<table>
    <thead>
        <tr>
            <th>Page</th>
            <th>Sessions</th>
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

<h2>Insight</h2>
<p>
    This traffic snapshot summarizes visit activity across the selected time window and highlights the page that received the most session activity.
</p>

<h2>System Insight</h2>
<p><?php echo htmlspecialchars($trafficInsight ?? 'No automated insight available for this period.', ENT_QUOTES, 'UTF-8'); ?></p>

<?php $pdfComment = trim($_GET['analyst_comment'] ?? ''); ?>
<?php if ($pdfComment !== ''): ?>
    <h2>Analyst Comment</h2>
    <p><?php echo nl2br(htmlspecialchars($pdfComment, ENT_QUOTES, 'UTF-8')); ?></p>
<?php endif; ?>

</body>
</html>
