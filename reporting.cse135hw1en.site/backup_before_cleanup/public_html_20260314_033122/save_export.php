<?php


require_once __DIR__ . '/middleware/role_check.php';
require_role(['super_admin', 'analyst']);
require_once __DIR__ . '/models/db.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$allowedReports = [
    'traffic' => 'saved_traffic_pdf.php',
    'performance' => 'saved_performance_pdf.php',
    'behavior' => 'saved_behavior_pdf.php',
    'errors' => 'saved_errors_pdf.php',
];

$report = $_GET['report'] ?? '';
if (!isset($allowedReports[$report])) {
    http_response_code(400);
    die('Invalid report type.');
}

$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-29 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$analystComment = trim($_POST['analyst_comment'] ?? $_GET['analyst_comment'] ?? '');

$_GET['start_date'] = $startDate;
$_GET['end_date'] = $endDate;
$_GET['analyst_comment'] = $analystComment;

ob_start();
require __DIR__ . '/' . $allowedReports[$report];
$html = ob_get_clean();

$options = new Options();
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$reportsDir = __DIR__ . '/saved_report_files';
if (!is_dir($reportsDir)) {
    mkdir($reportsDir, 0775, true);
}

$fileName = $report . '_' . date('Ymd_His') . '.pdf';
$filePath = $reportsDir . '/' . $fileName;
file_put_contents($filePath, $dompdf->output());

$publicPath = '/saved_report_files/' . $fileName;

$stmt = $pdo->prepare("
    INSERT INTO saved_reports
        (report_type, start_date, end_date, generated_by, pdf_path, analyst_comment, created_at)
    VALUES
        (?, ?, ?, ?, ?, ?, NOW())
");

$stmt->execute([
    $report,
    $startDate,
    $endDate,
    $_SESSION['username'] ?? 'unknown',
    $publicPath,
    $analystComment
]);

header('Location: /saved_reports.php');
exit();
?>
