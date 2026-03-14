<?php
require_once __DIR__ . '/middleware/role_check.php';
require_role(['super_admin']);
require_once __DIR__ . '/models/db.php';

$stmt = $pdo->query("
    SELECT id, username, role, allowed_sections, is_active, created_at
    FROM users
    ORDER BY id ASC
");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/views/header.php';
include __DIR__ . '/views/navbar.php';
?>

<style>
    /* ── Page layout ── */
    .users-page {
        padding: 32px 32px 0;
    }

    .users-page h1 {
        font-family: 'Syne', sans-serif;
        font-size: 1.7rem;
        font-weight: 700;
        letter-spacing: -0.03em;
        color: #e4e8f5;
        margin-bottom: 4px;
    }

    .users-page .subtitle {
        font-size: 0.875rem;
        color: #6b7280;
        margin-bottom: 32px;
    }

    /* ── Section boxes — same as dashboard cards ── */
    .section-box {
        background: #0d1120;
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 16px;
        padding: 28px 32px;
        margin-bottom: 24px;
        box-shadow:
            0 0 0 1px rgba(255,255,255,0.03),
            0 8px 32px rgba(0,0,0,0.5),
            0 2px 8px rgba(0,0,0,0.3);
    }

    .section-box h2 {
        font-family: 'Syne', sans-serif;
        font-size: 0.95rem;
        font-weight: 700;
        color: #e4e8f5;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        margin: 0 0 20px 0;
        padding-bottom: 12px;
        border-bottom: 1px solid rgba(255,255,255,0.07);
    }

    /* ── Create user form ── */
    .create-form {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }

    .form-field {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .form-field.full-width {
        grid-column: 1 / -1;
    }

    .form-field label {
        font-size: 0.72rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #6b7280;
    }

    .form-field input[type="text"],
    .form-field input[type="password"],
    .form-field select {
        background: #111827;
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 10px;
        color: #e4e8f5;
        font-family: 'DM Sans', sans-serif;
        font-size: 0.9rem;
        padding: 10px 14px;
        outline: none;
        transition: border-color 150ms ease, box-shadow 150ms ease;
        color-scheme: dark;
        appearance: none;
    }

    .form-field input:focus,
    .form-field select:focus {
        border-color: #4f7cff;
        box-shadow: 0 0 0 3px rgba(79,124,255,0.15);
    }

    .form-field input::placeholder {
        color: #374151;
    }

    /* Checkbox row */
    .checkbox-field {
        display: flex;
        align-items: center;
        gap: 10px;
        grid-column: 1 / -1;
        margin-top: 4px;
    }

    .checkbox-field input[type="checkbox"] {
        width: 16px;
        height: 16px;
        accent-color: #4f7cff;
        cursor: pointer;
    }

    .checkbox-field span {
        font-size: 0.875rem;
        color: #e4e8f5;
    }

    /* Submit button */
    .btn-submit {
        grid-column: 1 / -1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #4f7cff;
        border: none;
        border-radius: 10px;
        color: #fff;
        font-family: 'DM Sans', sans-serif;
        font-size: 0.9rem;
        font-weight: 500;
        padding: 11px 24px;
        cursor: pointer;
        transition: background 150ms ease, box-shadow 150ms ease, transform 100ms ease;
        margin-top: 8px;
        position: relative;
        overflow: hidden;
    }

    .btn-submit::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, transparent 60%);
        pointer-events: none;
    }

    .btn-submit:hover {
        background: #6b93ff;
        box-shadow: 0 0 24px rgba(79,124,255,0.4);
    }

    .btn-submit:active { transform: translateY(1px); }

    /* ── Users table ── */
    .users-table-wrap {
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.875rem;
    }

    th {
        background: rgba(255,255,255,0.03);
        border-bottom: 1px solid rgba(255,255,255,0.1);
        padding: 10px 14px;
        text-align: left;
        font-size: 0.68rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #6b7280;
        white-space: nowrap;
    }

    td {
        border-bottom: 1px solid rgba(255,255,255,0.06);
        padding: 12px 14px;
        color: #e4e8f5;
        vertical-align: middle;
    }

    tr:last-child td { border-bottom: none; }
    tr:hover td { background: rgba(255,255,255,0.02); }

    /* Role pill */
    .role-pill {
        display: inline-block;
        padding: 2px 10px;
        border-radius: 9999px;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .role-super_admin { background: rgba(79,124,255,0.18);  color: #4f7cff; }
    .role-analyst     { background: rgba(52,211,153,0.15);  color: #34d399; }
    .role-viewer      { background: rgba(107,114,128,0.2);  color: #9ca3af; }

    /* Active badge */
    .badge-active   { color: #34d399; font-weight: 600; }
    .badge-inactive { color: #f87171; font-weight: 600; }

    /* Protected label */
    .protected-label {
        font-size: 0.78rem;
        font-weight: 600;
        color: #6b7280;
        font-style: italic;
    }

    /* Toggle button */
    .btn-toggle {
        background: transparent;
        border: 1px solid rgba(255,255,255,0.12);
        border-radius: 7px;
        color: #e4e8f5;
        font-family: 'DM Sans', sans-serif;
        font-size: 0.78rem;
        font-weight: 500;
        padding: 5px 12px;
        cursor: pointer;
        transition: background 150ms ease, border-color 150ms ease;
    }

    .btn-toggle:hover {
        background: rgba(255,255,255,0.06);
        border-color: rgba(255,255,255,0.22);
    }

    .btn-toggle.deactivate {
        border-color: rgba(248,113,113,0.3);
        color: #f87171;
    }

    .btn-toggle.deactivate:hover {
        background: rgba(248,113,113,0.1);
        border-color: #f87171;
    }

    .btn-toggle.activate {
        border-color: rgba(52,211,153,0.3);
        color: #34d399;
    }

    .btn-toggle.activate:hover {
        background: rgba(52,211,153,0.1);
        border-color: #34d399;
    }

    @media (max-width: 700px) {
        .create-form { grid-template-columns: 1fr; }
        .form-field.full-width,
        .checkbox-field,
        .btn-submit { grid-column: auto; }
    }
</style>

<div class="users-page">

    <h1>User Management</h1>
    <p class="subtitle">Only super admins can access this page.</p>

    <!-- Create User -->
    <div class="section-box">
        <h2>Create User</h2>
        <form method="POST" action="/create_user.php" class="create-form">

            <div class="form-field">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required placeholder="Enter username">
            </div>

            <div class="form-field">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Enter password">
            </div>

            <div class="form-field">
                <label for="role">Role</label>
                <select id="role" name="role" required>
                    <option value="viewer">Viewer</option>
                    <option value="analyst">Analyst</option>
                </select>
            </div>

            <div class="form-field">
                <label for="allowed_sections">Allowed Sections</label>
                <input type="text" id="allowed_sections" name="allowed_sections"
                       placeholder="performance,behavior,traffic,errors">
            </div>

            <div class="checkbox-field">
                <input type="checkbox" id="is_active" name="is_active" checked>
                <span>Active</span>
            </div>

            <button type="submit" class="btn-submit">Create User</button>

        </form>
    </div>

    <!-- Existing Users -->
    <div class="section-box">
        <h2>Existing Users</h2>
        <div class="users-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Allowed Sections</th>
                        <th>Active</th>
                        <th>Created At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td style="color:#6b7280;"><?php echo htmlspecialchars((string)$user['id'], ENT_QUOTES, 'UTF-8'); ?></td>

                            <td><?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?></td>

                            <td>
                                <span class="role-pill role-<?php echo htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>

                            <td style="color:#6b7280; font-size:0.82rem;">
                                <?php echo htmlspecialchars($user['allowed_sections'] ?? '—', ENT_QUOTES, 'UTF-8'); ?>
                            </td>

                            <td>
                                <?php if ((int)$user['is_active'] === 1): ?>
                                    <span class="badge-active">Yes</span>
                                <?php else: ?>
                                    <span class="badge-inactive">No</span>
                                <?php endif; ?>
                            </td>

                            <td style="color:#6b7280; font-size:0.82rem; font-family:'DM Mono',monospace;">
                                <?php echo htmlspecialchars($user['created_at'] ?? '—', ENT_QUOTES, 'UTF-8'); ?>
                            </td>

                            <td>
                                <?php if ((int)$user['id'] === (int)($_SESSION['user_id'] ?? 0)): ?>
                                    <span class="protected-label">Current Super Admin</span>
                                <?php elseif ($user['role'] === 'super_admin'): ?>
                                    <span class="protected-label">Protected</span>
                                <?php else: ?>
                                    <form method="POST" action="/toggle_user.php" style="display:inline;">
                                        <input type="hidden" name="id"
                                               value="<?php echo htmlspecialchars((string)$user['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <button type="submit"
                                            class="btn-toggle <?php echo ((int)$user['is_active'] === 1) ? 'deactivate' : 'activate'; ?>">
                                            <?php echo ((int)$user['is_active'] === 1) ? 'Deactivate' : 'Activate'; ?>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php include __DIR__ . '/views/footer.php'; ?>