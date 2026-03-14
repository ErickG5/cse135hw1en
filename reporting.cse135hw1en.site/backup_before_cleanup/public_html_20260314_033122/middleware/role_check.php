<?php
require_once __DIR__ . '/auth_check.php';

function require_role(array $roles): void
{
    $currentRole = $_SESSION['role'] ?? '';

    if (!in_array($currentRole, $roles, true)) {
        http_response_code(403);
        require __DIR__ . '/../errors/403.php';
        exit();
    }
}

function require_section(string $section): void
{
    $currentRole = $_SESSION['role'] ?? '';

    if ($currentRole === 'super_admin') {
        return;
    }

    if ($currentRole !== 'analyst') {
        http_response_code(403);
        require __DIR__ . '/../errors/403.php';
        exit();
    }

    $allowedSections = $_SESSION['allowed_sections'] ?? '';

    if ($allowedSections === 'all') {
        return;
    }

    $sections = array_map('trim', explode(',', $allowedSections));

    if (!in_array($section, $sections, true)) {
        http_response_code(403);
        require __DIR__ . '/../errors/403.php';
        exit();
    }
}
?>
