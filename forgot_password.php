<?php
require 'config.php';

$step = 1;
$security_question = '';
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['send_email'])) {
        $email = sanitizeInput($_POST['email']);
        if (!validateEmail($email)) {
            $error = "Please enter a valid email address.";
        } else {
            $stmt = $pdo->prepare("SELECT id, security_question FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if ($user) {
                $_SESSION['reset_user_id'] = $user['id'];
                $_SESSION['reset_security_question'] = $user['security_question'];
                $security_question = $user['security_question'];
                $step = 2;
            } else {
                $error = "No account found with that email.";
            }
        }
    } elseif (isset($_POST['reset_password'])) {
        if (!isset($_SESSION['reset_user_id'])) {
            $error = "Session expired. Please start again.";
            $step = 1;
        } else {
            $security_answer  = sanitizeInput($_POST['security_answer']);
            $new_password     = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];

            if (empty($security_answer) || empty($new_password) || empty($confirm_password)) {
                $error = "All fields are required.";
                $step = 2;
                $security_question = $_SESSION['reset_security_question'] ?? '';
            } elseif ($new_password !== $confirm_password) {
                $error = "Passwords do not match.";
                $step = 2;
                $security_question = $_SESSION['reset_security_question'] ?? '';
            } elseif (!validatePassword($new_password)) {
                $error = "Password must be at least 8 characters with uppercase, lowercase, and a number.";
                $step = 2;
                $security_question = $_SESSION['reset_security_question'] ?? '';
            } else {
                $stmt = $pdo->prepare("SELECT security_answer FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['reset_user_id']]);
                $stored_answer = $stmt->fetchColumn();
                if ($stored_answer && verifyPassword($security_answer, $stored_answer)) {
                    $hashed_password = hashPassword($new_password);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    if ($stmt->execute([$hashed_password, $_SESSION['reset_user_id']])) {
                        $success = "Password reset successfully. You can now sign in with your new password.";
                        logAudit($_SESSION['reset_user_id'], 'password_reset', 'success');
                        unset($_SESSION['reset_user_id'], $_SESSION['reset_security_question']);
                        $step = 1;
                    } else {
                        $error = "Password reset failed. Please try again.";
                        $step = 2;
                        $security_question = $_SESSION['reset_security_question'] ?? '';
                    }
                } else {
                    $error = "Invalid security answer.";
                    $step = 2;
                    $security_question = $_SESSION['reset_security_question'] ?? '';
                }
            }
        }
    }
}

