<?php
require_once __DIR__ . '/middleware/role_check.php';
require_role(['super_admin', 'analyst', 'viewer']);
require_once __DIR__ . '/models/db.php';
require_once __DIR__ . '/models/report_model.php';
require_once __DIR__ . '/models/chart_image_helper.php';
require_once __DIR__ . '/models/date_filter.php';
require_once __DIR__ . '/models/insight_engine.php';

$range = getReportDateRange();
$data = getErrorReportData($pdo, $range['start_date'], $range['end_date']);

$totalErrors = array_sum($data['errors_by_day']);
$topErrorPage = $data['top_error_pages'][0]['page'] ?? 'N/A';

$previousRange = getPreviousDateRange($range['start_date'], $range['end_date']);
$previousData = getErrorReportData($pdo, $previousRange['start_date'], $previousRange['end_date']);
$prevErrors = array_sum($previousData['errors_by_day']);

$errorInsight = generateErrorInsight($totalErrors, $prevErrors);

$status = 'Healthy';
$statusNote = 'No significant error volume detected.';

if ($totalErrors > 20) {
    $status = 'Needs Attention';
    $statusNote = 'Error volume is high and should be investigated.';
} elseif ($totalErrors > 0) {
    $status = 'Watch';
    $statusNote = 'Some errors detected but not at critical levels.';
}

$chartImageDataUri = null;

try {
    $chartImageUrl = generateLineChartPng(
        array_keys($data['errors_by_day']),
        array_values($data['errors_by_day']),
        'errors_trend',
        'Errors'
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
    <title>Error Snapshot PDF</title>
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

<h1>Error Snapshot</h1>
<p class="meta">
    Generated on <?php echo htmlspecialchars(date('Y-m-d H:i:s'), ENT_QUOTES, 'UTF-8'); ?><br>
    Date Range: <?php echo htmlspecialchars($range['label'], ENT_QUOTES, 'UTF-8'); ?>
</p>

<div class="summary-box">
    <h2>Summary</h2>
    <p><strong>Status:</strong> <?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?></p>
    <p><strong>Status Note:</strong> <?php echo htmlspecialchars($statusNote, ENT_QUOTES, 'UTF-8'); ?></p>
    <p><strong>Total Errors:</strong> <?php echo htmlspecialchars((string)$totalErrors, ENT_QUOTES, 'UTF-8'); ?></p>
    <p><strong>Top Error Page:</strong> <?php echo htmlspecialchars((string)$topErrorPage, ENT_QUOTES, 'UTF-8'); ?></p>
</div>

<h2>Errors per Day</h2>
<div class="chart-box">
    <?php if ($chartImageDataUri !== null): ?>
        <img src="<?php echo $chartImageDataUri; ?>" alt="Errors Chart">
    <?php else: ?>
        <p>Chart image could not be generated.</p>
    <?php endif; ?>
</div>

<h2>Insight</h2>
<p>
    This error snapshot summarizes client-side error activity across the selected date range and highlights the page most affected by failures.
</p>

<h2>System Insight</h2>
<p><?php echo htmlspecialchars($errorInsight ?? 'No automated insight available for this period.', ENT_QUOTES, 'UTF-8'); ?></p>

<?php $pdfComment = trim($_GET['analyst_comment'] ?? ''); ?>
<?php if ($pdfComment !== ''): ?>
    <h2>Analyst Comment</h2>
    <p><?php echo nl2br(htmlspecialchars($pdfComment, ENT_QUOTES, 'UTF-8')); ?></p>
<?php endif; ?>

</body>
</html>
