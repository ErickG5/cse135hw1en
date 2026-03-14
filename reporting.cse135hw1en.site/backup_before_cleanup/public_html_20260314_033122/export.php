<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('memory_limit', '256M');

require_once __DIR__ . '/middleware/role_check.php';
require_role(['super_admin', 'analyst', 'viewer']);

require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$report = $_GET['report'] ?? '';

$allowedReports = [
    'traffic' => 'saved_traffic_pdf.php',
    'performance' => 'saved_performance_pdf.php',
    'behavior' => 'saved_behavior_pdf.php',
    'errors' => 'saved_errors_pdf.php',
];

if (!array_key_exists($report, $allowedReports)) {
    http_response_code(404);
    require __DIR__ . '/errors/404.php';
    exit();
}

$reportFile = __DIR__ . '/' . $allowedReports[$report];

if (!file_exists($reportFile)) {
    http_response_code(404);
    require __DIR__ . '/errors/404.php';
    exit();
}

ob_start();
include $reportFile;
$html = ob_get_clean();

$options = new Options();
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = $report . '_report.pdf';
$pdfOutput = $dompdf->output();

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $filename . '"');
header('Content-Length: ' . strlen($pdfOutput));

echo $pdfOutput;
exit();
?>