if ($step === 2 && empty($security_question)) {
    $security_question = $_SESSION['reset_security_question'] ?? '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SecureVault — Reset Password</title>
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
        .brand-name { font-size: 1.1rem; font-weight: 700; letter-spacing: -0.02em; color: var(--text); }
        .brand-name span { color: var(--accent); }

        /* Step indicator */
        .steps {
            display: flex;
            align-items: center;
            gap: 0;
            margin-bottom: 32px;
        }

        .step {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.78rem;
            font-weight: 600;
        }

        .step-num {
            width: 24px; height: 24px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.7rem;
            font-weight: 700;
        }

        .step.done .step-num  { background: rgba(16,185,129,0.15); color: #6ee7b7; border: 1px solid rgba(16,185,129,0.3); }
        .step.active .step-num{ background: rgba(59,130,246,0.15); color: var(--accent); border: 1px solid rgba(59,130,246,0.3); }
        .step.idle .step-num  { background: var(--surface2); color: var(--muted); border: 1px solid var(--border); }

        .step.done  .step-label { color: #6ee7b7; }
        .step.active .step-label{ color: var(--text); }
        .step.idle  .step-label { color: var(--muted); }

        .step-connector {
            flex: 1;
            height: 1px;
            background: var(--border);
            margin: 0 10px;
        }

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
            display: flex; align-items: flex-start; gap: 10px;
            padding: 14px 16px; border-radius: 12px;
            font-size: 0.875rem; font-weight: 500;
            margin-bottom: 24px; line-height: 1.5;
        }

        .alert svg { width: 16px; height: 16px; flex-shrink: 0; margin-top: 1px; }
        .alert-error   { background: var(--danger-bg);  border: 1px solid rgba(239,68,68,0.25);  color: #fca5a5; }
        .alert-success { background: var(--success-bg); border: 1px solid rgba(16,185,129,0.25); color: #6ee7b7; }

        .field { margin-bottom: 20px; }

        label {
            display: block;
            font-size: 0.8rem; font-weight: 600;
            letter-spacing: 0.06em; text-transform: uppercase;
            color: var(--muted); margin-bottom: 8px;
        }

        .input-wrap { position: relative; }

        .input-wrap svg {
            position: absolute; left: 14px; top: 50%;
            transform: translateY(-50%);
            width: 16px; height: 16px; color: var(--muted); pointer-events: none;
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

        input:disabled {
            opacity: 0.6;
            cursor: not-allowed;
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
            border: none; border-radius: 12px;
            color: white; font-size: 0.95rem; font-weight: 700;
            font-family: inherit; cursor: pointer;
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

        .btn:hover { opacity: 0.92; transform: translateY(-1px); box-shadow: 0 8px 30px var(--glow); }
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

        .card {
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

        .divider { height: 1px; background: var(--border); margin: 28px 0; }

        .back-link {
            display: flex; align-items: center; justify-content: center; gap: 6px;
            color: var(--muted); font-size: 0.875rem; text-decoration: none;
            transition: color 0.2s;
        }

        .back-link svg { width: 13px; height: 13px; }
        .back-link:hover { color: var(--accent); }

        .security-badge {
            display: flex; align-items: center; justify-content: center; gap: 6px;
            margin-top: 28px; color: #334155; font-size: 0.75rem;
        }

        .security-badge svg { width: 12px; height: 12px; }

        /* Responsive Design */
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
            
            /* Step indicator adjustments */
            .steps { margin-bottom: 28px; gap: 0; }
            .step { font-size: 0.7rem; }
            .step-num { width: 22px; height: 22px; font-size: 0.65rem; }
            .step-label { display: none; } /* Hide labels on very small screens */
            .step-connector { margin: 0 8px; }
            
            /* Ensure minimum touch target sizes (44x44px) */
            input { 
                padding: 15px 14px 15px 42px; 
                font-size: 16px; /* Prevents zoom on iOS */
                min-height: 44px;
            }
            
            .btn { 
                padding: 16px; 
                font-size: 1rem;
                min-height: 44px;
            }
            
            /* Adjust font sizes for mobile readability */
            label { font-size: 0.75rem; }
            .alert { font-size: 0.8rem; padding: 12px 14px; }
            .back-link { font-size: 0.8rem; }
            .security-badge { font-size: 0.7rem; margin-top: 24px; }
        }

        @media (max-width: 480px) { 
            .card { padding: 28px 20px; }
        }
    </style>
</head>
<body>
    <div class="orb2"></div>
    <div class="card">
        <div class="brand">
            <div class="brand-icon">
                <svg viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
            </div>
            <div class="brand-name">Secure<span>Vault</span></div>
        </div>

        <!-- Step indicator -->
        <div class="steps">
            <div class="step <?php echo $step >= 1 ? ($step > 1 ? 'done' : 'active') : 'idle'; ?>">
                <div class="step-num"><?php echo $step > 1 ? '✓' : '1'; ?></div>
                <span class="step-label">Verify Email</span>
            </div>
            <div class="step-connector"></div>
            <div class="step <?php echo $step >= 2 ? 'active' : 'idle'; ?>">
                <div class="step-num">2</div>
                <span class="step-label">Reset Password</span>
            </div>
        </div>

        <?php if ($step === 1): ?>
        <h1>Forgot password?</h1>
        <p class="subtitle">Enter your email and we'll verify your identity using your security question.</p>
        <?php else: ?>
        <h1>Reset password</h1>
        <p class="subtitle">Answer your security question and choose a new strong password.</p>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-error">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="alert alert-success">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            <?php echo htmlspecialchars($success); ?>
        </div>
        <?php endif; ?>

        <?php if ($step === 1): ?>
        <form method="post">
            <div class="field">
                <label for="email">Email Address</label>
                <div class="input-wrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    <input type="email" id="email" name="email" placeholder="you@example.com" autocomplete="email" required>
                </div>
            </div>
            <button type="submit" name="send_email" class="btn" id="continueBtn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="M12 5l7 7-7 7"/></svg>
                Continue
            </button>
        </form>

        <?php else: ?>
        <form method="post">
            <div class="field">
                <label>Your Security Question</label>
                <div class="input-wrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    <input type="text" value="<?php echo htmlspecialchars($security_question); ?>" disabled>
                </div>
            </div>
            <div class="field">
                <label for="security_answer">Security Answer</label>
                <div class="input-wrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    <input type="password" id="security_answer" name="security_answer" placeholder="Your answer" autocomplete="off" required>
                </div>
            </div>
            <div class="field">
                <label for="new_password">New Password</label>
                <div class="input-wrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <input type="password" id="new_password" name="new_password" placeholder="Min 8 chars, upper, lower, number" autocomplete="new-password" required>
                </div>
            </div>
            <div class="field">
                <label for="confirm_password">Confirm New Password</label>
                <div class="input-wrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Repeat new password" autocomplete="new-password" required>
                </div>
            </div>
            <button type="submit" name="reset_password" class="btn" id="resetBtn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                Reset Password
            </button>
        </form>
        <?php endif; ?>

        <div class="divider"></div>

        <a href="login.php" class="back-link">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
            Back to Sign In
        </a>

        <div class="security-badge">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
            Identity verified via security question
        </div>
    </div>

    <script>
        // Form submission loading states
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.classList.add('btn-loading');
                    
                    // Update button text based on which form
                    if (submitBtn.name === 'send_email') {
                        submitBtn.textContent = 'Verifying...';
                    } else if (submitBtn.name === 'reset_password') {
                        submitBtn.textContent = 'Resetting...';
                    }
                }
            });
        });
    </script>
</body>
</html>
