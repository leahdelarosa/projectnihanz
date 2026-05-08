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
        /* ============================================
           FONTS
           ============================================ */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

        /* ============================================
           RESET & BASE STYLES
           ============================================ */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        /* ============================================
           DESIGN TOKENS
           ============================================ */
        :root {
            /* Colors */
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

        /* ============================================
           LAYOUT
           ============================================ */
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

        /* Background grid pattern */
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

        /* Decorative glow orbs */
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

        /* ============================================
           COMPONENTS - Card
           ============================================ */
        .card {
            position: relative;
            width: min(100%, 460px);
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 48px 40px;
            box-shadow: 0 0 0 1px rgba(255,255,255,0.03), 0 32px 80px rgba(0,0,0,0.5);
            z-index: 1;
            animation: fadeIn 0.4s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ============================================
           COMPONENTS - Brand
           ============================================ */
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

        /* ============================================
           COMPONENTS - Typography
           ============================================ */
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

        /* ============================================
           COMPONENTS - Alerts
           ============================================ */
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

        .alert svg { width: 16px; height: 16px; flex-shrink: 0; margin-top: 1px; }

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

        /* ============================================
           COMPONENTS - Forms
           ============================================ */
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

        /* ============================================
           COMPONENTS - Buttons
           ============================================ */
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
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .btn svg {
            width: 14px;
            height: 14px;
            flex-shrink: 0;
        }

        .btn:hover {
            opacity: 0.92;
            transform: translateY(-1px);
            box-shadow: 0 8px 30px var(--glow);
        }

        .btn:active { transform: translateY(0); }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .btn-loading {
            position: relative;
            color: transparent;
        }

        .btn-loading::after {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            top: 50%;
            left: 50%;
            margin-left: -8px;
            margin-top: -8px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.6s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* ============================================
           UTILITIES
           ============================================ */
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

        /* ============================================
           RESPONSIVE DESIGN
           ============================================ */
        /* Tablet: 560px - 899px */
        @media (max-width: 899px) and (min-width: 560px) {
            body { padding: 20px; }
            .card { padding: 40px 32px; }
        }

        /* Mobile: < 560px */
        @media (max-width: 560px) {
            body { padding: 16px; }
            .card { 
                padding: 32px 24px; 
                border-radius: 20px;
            }
            h1 { font-size: 1.5rem; }
            .subtitle { font-size: 0.85rem; }
            .brand { margin-bottom: 28px; }
            .brand-icon { width: 40px; height: 40px; }
            .brand-icon svg { width: 20px; height: 20px; }
            .brand-name { font-size: 1rem; }
            
            /* Ensure minimum touch target sizes (44x44px) */
            input { 
                padding: 15px 14px 15px 42px; 
                font-size: 16px; /* Prevents zoom on iOS */
            }
            .btn { 
                padding: 16px; 
                font-size: 1rem;
                min-height: 44px;
            }
            
            /* Adjust font sizes for mobile readability */
            label { font-size: 0.75rem; }
            .alert { font-size: 0.8rem; padding: 12px 14px; }
            .links a { font-size: 0.8rem; }
            .security-badge { font-size: 0.7rem; margin-top: 24px; }
        }
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

            <button type="submit" class="btn" id="submitBtn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                Sign In
            </button>
        </form>

        <script>
            document.querySelector('form').addEventListener('submit', function(e) {
                const btn = document.getElementById('submitBtn');
                btn.disabled = true;
                btn.classList.add('btn-loading');
                btn.textContent = 'Signing in...';
            });
        </script>

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
