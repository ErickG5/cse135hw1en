<?php
require_once __DIR__ . '/middleware/role_check.php';
require_role(['super_admin', 'analyst']);
require_once __DIR__ . '/models/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method Not Allowed');
}

$reportId = isset($_POST['report_id']) ? (int)$_POST['report_id'] : 0;
$comment = trim($_POST['analyst_comment'] ?? '');

if ($reportId <= 0) {
    http_response_code(400);
    die('Invalid report id.');
}

$stmt = $pdo->prepare("
    SELECT id, report_type
    FROM saved_reports
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$reportId]);
$report = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$report) {
    http_response_code(404);
    die('Saved report not found.');
}

$role = $_SESSION['role'] ?? '';
$allowed = $_SESSION['allowed_sections'] ?? '';

$canManage = false;

if ($role === 'super_admin') {
    $canManage = true;
} elseif ($role === 'analyst') {
    if ($allowed === 'all') {
        $canManage = true;
    } else {
        $sections = array_map('trim', explode(',', $allowed));
        $canManage = in_array($report['report_type'], $sections, true);
    }
}

if (!$canManage) {
    http_response_code(403);
    die('You are not allowed to update comments for this report type.');
}

$updateStmt = $pdo->prepare("
    UPDATE saved_reports
    SET analyst_comment = ?
    WHERE id = ?
");
$updateStmt->execute([$comment, $reportId]);

header('Location: /saved_reports.php');
exit();
?>
