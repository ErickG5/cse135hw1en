<?php
$role = $_SESSION['role'] ?? '';
$allowedSectionsRaw = $_SESSION['allowed_sections'] ?? '';

$allowedSections = array_filter(array_map('trim', explode(',', $allowedSectionsRaw)));

function canViewSection(string $section, string $role, array $allowedSections): bool
{
    if ($role === 'super_admin') {
        return true;
    }

    if ($role !== 'analyst') {
        return false;
    }

    if (in_array('all', $allowedSections, true)) {
        return true;
    }

    return in_array($section, $allowedSections, true);
}

$current = basename($_SERVER['PHP_SELF']);
?>

<nav class="sidebar">

    <div class="sidebar-label">Main</div>

    <a href="dashboard.php" class="<?php echo $current === 'dashboard.php' ? 'active' : ''; ?>">
        Dashboard
    </a>

    <?php if (in_array($role, ['super_admin','analyst'], true)): ?>
        <a href="/charts.php" class="<?php echo $current === 'charts.php' ? 'active' : ''; ?>">
            Overview
        </a>
    <?php endif; ?>

    <div class="sidebar-label">Reports</div>

    <?php if (canViewSection('traffic', $role, $allowedSections)): ?>
        <a href="/traffic.php" class="<?php echo $current === 'traffic.php' ? 'active' : ''; ?>">
            Traffic
        </a>
    <?php endif; ?>

    <?php if (canViewSection('performance', $role, $allowedSections)): ?>
        <a href="/performance.php" class="<?php echo $current === 'performance.php' ? 'active' : ''; ?>">
            Performance
        </a>
    <?php endif; ?>

    <?php if (canViewSection('behavior', $role, $allowedSections)): ?>
        <a href="/behavior.php" class="<?php echo $current === 'behavior.php' ? 'active' : ''; ?>">
            Behavior
        </a>
    <?php endif; ?>

    <?php if (canViewSection('errors', $role, $allowedSections)): ?>
        <a href="/errors.php" class="<?php echo $current === 'errors.php' ? 'active' : ''; ?>">
            Errors
        </a>
    <?php endif; ?>

    <div class="sidebar-divider"></div>

    <?php if (in_array($role, ['super_admin','analyst','viewer'], true)): ?>
        <a href="/saved_reports.php" class="<?php echo $current === 'saved_reports.php' ? 'active' : ''; ?>">
            Saved Reports
        </a>
    <?php endif; ?>
	
<?php if ($role === 'super_admin'): ?>
    <a href="/reports.php" class="<?php echo $current === 'reports.php' ? 'active' : ''; ?>">
        Reports
    </a>
<?php endif; ?>

    <?php if ($role === 'super_admin'): ?>
        <a href="/users.php" class="<?php echo $current === 'users.php' ? 'active' : ''; ?>">
            Manage Users
        </a>
    <?php endif; ?>

</nav>

<main class="main-content">
