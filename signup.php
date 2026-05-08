<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name        = sanitizeInput($_POST['full_name']);
    $email            = sanitizeInput($_POST['email']);
    $password         = $_POST['password'];
    $contact_number   = sanitizeInput($_POST['contact_number']);
    $address          = sanitizeInput($_POST['address']);
    $birthdate        = sanitizeInput($_POST['birthdate']);
    $security_question= sanitizeInput($_POST['security_question']);
    $security_answer  = sanitizeInput($_POST['security_answer']);

    $errors = [];

    if (empty($full_name))        $errors[] = "Full name is required.";
    if (!validateEmail($email))   $errors[] = "Invalid email address.";
    if (!validatePassword($password)) $errors[] = "Password must be at least 8 characters with uppercase, lowercase, and a number.";
    if (empty($contact_number))   $errors[] = "Contact number is required.";
    if (empty($address))          $errors[] = "Address is required.";
    if (empty($birthdate))        $errors[] = "Birthdate is required.";
    if (empty($security_question))$errors[] = "Security question is required.";
    if (empty($security_answer))  $errors[] = "Security answer is required.";

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) $errors[] = "An account with this email already exists.";

    if (empty($errors)) {
        $hashed_password    = hashPassword($password);
        $encrypted_contact  = encryptData($contact_number);
        $encrypted_address  = encryptData($address);
        $encrypted_birthdate= encryptData($birthdate);
        $hashed_answer      = hashPassword($security_answer);

        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, contact_number, address, birthdate, security_question, security_answer) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$full_name, $email, $hashed_password, $encrypted_contact, $encrypted_address, $encrypted_birthdate, $security_question, $hashed_answer])) {
            logAudit($pdo->lastInsertId(), 'registration', 'success');
            header('Location: login.php?success=1');
            exit;
        } else {
            $errors[] = "Registration failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SecureVault — Create Account</title>
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
            align-items: flex-start;
            justify-content: center;
            padding: 40px 24px;
            position: relative;
            overflow-x: hidden;
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
            width: min(100%, 560px);
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

        .alert-error {
            background: var(--danger-bg);
            border: 1px solid rgba(239,68,68,0.25);
            color: #fca5a5;
            border-radius: 12px;
            padding: 14px 16px;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 24px;
            line-height: 1.5;
        }

        .alert-error ul {
            padding-left: 18px;
            margin: 0;
            line-height: 1.8;
        }

        .alert-success {
            background: var(--success-bg);
            border: 1px solid rgba(16,185,129,0.25);
            color: #6ee7b7;
            border-radius: 12px;
            padding: 14px 16px;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 24px;
            line-height: 1.5;
        }

        .section-label {
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--accent);
            margin: 28px 0 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-label::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .field { margin-bottom: 16px; }
        .field.full { grid-column: 1 / -1; }

        label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 8px;
        }

        .input-wrap { position: relative; }

        .input-wrap svg {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            width: 16px; height: 16px;
            color: var(--muted);
            pointer-events: none;
        }

        .input-wrap.textarea-wrap svg { top: 16px; transform: none; }

        input, textarea, select {
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

        textarea {
            resize: vertical;
            min-height: 90px;
            padding-top: 13px;
        }

        input::placeholder, textarea::placeholder { color: #475569; }

        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(59,130,246,0.15);
        }

        /* Password strength meter */
        .strength-bar {
            height: 4px;
            border-radius: 2px;
            background: #1e293b;
            margin-top: 8px;
            overflow: hidden;
        }

        .strength-fill {
            height: 100%;
            border-radius: 2px;
            width: 0%;
            transition: width 0.3s ease, background 0.3s ease;
        }

        .strength-text {
            font-size: 0.75rem;
            margin-top: 6px;
            font-weight: 500;
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
            margin-top: 24px;
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

        .login-link {
            text-align: center;
            color: var(--muted);
            font-size: 0.875rem;
        }

        .login-link a { color: var(--accent); font-weight: 600; text-decoration: none; transition: color 0.2s; }
        .login-link a:hover { text-decoration: underline; }

        .security-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: 24px;
            color: #334155;
            font-size: 0.75rem;
        }

        .security-badge svg { width: 12px; height: 12px; }

        /* Responsive Design */
        /* Tablet: 560px - 899px */
        @media (max-width: 899px) and (min-width: 560px) {
            body { padding: 32px 20px; }
            .card { padding: 40px 32px; }
            .grid-2 { gap: 14px; }
        }

        /* Mobile: < 560px */
        @media (max-width: 560px) {
            body { padding: 24px 16px; }
            .card { 
                padding: 32px 24px; 
                border-radius: 20px;
            }
            
            /* Stack form fields vertically */
            .grid-2 { 
                grid-template-columns: 1fr; 
                gap: 16px;
            }
            
            h1 { font-size: 1.5rem; }
            .subtitle { font-size: 0.85rem; }
            .brand { margin-bottom: 28px; }
            .brand-icon { width: 40px; height: 40px; }
            .brand-icon svg { width: 20px; height: 20px; }
            .brand-name { font-size: 1rem; }
            
            /* Ensure minimum touch target sizes (44x44px) */
            input, select { 
                padding: 15px 14px 15px 42px; 
                font-size: 16px; /* Prevents zoom on iOS */
                min-height: 44px;
            }
            
            select { padding-left: 42px; }
            
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
            
            /* Password strength meter adjustments */
            .strength-meter { margin-top: 10px; }
            .strength-label { font-size: 0.75rem; }
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

        <h1>Create account</h1>
        <p class="subtitle">Your data is encrypted end-to-end. Fill in your details to get started.</p>

        <?php if (!empty($errors)): ?>
        <div class="alert-error">
            <ul><?php foreach ($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>"; ?></ul>
        </div>
        <?php endif; ?>

        <form method="post" autocomplete="on">

            <div class="section-label">Personal Info</div>
            <div class="grid-2">
                <div class="field full">
                    <label for="full_name">Full Name</label>
                    <div class="input-wrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        <input type="text" id="full_name" name="full_name" placeholder="Jane Doe" autocomplete="name" required>
                    </div>
                </div>

                <div class="field full">
                    <label for="email">Email Address</label>
                    <div class="input-wrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        <input type="email" id="email" name="email" placeholder="you@example.com" autocomplete="email" required>
                    </div>
                </div>

                <div class="field">
                    <label for="contact_number">Phone Number</label>
                    <div class="input-wrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        <input type="text" id="contact_number" name="contact_number" placeholder="+1 555 000 0000" autocomplete="tel" required>
                    </div>
                </div>

                <div class="field">
                    <label for="birthdate">Date of Birth</label>
                    <div class="input-wrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        <input type="date" id="birthdate" name="birthdate" autocomplete="bday" required>
                    </div>
                </div>

                <div class="field full">
                    <label for="address">Address</label>
                    <div class="input-wrap textarea-wrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        <textarea id="address" name="address" placeholder="123 Main St, City, Country" autocomplete="street-address" required></textarea>
                    </div>
                </div>
            </div>

            <div class="section-label">Security</div>
            <div class="grid-2">
                <div class="field full">
                    <label for="password">Password</label>
                    <div class="input-wrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        <input type="password" id="password" name="password" placeholder="Min 8 chars, upper, lower, number" autocomplete="new-password" required oninput="checkStrength(this.value)">
                    </div>
                    <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
                    <div class="strength-text" id="strengthText">Enter a password</div>
                </div>

                <div class="field full">
                    <label for="security_question">Security Question</label>
                    <div class="input-wrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        <input type="text" id="security_question" name="security_question" placeholder="e.g. What was your first pet's name?" autocomplete="off" required>
                    </div>
                </div>

                <div class="field full">
                    <label for="security_answer">Security Answer</label>
                    <div class="input-wrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        <input type="password" id="security_answer" name="security_answer" placeholder="Your answer (stored encrypted)" autocomplete="off" required>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn" id="submitBtn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                Create Secure Account
            </button>
        </form>

        <div class="divider"></div>

        <div class="login-link">
            Already have an account? <a href="login.php">Sign in</a>
        </div>

        <div class="security-badge">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
            All sensitive data is encrypted before storage
        </div>
    </div>

    <script>
        function checkStrength(val) {
            const fill = document.getElementById('strengthFill');
            const text = document.getElementById('strengthText');
            
            if (!fill || !text) return;
            
            let score = 0;
            
            // Calculate strength score
            if (val.length >= 8) score++;
            if (/[A-Z]/.test(val)) score++;
            if (/[a-z]/.test(val)) score++;
            if (/\d/.test(val)) score++;
            if (/[^A-Za-z0-9]/.test(val)) score++;

            // Define strength levels with colors matching design system
            const levels = [
                { w: '0%',   c: '#ef4444', t: 'Too short' },
                { w: '25%',  c: '#ef4444', t: 'Weak' },
                { w: '50%',  c: '#f59e0b', t: 'Fair' },
                { w: '75%',  c: '#3b82f6', t: 'Good' },
                { w: '100%', c: '#10b981', t: 'Strong' },
            ];
            
            const level = levels[score] || levels[0];
            
            // Apply styles with smooth transitions
            fill.style.width = level.w;
            fill.style.background = level.c;
            text.textContent = level.t;
            text.style.color = level.c;
        }

        // Form submission loading state
        document.querySelector('form').addEventListener('submit', function(e) {
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.classList.add('btn-loading');
            btn.textContent = 'Creating account...';
        });
    </script>
</body>
</html>
