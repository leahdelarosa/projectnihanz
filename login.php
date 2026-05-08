<?php
require 'config.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT id, password, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && verifyPassword($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        logAudit($user['id'], 'login', 'success');
        header('Location: dashboard.php');
        exit;
    } else {
        logAudit(null, 'login', 'failed');
        $error = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SecureVault — Login</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:        #0a0e1a;
            --surface:   #111827;
            --surface2:  #1a2235;
            --border:    rgba(99,179,237,0.12);
            --border-hi: rgba(99,179,237,0.35);
            --accent:    #3b82f6;
            --accent2:   #6366f1;
            --glow:      rgba(59,130,246,0.25);
            --text:      #f1f5f9;
            --muted:     #94a3b8;
            --danger:    #ef4444;
            --danger-bg: rgba(239,68,68,0.1);
            --success:   #10b981;
            --success-bg:rgba(16,185,129,0.1);
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            position: relative;
            overflow: hidden;
        }

        /* Animated background grid */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(59,130,246,0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(59,130,246,0.04) 1px, transparent 1px);
            background-size: 48px 48px;
            pointer-events: none;
        }

        /* Glow orbs */
        body::after {
            content: '';
            position: fixed;
            width: 600px; height: 600px;
            background: radial-gradient(circle, rgba(59,130,246,0.08) 0%, transparent 70%);
            top: -200px; left: -200px;
            pointer-events: none;
        }

        .orb2 {
            position: fixed;
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(99,102,241,0.07) 0%, transparent 70%);
            bottom: -150px; right: -150px;
            pointer-events: none;
            border-radius: 50%;
        }

        .card {
            position: relative;
            width: min(100%, 460px);
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 48px 40px;
            box-shadow: 0 0 0 1px rgba(255,255,255,0.03), 0 32px 80px rgba(0,0,0,0.5);
            z-index: 1;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 36px;
        }

        .brand-icon {
            width: 44px; height: 44px;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 0 20px var(--glow);
            flex-shrink: 0;
        }

        .brand-icon svg { width: 22px; height: 22px; fill: white; }

        .brand-name {
            font-size: 1.1rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: var(--text);
        }

        .brand-name span { color: var(--accent); }

        h1 {
            font-size: 1.75rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            line-height: 1.1;
            margin-bottom: 8px;
        }

        .subtitle {
            color: var(--muted);
            font-size: 0.9rem;
            line-height: 1.6;
            margin-bottom: 32px;
        }

        .alert {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 14px 16px;
            border-radius: 12px;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 24px;
            line-height: 1.5;
        }

        .alert-error {
            background: var(--danger-bg);
            border: 1px solid rgba(239,68,68,0.25);
            color: #fca5a5;
        }

        .alert-success {
            background: var(--success-bg);
            border: 1px solid rgba(16,185,129,0.25);
            color: #6ee7b7;
        }

        .alert svg { width: 16px; height: 16px; flex-shrink: 0; margin-top: 1px; }

        .field { margin-bottom: 20px; }

        label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 8px;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap svg {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            width: 16px; height: 16px;
            color: var(--muted);
            pointer-events: none;
        }

        input {
            width: 100%;
            padding: 13px 14px 13px 42px;
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 12px;
            color: var(--text);
            font-size: 0.95rem;
            font-family: inherit;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        input::placeholder { color: #475569; }

        input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(59,130,246,0.15);
        }

        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 0.95rem;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            transition: opacity 0.2s, transform 0.15s, box-shadow 0.2s;
            box-shadow: 0 4px 20px var(--glow);
            margin-top: 8px;
            letter-spacing: 0.01em;
        }

        .btn:hover {
            opacity: 0.92;
            transform: translateY(-1px);
            box-shadow: 0 8px 30px var(--glow);
        }

        .btn:active { transform: translateY(0); }

        .divider {
            height: 1px;
            background: var(--border);
            margin: 28px 0;
        }

        .links {
            display: flex;
            flex-direction: column;
            gap: 10px;
            align-items: center;
        }

        .links a {
            color: var(--muted);
            font-size: 0.875rem;
            text-decoration: none;
            transition: color 0.2s;
        }

        .links a:hover { color: var(--accent); }

        .links a strong { color: var(--accent); font-weight: 600; }

        .security-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: 28px;
            color: #334155;
            font-size: 0.75rem;
        }

        .security-badge svg { width: 12px; height: 12px; }
    </style>
</head>
<body>
    <div class="orb2"></div>
    <div class="card">
        <div class="brand">
            <div class="brand-icon">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/>
                </svg>
            </div>
            <div class="brand-name">Secure<span>Vault</span></div>
        </div>

        <h1>Welcome back</h1>
        <p class="subtitle">Sign in to your secure account to continue.</p>

        <?php if (isset($error)): ?>
        <div class="alert alert-error">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            Registration successful. You can now sign in.
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            Account deleted successfully.
        </div>
        <?php endif; ?>

        <form method="post" autocomplete="on">
            <div class="field">
                <label for="email">Email address</label>
                <div class="input-wrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    <input type="email" id="email" name="email" placeholder="you@example.com" autocomplete="email" required>
                </div>
            </div>

            <div class="field">
                <label for="password">Password</label>
                <div class="input-wrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <input type="password" id="password" name="password" placeholder="••••••••" autocomplete="current-password" required>
                </div>
            </div>

            <button type="submit" class="btn">Sign In</button>
        </form>

        <div class="divider"></div>

        <div class="links">
            <a href="forgot_password.php">Forgot your password?</a>
            <a href="signup.php">No account yet? <strong>Create one</strong></a>
        </div>

        <div class="security-badge">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
            256-bit encrypted · Secure session
        </div>
    </div>
</body>
</html>
