<?php
require_once __DIR__ . '/middleware/role_check.php';
require_role(['super_admin']);
require_once __DIR__ . '/models/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die("Method Not Allowed");
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? 'viewer';
$allowed_sections = trim($_POST['allowed_sections'] ?? '');
$is_active = isset($_POST['is_active']) ? 1 : 0;

$valid_roles = ['analyst', 'viewer']; // super_admin intentionally excluded

if ($username === '' || $password === '') {
    http_response_code(400);
    die("Username and password are required.");
}

if (!in_array($role, $valid_roles, true)) {
    http_response_code(400);
    die("Invalid role. New users may only be analyst or viewer.");
}

$password_hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("
    INSERT INTO users (username, password_hash, role, allowed_sections, is_active)
    VALUES (?, ?, ?, ?, ?)
");

try {
    $stmt->execute([$username, $password_hash, $role, $allowed_sections, $is_active]);
    header("Location: /users.php");
    exit();
} catch (PDOException $e) {
    http_response_code(500);
    die("Could not create user. The username may already exist.");
}
?>
