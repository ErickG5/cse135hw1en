<?php
require_once __DIR__ . '/middleware/session_bootstrap.php';
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
if (!empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: /dashboard.php");
    exit();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$error_message = '';
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'inactive') {
        $error_message = 'This account has been deactivated. Please contact an administrator.';
    } elseif ($_GET['error'] === 'invalid') {
        $error_message = 'Invalid username or password.';
    } elseif ($_GET['error'] === 'locked') {
        $error_message = 'Too many failed login attempts. Please try again in 15 minutes.';
    } elseif ($_GET['error'] === 'csrf') {
        $error_message = 'Your session expired. Please try logging in again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — Analytics</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:       #080b12;
            --surface:  #111827;
            --card:     #0d1120;
            --border:   rgba(255,255,255,0.06);
            --border-strong: rgba(255,255,255,0.1);
            --accent:   #4f7cff;
            --accent-2: #8b5cf6;
            --text:     #f0f2ff;
            --muted:    #6b7280;
            --danger:   #f87171;
        }

        html, body {
            height: 100%;
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            -webkit-font-smoothing: antialiased;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse 60% 50% at 50% 40%, rgba(79,124,255,0.1) 0%, transparent 60%),
                radial-gradient(ellipse 40% 40% at 60% 60%, rgba(139,92,246,0.07) 0%, transparent 55%);
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

        .page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            position: relative;
            z-index: 1;
        }

        .form-card {
            width: 100%;
            max-width: 420px;
            animation: slideUp 400ms cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Brand mark above the box */
        .brand-mark {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 24px;
        }

        .brand-mark .pip {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--accent);
            box-shadow: 0 0 12px var(--accent);
        }

        .brand-mark span {
            font-family: 'Syne', sans-serif;
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--muted);
        }

        /* The box */
        .form-box {
            background: var(--card);
            border: 1px solid var(--border-strong);
            border-radius: 16px;
            padding: 36px 32px;
            box-shadow:
                0 0 0 1px rgba(255,255,255,0.03),
                0 8px 32px rgba(0,0,0,0.5),
                0 2px 8px rgba(0,0,0,0.3);
        }

        .heading {
            font-family: 'Syne', sans-serif;
            font-size: 1.7rem;
            font-weight: 700;
            letter-spacing: -0.03em;
            color: var(--text);
            margin-bottom: 4px;
            text-align: center;
        }

        .subheading {
            font-size: 0.875rem;
            color: var(--muted);
            margin-bottom: 28px;
            text-align: center;
        }

        /* Error */
        .error-box {
            background: rgba(248,113,113,0.08);
            border: 1px solid rgba(248,113,113,0.22);
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 0.85rem;
            color: var(--danger);
            margin-bottom: 24px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .error-box::before {
            content: '!';
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: rgba(248,113,113,0.2);
            font-weight: 700;
            font-size: 0.75rem;
            flex-shrink: 0;
            margin-top: 1px;
        }

        /* Fields */
        .field { margin-bottom: 20px; }

        .field label {
            display: block;
            font-size: 0.78rem;
            font-weight: 500;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.07em;
            margin-bottom: 8px;
        }

        .field input {
            width: 100%;
            background: var(--surface);
            border: 1px solid var(--border-strong);
            border-radius: 10px;
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            font-size: 0.95rem;
            padding: 13px 16px;
            outline: none;
            transition: border-color 150ms ease, box-shadow 150ms ease, background 150ms ease;
        }

        .field input::placeholder { color: #374151; }

        .field input:focus {
            border-color: var(--accent);
            background: #0f1522;
            box-shadow: 0 0 0 3px rgba(79,124,255,0.15), inset 0 1px 2px rgba(0,0,0,0.3);
        }

        /* Submit */
        .submit-btn {
            width: 100%;
            margin-top: 8px;
            padding: 14px;
            background: var(--accent);
            color: #fff;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.95rem;
            font-weight: 500;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: background 150ms ease, box-shadow 150ms ease, transform 100ms ease;
            letter-spacing: 0.01em;
        }

        .submit-btn::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.12) 0%, transparent 60%);
            pointer-events: none;
        }

        .submit-btn:hover {
            background: #6b93ff;
            box-shadow: 0 0 28px rgba(79,124,255,0.4);
        }

        .submit-btn:active { transform: translateY(1px); }

        /* Footer */
        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 24px;
        }

        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        .divider span {
            font-size: 0.75rem;
            color: var(--muted);
            white-space: nowrap;
        }
    </style>
</head>
<body>

<div class="page">
    <div class="form-card">

        <div class="brand-mark">
            <div class="pip"></div>
            <span>Analytics Platform</span>
        </div>

        <!-- Box around the form -->
        <div class="form-box">

            <div class="heading">Welcome back</div>
            <div class="subheading">Sign in to your account to continue</div>

            <?php if ($error_message !== ''): ?>
                <div class="error-box">
                    <?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="/controllers/authenticate.php">
                <input type="hidden" name="csrf_token"
                       value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

                <div class="field">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required
                           autocomplete="username" placeholder="your username" autofocus>
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required
                           autocomplete="current-password" placeholder="your password">
                </div>

                <button type="submit" class="submit-btn">Sign In</button>
            </form>

            <div class="divider"><span>CSE 135 Analytics Platform</span></div>

        </div>

    </div>
</div>

</body>
</html>