<?php
require_once __DIR__ . '/middleware/role_check.php';
require_role(['super_admin', 'analyst', 'viewer']);
require_once __DIR__ . '/models/db.php';

function user_can_manage_saved_report(string $reportType): bool
{
    $role = $_SESSION['role'] ?? '';

    if ($role === 'super_admin') {
        return true;
    }

    if ($role !== 'analyst') {
        return false;
    }

    $allowed = $_SESSION['allowed_sections'] ?? '';
    if ($allowed === 'all') {
        return true;
    }

    $sections = array_map('trim', explode(',', $allowed));
    return in_array($reportType, $sections, true);
}

$stmt = $pdo->query("
    SELECT id, report_type, start_date, end_date, generated_by, pdf_path, analyst_comment, created_at
    FROM saved_reports
    ORDER BY created_at DESC
");
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/views/header.php';
include __DIR__ . '/views/navbar.php';
include __DIR__ . '/views/report_styles.php';
?>

<style>
    /* ── Saved reports extra styles ── */

    .report-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        margin-bottom: 16px;
    }

    .report-meta-item {
        display: flex;
        flex-direction: column;
        gap: 3px;
    }

    .report-meta-item .meta-label {
        font-size: 0.68rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #6b7280;
    }

    .report-meta-item .meta-value {
        font-size: 0.875rem;
        color: #e4e8f5;
        font-family: 'DM Mono', monospace;
    }

    /* PDF link */
    .pdf-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 0.82rem;
        font-weight: 500;
        color: #4f7cff;
        text-decoration: none;
        border: 1px solid rgba(79,124,255,0.25);
        border-radius: 7px;
        padding: 5px 12px;
        transition: background 150ms ease, border-color 150ms ease;
        margin-bottom: 16px;
        width: fit-content;
    }

    .pdf-link:hover {
        background: rgba(79,124,255,0.1);
        border-color: #4f7cff;
        color: #4f7cff;
    }

    .pdf-missing {
        font-size: 0.82rem;
        color: #6b7280;
        font-style: italic;
        margin-bottom: 16px;
    }

    /* ── Edit comment section ── */
    .manage-section {
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid rgba(255,255,255,0.07);
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .manage-section .field-label {
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #6b7280;
        margin-bottom: 8px;
        display: block;
    }

    .manage-section textarea {
        width: 100%;
        background: #111827;
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 10px;
        color: #e4e8f5;
        font-family: 'DM Sans', sans-serif;
        font-size: 0.875rem;
        padding: 12px 14px;
        outline: none;
        resize: vertical;
        transition: border-color 150ms ease, box-shadow 150ms ease;
        line-height: 1.6;
    }

    .manage-section textarea:focus {
        border-color: #4f7cff;
        box-shadow: 0 0 0 3px rgba(79,124,255,0.15);
    }

    /* Button row */
    .manage-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        align-items: center;
    }

    .btn-update {
        background: #4f7cff;
        border: none;
        border-radius: 9px;
        color: #fff;
        font-family: 'DM Sans', sans-serif;
        font-size: 0.85rem;
        font-weight: 500;
        padding: 9px 20px;
        cursor: pointer;
        position: relative;
        overflow: hidden;
        transition: background 150ms ease, box-shadow 150ms ease, transform 100ms ease;
    }

    .btn-update::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, transparent 60%);
        pointer-events: none;
    }

    .btn-update:hover {
        background: #6b93ff;
        box-shadow: 0 0 20px rgba(79,124,255,0.4);
    }

    .btn-update:active { transform: translateY(1px); }

    .btn-delete {
        background: transparent;
        border: 1px solid rgba(248,113,113,0.3);
        border-radius: 9px;
        color: #f87171;
        font-family: 'DM Sans', sans-serif;
        font-size: 0.85rem;
        font-weight: 500;
        padding: 9px 20px;
        cursor: pointer;
        transition: background 150ms ease, border-color 150ms ease, transform 100ms ease;
    }

    .btn-delete:hover {
        background: rgba(248,113,113,0.1);
        border-color: #f87171;
    }

    .btn-delete:active { transform: translateY(1px); }
</style>

