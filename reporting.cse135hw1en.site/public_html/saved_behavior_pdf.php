<?php
require_once __DIR__ . '/middleware/role_check.php';
require_role(['super_admin', 'analyst', 'viewer']);
require_once __DIR__ . '/models/db.php';
require_once __DIR__ . '/models/report_model.php';
require_once __DIR__ . '/models/chart_image_helper.php';
require_once __DIR__ . '/models/date_filter.php';
require_once __DIR__ . '/models/insight_engine.php';

$range = getReportDateRange();
$data = getBehaviorReportData($pdo, $range['start_date'], $range['end_date']);

$totalEvents = array_sum($data['event_counts']);
$topPage = $data['top_pages'][0]['page'] ?? 'N/A';

$previousRange = getPreviousDateRange($range['start_date'], $range['end_date']);
$previousData = getBehaviorReportData($pdo, $previousRange['start_date'], $previousRange['end_date']);
$prevEvents = array_sum($previousData['event_counts']);

$behaviorInsight = generateBehaviorInsight($totalEvents, $prevEvents);

$status = 'Healthy';
$statusNote = 'User interaction indicates healthy engagement.';

if ($totalEvents < 20) {
    $status = 'Watch';
    $statusNote = 'Engagement levels are relatively low.';
}

$chartImageDataUri = null;

try {
    $chartImageUrl = generateBarChartPng(
        array_keys($data['event_counts']),
        array_values($data['event_counts']),
        'behavior_events',
        'Events'
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
    <title>Behavior Snapshot PDF</title>
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
    </style>
</head>
<body>

<h1>Behavior Snapshot</h1>
<p class="meta">
    Generated on <?php echo htmlspecialchars(date('Y-m-d H:i:s'), ENT_QUOTES, 'UTF-8'); ?><br>
    Date Range: <?php echo htmlspecialchars($range['label'], ENT_QUOTES, 'UTF-8'); ?>
</p>

<div class="summary-box">
    <h2>Summary</h2>
    <p><strong>Status:</strong> <?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?></p>
    <p><strong>Status Note:</strong> <?php echo htmlspecialchars($statusNote, ENT_QUOTES, 'UTF-8'); ?></p>
    <p><strong>Total Events:</strong> <?php echo htmlspecialchars((string)$totalEvents, ENT_QUOTES, 'UTF-8'); ?></p>
    <p><strong>Top Page:</strong> <?php echo htmlspecialchars((string)$topPage, ENT_QUOTES, 'UTF-8'); ?></p>
</div>

<h2>Event Activity Mix</h2>
<div class="chart-box">
    <?php if ($chartImageDataUri !== null): ?>
        <img src="<?php echo $chartImageDataUri; ?>" alt="Behavior Chart">
    <?php else: ?>
        <p>Chart image could not be generated.</p>
    <?php endif; ?>
</div>

<h2>Insight</h2>
<p>
    This behavior snapshot summarizes interaction activity across the selected date range and highlights the page receiving the most engagement.
</p>

<h2>System Insight</h2>
<p><?php echo htmlspecialchars($behaviorInsight ?? 'No automated insight available for this period.', ENT_QUOTES, 'UTF-8'); ?></p>

<?php $pdfComment = trim($_GET['analyst_comment'] ?? ''); ?>
<?php if ($pdfComment !== ''): ?>
    <h2>Analyst Comment</h2>
    <p><?php echo nl2br(htmlspecialchars($pdfComment, ENT_QUOTES, 'UTF-8')); ?></p>
<?php endif; ?>

</body>
</html>
