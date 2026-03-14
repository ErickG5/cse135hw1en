<?php
require_once __DIR__ . '/middleware/role_check.php';
require_role(['super_admin', 'analyst', 'viewer']);
?>
<?php include __DIR__ . '/views/header.php'; ?>
<?php include __DIR__ . '/views/navbar.php'; ?>

<div class="page-heading">Analytics Dashboard</div>
<div class="page-subheading">Here's an overview of your account</div>

<!-- Welcome banner — same box style as login form-box -->
<div class="box welcome-box">
    <div class="welcome-pip"></div>
    <div>
        <h2>Welcome back, <?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?></h2>
        <p>Use the sidebar to navigate between report sections.</p>
    </div>
</div>

<!-- Info cards -->
<div class="card-grid">

    <div class="box card-box">
        <div class="card-label">Logged in as</div>
        <div class="card-value">
            <?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?>
        </div>
    </div>

    <div class="box card-box">
        <div class="card-label">Role</div>
        <div class="card-value">
            <span class="role-pill role-<?php echo htmlspecialchars($_SESSION['role'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo htmlspecialchars($_SESSION['role'], ENT_QUOTES, 'UTF-8'); ?>
            </span>
        </div>
    </div>

    <div class="box card-box">
        <div class="card-label">Access level</div>
        <div class="card-value" style="font-size:0.9rem; color:var(--muted); font-family:'DM Sans',sans-serif; font-weight:400;">
            <?php if ($_SESSION['role'] === 'super_admin'): ?>
                Full access
            <?php elseif ($_SESSION['role'] === 'analyst'): ?>
                Assigned sections
            <?php else: ?>
                Saved reports only
            <?php endif; ?>
        </div>
    </div>

</div>

<div class="section-divider"><span>CSE 135 Analytics Platform</span></div>

<?php include __DIR__ . '/views/footer.php'; ?>