<style>

/* ── Report page wrapper ── */
.report-page {
    padding: 32px 32px 0;
}

.report-page h1 {
    font-family: 'Syne', sans-serif;
    font-size: 1.7rem;
    font-weight: 700;
    letter-spacing: -0.03em;
    color: #e4e8f5;
    margin-bottom: 8px;
}

.report-page .subtitle {
    font-size: 0.875rem;
    color: #6b7280;
    margin-bottom: 32px;
}

/* ── Quick range bar ── */
.quick-range-bar {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 28px;
}

.quick-range-bar a {
    text-decoration: none;
}

.quick-range-btn {
    background: #0d1120;
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 8px;
    padding: 8px 14px;
    font-family: 'DM Sans', sans-serif;
    font-size: 0.82rem;
    font-weight: 500;
    color: #6b7280;
    cursor: pointer;
    transition: background 150ms ease, border-color 150ms ease, color 150ms ease;
}

.quick-range-btn:hover {
    background: rgba(255,255,255,0.04);
    border-color: rgba(255,255,255,0.18);
    color: #e4e8f5;
}

.quick-range-btn.active {
    background: rgba(79,124,255,0.15);
    border-color: #4f7cff;
    color: #4f7cff;
    font-weight: 600;
}

/* ── Filter form ── */
.filter-form {
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.filter-form label {
    font-size: 0.78rem;
    font-weight: 500;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    margin-right: 0;
}

.filter-form input,
.filter-form select {
    background: #0d1120;
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 8px;
    color: #e4e8f5;
    font-family: 'DM Sans', sans-serif;
    font-size: 0.875rem;
    padding: 7px 12px;
    outline: none;
    transition: border-color 150ms ease, box-shadow 150ms ease;
    color-scheme: dark;
}

.filter-form input:focus,
.filter-form select:focus {
    border-color: #4f7cff;
    box-shadow: 0 0 0 3px rgba(79,124,255,0.15);
}

.filter-form button {
    background: #4f7cff;
    border: none;
    border-radius: 8px;
    color: #fff;
    font-family: 'DM Sans', sans-serif;
    font-size: 0.875rem;
    font-weight: 500;
    padding: 7px 16px;
    cursor: pointer;
    transition: background 150ms ease, box-shadow 150ms ease;
}

.filter-form button:hover {
    background: #6b93ff;
    box-shadow: 0 0 20px rgba(79,124,255,0.35);
}

/* ── Summary cards ── */
.summary-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}

.summary-card {
    background: #0d1120;
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 16px;
    padding: 20px 22px;
    box-shadow:
        0 0 0 1px rgba(255,255,255,0.03),
        0 8px 32px rgba(0,0,0,0.5),
        0 2px 8px rgba(0,0,0,0.3);
    transition: border-color 150ms ease, box-shadow 150ms ease;
}

.summary-card:hover {
    border-color: rgba(79,124,255,0.25);
    box-shadow:
        0 0 0 1px rgba(255,255,255,0.04),
        0 8px 32px rgba(0,0,0,0.6),
        0 0 20px rgba(79,124,255,0.08);
}

.summary-card h3 {
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: #6b7280;
    margin: 0 0 10px 0;
}

.summary-card .metric {
    font-family: 'Syne', sans-serif;
    font-size: 1.5rem;
    font-weight: 700;
    letter-spacing: -0.03em;
    color: #e4e8f5;
    overflow-wrap: anywhere;
    line-height: 1.1;
}

.kpi-subtext {
    margin-top: 6px;
    font-size: 0.9rem;
    color: #6b7280;
}

.kpi-delta {
    margin-top: 6px;
    font-size: 0.95rem;
    font-weight: bold;
}