<div class="report-page">

    <h1>Saved Reports</h1>
    <p class="subtitle">Previously generated report snapshots.</p>

    <?php if (empty($reports)): ?>
        <div class="notes-card">
            <p>No saved reports yet.</p>
        </div>
    <?php else: ?>
        <?php foreach ($reports as $report): ?>
            <?php $canManage = user_can_manage_saved_report((string)$report['report_type']); ?>

            <div class="notes-card">
                <h2><?php echo htmlspecialchars(ucfirst($report['report_type']) . ' Snapshot', ENT_QUOTES, 'UTF-8'); ?></h2>

                <!-- Meta row -->
                <div class="report-meta">
                    <div class="report-meta-item">
                        <span class="meta-label">Date Range</span>
                        <span class="meta-value">
                            <?php echo htmlspecialchars($report['start_date'], ENT_QUOTES, 'UTF-8'); ?>
                            &rarr;
                            <?php echo htmlspecialchars($report['end_date'], ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </div>
                    <div class="report-meta-item">
                        <span class="meta-label">Generated By</span>
                        <span class="meta-value"><?php echo htmlspecialchars($report['generated_by'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="report-meta-item">
                        <span class="meta-label">Created At</span>
                        <span class="meta-value"><?php echo htmlspecialchars($report['created_at'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                </div>

                <!-- PDF link -->
                <?php
                $pdfPath     = $report['pdf_path'] ?? '';
                $fullPdfPath = __DIR__ . $pdfPath;
                ?>
                <?php if ($pdfPath !== '' && file_exists($fullPdfPath)): ?>
                    <a href="<?php echo htmlspecialchars($pdfPath, ENT_QUOTES, 'UTF-8'); ?>"
                       target="_blank" class="pdf-link">
                        &#x2197; Open Saved PDF
                    </a>
                <?php else: ?>
                    <p class="pdf-missing">Report file missing</p>
                <?php endif; ?>

                <!-- Analyst comment display -->
                <div class="insight-box">
                    <strong style="color:#e4e8f5; font-size:0.78rem; text-transform:uppercase; letter-spacing:0.08em;">Analyst Comment</strong>
                    <?php if (!empty($report['analyst_comment'])): ?>
                        <p style="margin-top:8px; color:#9ca3af; font-size:0.875rem; line-height:1.6;">
                            <?php echo nl2br(htmlspecialchars($report['analyst_comment'], ENT_QUOTES, 'UTF-8')); ?>
                        </p>
                    <?php else: ?>
                        <p style="margin-top:8px; color:#6b7280; font-style:italic; font-size:0.875rem;">No analyst comment added.</p>
                    <?php endif; ?>
                </div>

                <!-- Manage section (analysts / super admin only) -->
                <?php if ($canManage): ?>
                    <div class="manage-section">

                        <!-- Edit comment form -->
                        <form method="POST" action="/update_saved_report_comment.php">
                            <input type="hidden" name="report_id"
                                   value="<?php echo htmlspecialchars((string)$report['id'], ENT_QUOTES, 'UTF-8'); ?>">

                            <label class="field-label"
                                   for="comment_<?php echo (int)$report['id']; ?>">Edit Comment</label>

                            <textarea
                                id="comment_<?php echo (int)$report['id']; ?>"
                                name="analyst_comment"
                                rows="4"
                            ><?php echo htmlspecialchars($report['analyst_comment'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>

                            <div class="manage-actions" style="margin-top:12px;">
                                <button type="submit" class="btn-update">Update Comment</button>

                                <!-- Delete inside same action row -->
                                <form method="POST" action="/delete_saved_report.php"
                                      onsubmit="return confirm('Delete this saved report? This will also remove the PDF file.');"
                                      style="margin:0;">
                                    <input type="hidden" name="report_id"
                                           value="<?php echo htmlspecialchars((string)$report['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <button type="submit" class="btn-delete">Delete Report</button>
                                </form>
                            </div>
                        </form>

                    </div>
                <?php endif; ?>

            </div>

        <?php endforeach; ?>
    <?php endif; ?>

</div>

<?php include __DIR__ . '/views/footer.php'; ?>