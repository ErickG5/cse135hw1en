<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:            #080b12;
            --surface:       #111827;
            --card:          #0d1120;
            --border:        rgba(255,255,255,0.06);
            --border-strong: rgba(255,255,255,0.1);
            --accent:        #4f7cff;
            --accent-2:      #8b5cf6;
            --text:          #f0f2ff;
            --muted:         #6b7280;
            --danger:        #f87171;
            --good:          #34d399;
        }

        html, body {
            height: 100%;
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            -webkit-font-smoothing: antialiased;
        }

        /* Same background effects as login page */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse 60% 50% at 50% 40%, rgba(79,124,255,0.07) 0%, transparent 60%),
                radial-gradient(ellipse 40% 40% at 60% 60%, rgba(139,92,246,0.05) 0%, transparent 55%);
            pointer-events: none;
            z-index: 0;
        }

        body::after {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
            background-size: 48px 48px;
            pointer-events: none;
            z-index: 0;
        }

        /* ── Top bar ── */
        .top-bar {
            position: sticky;
            top: 0;
            z-index: 100;
            height: 56px;
            background: var(--card);
            border-bottom: 1px solid var(--border-strong);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 28px;
        }

        .top-bar-brand {
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .top-bar-brand .pip {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--accent);
            box-shadow: 0 0 10px var(--accent);
            flex-shrink: 0;
        }

        .top-bar-brand span {
            font-family: 'Syne', sans-serif;
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .top-bar-right {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .top-bar-user {
            font-size: 0.82rem;
            color: var(--muted);
        }

        .top-bar-logout {
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--danger);
            text-decoration: none;
            border: 1px solid rgba(248,113,113,0.25);
            border-radius: 8px;
            padding: 5px 14px;
            transition: background 150ms ease, border-color 150ms ease;
        }

        .top-bar-logout:hover {
            background: rgba(248,113,113,0.1);
            border-color: var(--danger);
        }

        /* ── Shell: sidebar + content ── */
        .shell {
            display: grid;
            grid-template-columns: 210px 1fr;
            min-height: calc(100vh - 56px);
            position: relative;
            z-index: 1;
        }

        /* ── Sidebar ── */
        .sidebar {
            background: var(--card);
            border-right: 1px solid var(--border-strong);
            padding: 20px 0;
            position: sticky;
            top: 56px;
            height: calc(100vh - 56px);
            overflow-y: auto;
        }

        .sidebar::-webkit-scrollbar { width: 3px; }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.07); border-radius: 9999px; }

        .sidebar-label {
            font-size: 0.63rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: rgba(107,114,128,0.5);
            padding: 0 18px;
            margin: 18px 0 5px;
        }

        .sidebar-label:first-child { margin-top: 4px; }

        .sidebar a {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 8px 18px;
            font-size: 0.855rem;
            font-weight: 500;
            color: var(--muted);
            text-decoration: none;
            border-left: 2px solid transparent;
            transition: color 150ms ease, background 150ms ease, border-color 150ms ease;
            white-space: nowrap;
        }

        .sidebar a:hover {
            color: var(--text);
            background: rgba(255,255,255,0.04);
            border-left-color: rgba(255,255,255,0.12);
        }

        .sidebar a.active {
            color: var(--accent);
            background: rgba(79,124,255,0.1);
            border-left-color: var(--accent);
            font-weight: 600;
        }

        .sidebar-divider {
            height: 1px;
            background: var(--border);
            margin: 10px 18px;
        }

        /* ── Main content ── */
        .main {
            padding: 36px 40px;
            min-height: calc(100vh - 56px);
            overflow-x: hidden;
        }

        /* ── Reusable box — same as login form-box ── */
        .box {
            background: var(--card);
            border: 1px solid var(--border-strong);
            border-radius: 16px;
            box-shadow:
                0 0 0 1px rgba(255,255,255,0.03),
                0 8px 32px rgba(0,0,0,0.5),
                0 2px 8px rgba(0,0,0,0.3);
            transition: border-color 150ms ease, box-shadow 150ms ease;
        }

        .box:hover {
            border-color: rgba(79,124,255,0.22);
            box-shadow:
                0 0 0 1px rgba(255,255,255,0.04),
                0 8px 32px rgba(0,0,0,0.6),
                0 0 24px rgba(79,124,255,0.07);
        }

        /* ── Typography helpers ── */
        .page-heading {
            font-family: 'Syne', sans-serif;
            font-size: 1.7rem;
            font-weight: 700;
            letter-spacing: -0.03em;
            color: var(--text);
            margin-bottom: 4px;
        }

        .page-subheading {
            font-size: 0.875rem;
            color: var(--muted);
            margin-bottom: 28px;
        }

        /* ── Role pill ── */
        .role-pill {
            display: inline-block;
            padding: 3px 12px;
            border-radius: 9999px;
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .role-super_admin { background: rgba(79,124,255,0.18); color: #4f7cff; }
        .role-analyst     { background: rgba(52,211,153,0.15); color: #34d399; }
        .role-viewer      { background: rgba(107,114,128,0.2);  color: #9ca3af; }

        /* ── Card grid ── */
        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
        }

        .card-box {
            padding: 22px 24px;
        }

        .card-label {
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--muted);
            margin-bottom: 10px;
        }

        .card-value {
            font-family: 'Syne', sans-serif;
            font-size: 1.3rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: var(--text);
        }

        /* ── Welcome banner ── */
        .welcome-box {
            padding: 24px 28px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .welcome-pip {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--accent);
            box-shadow: 0 0 12px var(--accent);
            flex-shrink: 0;
        }

        .welcome-box h2 {
            font-family: 'Syne', sans-serif;
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 2px;
        }

        .welcome-box p {
            font-size: 0.85rem;
            color: var(--muted);
        }

        /* ── Divider ── */
        .section-divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 28px 0;
        }

        .section-divider::before, .section-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        .section-divider span {
            font-size: 0.72rem;
            color: var(--muted);
            white-space: nowrap;
        }
    </style>
</head>
<body>

<noscript>
<div style="background:#ffdddd;padding:12px;text-align:center;font-weight:bold;">
This analytics platform works best with JavaScript enabled. Some charts and interactive features may not display.
</div>
</noscript>


<!-- Top bar -->
<header class="top-bar">
    <a href="/dashboard.php" class="top-bar-brand">
        <div class="pip"></div>
        <span>Analytics Platform</span>
    </a>
    <div class="top-bar-right">
        <?php if (!empty($_SESSION['username'])): ?>
            <span class="top-bar-user"><?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?></span>
        <?php endif; ?>
        <a href="/logout.php" class="top-bar-logout">Sign out</a>
    </div>
</header>

<div class="shell">
