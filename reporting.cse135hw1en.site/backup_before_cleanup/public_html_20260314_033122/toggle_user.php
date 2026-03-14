<?php
require_once __DIR__ . '/middleware/role_check.php';
require_role(['super_admin']);
require_once __DIR__ . '/models/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die("Method Not Allowed");
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id <= 0) {
    http_response_code(400);
    die("Missing user id.");
}

// Prevent self-deactivation
if ($id === (int)($_SESSION['user_id'] ?? 0)) {
    http_response_code(403);
    die("You cannot change the active status of your own account.");
}

$stmt = $pdo->prepare("
    SELECT id, username, role, is_active
    FROM users
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$id]);
$targetUser = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$targetUser) {
    http_response_code(404);
    die("User not found.");
}

// Prevent deactivating the last active super admin
if ($targetUser['role'] === 'super_admin' && (int)$targetUser['is_active'] === 1) {
    $countStmt = $pdo->query("
        SELECT COUNT(*)
        FROM users
        WHERE role = 'super_admin' AND is_active = 1
    ");
    $activeSuperAdmins = (int)$countStmt->fetchColumn();

    if ($activeSuperAdmins <= 1) {
        http_response_code(403);
        die("You cannot deactivate the last active super admin.");
    }
}

$updateStmt = $pdo->prepare("
    UPDATE users
    SET is_active = NOT is_active
    WHERE id = ?
");
$updateStmt->execute([$id]);

header("Location: /users.php");
exit();
?>
