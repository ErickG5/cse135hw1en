<?php
require_once __DIR__ . '/../middleware/session_bootstrap.php';
require_once __DIR__ . '/../models/db.php';

// --- CSRF Validation ---
if (
    empty($_SESSION['csrf_token']) ||
    empty($_POST['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    unset($_SESSION['csrf_token']);
    header("Location: /login.php?error=csrf");
    exit();
}

// --- Rate Limiting (Brute Force Protection) ---
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt_time'] = time();
}

$max_attempts = 5;
$lockout_duration = 900; // 15 minutes

if ($_SESSION['login_attempts'] >= $max_attempts) {
    if (time() - $_SESSION['last_attempt_time'] < $lockout_duration) {
        header("Location: /login.php?error=locked");
        exit();
    } else {
        $_SESSION['login_attempts'] = 0;
    }
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// --- Look up user from database ---
$stmt = $pdo->prepare("
    SELECT id, username, password_hash, role, allowed_sections, is_active
    FROM users
    WHERE username = ?
    LIMIT 1
");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// --- Handle inactive account ---
if ($user && (int)$user['is_active'] !== 1) {
    header("Location: /login.php?error=inactive");
    exit();
}

// --- Authenticate ---
if ($user && password_verify($password, $user['password_hash'])) {
    $_SESSION['login_attempts'] = 0;

    session_regenerate_id(true);

    $_SESSION['logged_in'] = true;
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['allowed_sections'] = $user['allowed_sections'] ?? '';

    unset($_SESSION['csrf_token']);

    header("Location: /dashboard.php");
    exit();
} else {
    $_SESSION['login_attempts']++;
    $_SESSION['last_attempt_time'] = time();

    header("Location: /login.php?error=invalid");
    exit();
}
?>