.kpi-up   { color: #34d399; }
.kpi-down { color: #f87171; }
.kpi-flat { color: #6b7280; }

/* ── Chart cards ── */
.chart-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 28px;
}

.chart-card {
    background: #0d1120;
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 16px;
    padding: 20px 22px;
    box-shadow:
        0 0 0 1px rgba(255,255,255,0.03),
        0 8px 32px rgba(0,0,0,0.5);
}

.chart-card.wide {
    grid-column: 1 / -1;
}

.chart-card h2 {
    font-family: 'Syne', sans-serif;
    font-size: 1rem;
    font-weight: 600;
    color: #e4e8f5;
    margin: 0 0 16px 0;
    letter-spacing: -0.01em;
}

.chart-wrap {
    position: relative;
    height: 320px;
}

.chart-card canvas {
    background: transparent !important;
}

/* ── Table card ── */
.table-card {
    background: #0d1120;
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 16px;
    padding: 20px 22px;
    box-shadow:
        0 0 0 1px rgba(255,255,255,0.03),
        0 8px 32px rgba(0,0,0,0.5);
    margin-bottom: 24px;
    overflow-x: auto;
}

.table-card h2 {
    font-family: 'Syne', sans-serif;
    font-size: 1rem;
    font-weight: 600;
    color: #e4e8f5;
    margin: 0 0 16px 0;
}

/* ── Notes card ── */
.notes-card {
    background: #0d1120;
    border: 1px solid rgba(255,255,255,0.1);
    border-left: 3px solid #4f7cff;
    border-radius: 0 16px 16px 0;
    padding: 20px 24px;
    margin-bottom: 24px;
    box-shadow:
        0 0 0 1px rgba(255,255,255,0.03),
        0 8px 32px rgba(0,0,0,0.5);
}

.table-card h2,
.notes-card h2 {
    margin-top: 0;
}

.notes-card h2 {
    font-family: 'Syne', sans-serif;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: #4f7cff;
    margin: 0 0 10px 0;
}

.notes-card p {
    margin: 0;
    font-size: 0.9rem;
    color: #6b7280;
    line-height: 1.6;
}

/* ── Tables ── */
table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}

th, td {
    padding: 10px 12px;
    text-align: left;
    vertical-align: top;
}

th {
    background: rgba(255,255,255,0.03);
    border-bottom: 1px solid rgba(255,255,255,0.1);
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #6b7280;
    white-space: nowrap;
    /* Remove old light border */
    border-left: none;
    border-right: none;
    border-top: none;
}

td {
    border-bottom: 1px solid rgba(255,255,255,0.06);
    color: #e4e8f5;
    border-left: none;
    border-right: none;
    border-top: none;
}

tr:last-child td { border-bottom: none; }

tr:hover td { background: rgba(255,255,255,0.02); }

/* ── Status helpers ── */
.status-healthy  { color: #34d399; }
.status-watch    { color: #fbbf24; }
.status-attention{ color: #f87171; }

.status-note {
    margin-top: 8px;
    font-size: 0.82rem;
    color: #6b7280;
    line-height: 1.5;
}

.metric.status-healthy   { color: #34d399; font-weight: 600; }
.metric.status-watch     { color: #fbbf24; font-weight: 600; }
.metric.status-attention { color: #f87171; font-weight: 600; }

/* ── Status dot ── */
.status-dot {
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    margin-right: 6px;
    vertical-align: middle;
}

.status-dot.healthy    { background: #34d399; box-shadow: 0 0 6px #34d399; }
.status-dot.watch      { background: #fbbf24; box-shadow: 0 0 6px #fbbf24; }
.status-dot.attention  { background: #f87171; box-shadow: 0 0 6px #f87171; }

/* ── Insight box ── */
.insight-box {
    background: rgba(79,124,255,0.06);
    border-left: 4px solid #4f7cff;
    border-radius: 0 8px 8px 0;
    padding: 15px;
    margin-top: 12px;
    font-size: 0.875rem;
    color: #6b7280;
    line-height: 1.6;
}

/* ── Saved report actions ── */
.saved-report-actions {
    margin-top: 16px;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.saved-report-actions button,
.saved-report-actions a {
    background: #0d1120;
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 8px;
    color: #e4e8f5;
    font-family: 'DM Sans', sans-serif;
    font-size: 0.82rem;
    font-weight: 500;
    padding: 7px 16px;
    cursor: pointer;
    text-decoration: none;
    transition: background 150ms ease, border-color 150ms ease;
}

.saved-report-actions button:hover,
.saved-report-actions a:hover {
    background: rgba(255,255,255,0.05);
    border-color: rgba(255,255,255,0.2);
}

/* ── Responsive ── */
@media (max-width: 800px) {
    .summary-grid,
    .chart-grid {
        grid-template-columns: 1fr;
    }
    .chart-card.wide {
        grid-column: auto;
    }
}

.prepare-snapshot-btn {
    background: rgba(79,124,255,0.1);
    border: 1px solid rgba(79,124,255,0.3);
    border-radius: 8px;
    color: #4f7cff;
    font-family: 'DM Sans', sans-serif;
    font-size: 0.85rem;
    font-weight: 500;
    padding: 8px 18px;
    cursor: pointer;
    transition: all 150ms ease;
    text-decoration: none;
    display: inline-block;
    letter-spacing: 0.02em;
}

.prepare-snapshot-btn:hover {
    background: rgba(79,124,255,0.15);
    border-color: #4f7cff;
    color: #6b93ff;
    box-shadow: 0 0 20px rgba(79,124,255,0.25);
    transform: translateY(-1px);
}

.prepare-snapshot-btn:active {
    transform: translateY(1px);
    box-shadow: none;
}

/* ── Analyst Comment Section ── */
.analyst-comment-label {
    display: block;
    font-size: 0.78rem;
    font-weight: 500;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    margin-bottom: 8px;
}

.analyst-comment-textarea {
    width: 100%;
    background: #0d1120;
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 8px;
    color: #e4e8f5;
    font-family: 'DM Sans', sans-serif;
    font-size: 0.875rem;
    padding: 12px;
    outline: none;
    resize: vertical;
    transition: border-color 150ms ease, box-shadow 150ms ease;
}

.analyst-comment-textarea:focus {
    border-color: #4f7cff;
    box-shadow: 0 0 0 3px rgba(79,124,255,0.15);
}

.button-group {
    display: flex;
    gap: 12px;
    align-items: center;
    margin-top: 8px;
}

.btn-primary {
    background: #4f7cff;
    color: #fff;
    border: none;
    padding: 8px 20px;
}

.btn-primary:hover {
    background: #6b93ff;
    box-shadow: 0 0 20px rgba(79,124,255,0.35);
    transform: translateY(-1px);
}

.btn-outline {
    background: transparent;
    border: 1px solid rgba(79,124,255,0.3);
    color: #4f7cff;
}

.btn-outline:hover {
    background: rgba(79,124,255,0.1);
    border-color: #4f7cff;
    color: #6b93ff;
}

</style>

<script>
/* Chart.js dark theme — white grid lines and tick numbers */
(function () {
    if (typeof Chart === 'undefined') return;

    Chart.defaults.color                              = '#e4e8f5';
    Chart.defaults.borderColor                        = 'rgba(255,255,255,0.35)';
    Chart.defaults.scale.grid.color                  = 'rgba(255,255,255,0.35)';
    Chart.defaults.scale.grid.borderColor            = 'rgba(255,255,255,0.5)';
    Chart.defaults.scale.ticks.color                 = '#e4e8f5';
    Chart.defaults.scale.title.color                 = '#e4e8f5';
    Chart.defaults.plugins.legend.labels.color       = '#e4e8f5';
    Chart.defaults.plugins.tooltip.backgroundColor   = '#0d1120';
    Chart.defaults.plugins.tooltip.titleColor        = '#e4e8f5';
    Chart.defaults.plugins.tooltip.bodyColor         = '#9ca3af';
    Chart.defaults.plugins.tooltip.borderColor       = 'rgba(255,255,255,0.1)';
    Chart.defaults.plugins.tooltip.borderWidth       = 1;
    Chart.defaults.plugins.tooltip.padding           = 10;
    Chart.defaults.plugins.tooltip.cornerRadius      = 8;
})();
</script>