<?php
require_once __DIR__ . '/session_bootstrap.php';

if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: /login.php");
    exit();
}
?>
