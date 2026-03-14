<?php
require_once __DIR__ . '/middleware/role_check.php';
require_role(['super_admin', 'analyst', 'viewer']);
require_once __DIR__ . '/models/db.php';
require_once __DIR__ . '/models/report_model.php';
require_once __DIR__ . '/models/chart_image_helper.php';
require_once __DIR__ . '/models/date_filter.php';
require_once __DIR__ . '/models/insight_engine.php';

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

$previousRange = getPreviousDateRange($range['start_date'], $range['end_date']);
$previousData = getPerformanceReportData($pdo, $previousRange['start_date'], $previousRange['end_date']);

$prevAverageLoad = 0;
if (!empty($previousData['avg_load_by_day'])) {
    $prevValues = array_filter($previousData['avg_load_by_day'], fn($v) => (float)$v > 0);
    if ($prevValues) {
        $prevAverageLoad = round(array_sum($prevValues) / count($prevValues), 2);
    }
}

$performanceInsight = generatePerformanceInsight($averageLoad, $prevAverageLoad);

$status = 'Healthy';
$statusNote = 'Page load time is within healthy limits.';

if ($averageLoad > 1000) {
    $status = 'Needs Attention';
    $statusNote = 'Load times exceed one second and could harm user experience.';
} elseif ($averageLoad >= 600) {
    $status = 'Watch';
    $statusNote = 'Performance is acceptable but slower pages should be monitored.';
}

$chartImageDataUri = null;

try {
    $chartImageUrl = generateBarChartPng(
        array_keys($data['load_buckets']),
        array_values($data['load_buckets']),
        'performance_loads',
        'Loads'
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
    <title>Performance Snapshot PDF</title>
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

<h1>Performance Snapshot</h1>
<p class="meta">
    Generated on <?php echo htmlspecialchars(date('Y-m-d H:i:s'), ENT_QUOTES, 'UTF-8'); ?><br>
    Date Range: <?php echo htmlspecialchars($range['label'], ENT_QUOTES, 'UTF-8'); ?>
</p>

<div class="summary-box">
    <h2>Summary</h2>
    <p><strong>Status:</strong> <?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?></p>
    <p><strong>Status Note:</strong> <?php echo htmlspecialchars($statusNote, ENT_QUOTES, 'UTF-8'); ?></p>
    <p><strong>Average Load:</strong> <?php echo htmlspecialchars((string)$averageLoad, ENT_QUOTES, 'UTF-8'); ?> ms</p>
    <p><strong>Slowest Page:</strong> <?php echo htmlspecialchars((string)$slowestPage, ENT_QUOTES, 'UTF-8'); ?></p>
</div>

<h2>Load Time Distribution</h2>
<div class="chart-box">
    <?php if ($chartImageDataUri !== null): ?>
        <img src="<?php echo $chartImageDataUri; ?>" alt="Performance Chart">
    <?php else: ?>
        <p>Chart image could not be generated.</p>
    <?php endif; ?>
</div>

<h2>Insight</h2>
<p>
    This performance snapshot summarizes load-time distribution across the selected date range and highlights the page with the slowest observed average load time.
</p>

<h2>System Insight</h2>
<p><?php echo htmlspecialchars($performanceInsight ?? 'No automated insight available for this period.', ENT_QUOTES, 'UTF-8'); ?></p>

<?php $pdfComment = trim($_GET['analyst_comment'] ?? ''); ?>
<?php if ($pdfComment !== ''): ?>
    <h2>Analyst Comment</h2>
    <p><?php echo nl2br(htmlspecialchars($pdfComment, ENT_QUOTES, 'UTF-8')); ?></p>
<?php endif; ?>

</body>
</html>
