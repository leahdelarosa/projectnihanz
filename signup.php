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
            --accent:    #3b82f6;
            --accent2:   #6366f1;
            --glow:      rgba(59,130,246,0.22);
            --text:      #f1f5f9;
            --muted:     #94a3b8;
            --danger:    #ef4444;
            --danger-bg: rgba(239,68,68,0.1);
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

        .orb1 {
            position: fixed;
            width: 700px; height: 700px;
            background: radial-gradient(circle, rgba(59,130,246,0.07) 0%, transparent 70%);
            top: -300px; right: -200px;
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
        }

        .brand-name span { color: var(--accent); }

        h1 {
            font-size: 1.75rem;
            font-weight: 800;
            letter-spacing: -0.03em;
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
            margin-bottom: 24px;
        }

        .alert-error ul {
            padding-left: 18px;
            margin: 0;
            line-height: 1.8;
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
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 7px;
        }

        .input-wrap { position: relative; }

        .input-wrap svg {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            width: 15px; height: 15px;
            color: #475569;
            pointer-events: none;
        }

        .input-wrap.textarea-wrap svg { top: 16px; transform: none; }

        input, textarea, select {
            width: 100%;
            padding: 12px 12px 12px 40px;
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 11px;
            color: var(--text);
            font-size: 0.9rem;
            font-family: inherit;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        textarea {
            resize: vertical;
            min-height: 90px;
            padding-top: 12px;
        }

        input::placeholder, textarea::placeholder { color: #334155; }

        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(59,130,246,0.12);
        }

        /* Password strength */
        .strength-bar {
            height: 3px;
            border-radius: 2px;
            background: #1e293b;
            margin-top: 8px;
            overflow: hidden;
        }

        .strength-fill {
            height: 100%;
            border-radius: 2px;
            width: 0%;
            transition: width 0.3s, background 0.3s;
        }

        .strength-text {
            font-size: 0.75rem;
            color: var(--muted);
            margin-top: 4px;
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
        }

        .btn:hover { opacity: 0.92; transform: translateY(-1px); box-shadow: 0 8px 30px var(--glow); }
        .btn:active { transform: translateY(0); }

        .divider { height: 1px; background: var(--border); margin: 28px 0; }

        .login-link {
            text-align: center;
            color: var(--muted);
            font-size: 0.875rem;
        }

        .login-link a { color: var(--accent); font-weight: 600; text-decoration: none; }
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

        @media (max-width: 520px) {
            .card { padding: 32px 24px; }
            .grid-2 { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="orb1"></div>
    <div class="card">
        <div class="brand">
            <div class="brand-icon">
                <svg viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
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

            <button type="submit" class="btn">Create Secure Account</button>
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
            let score = 0;
            if (val.length >= 8) score++;
            if (/[A-Z]/.test(val)) score++;
            if (/[a-z]/.test(val)) score++;
            if (/\d/.test(val)) score++;
            if (/[^A-Za-z0-9]/.test(val)) score++;

            const levels = [
                { w: '0%',   c: '#ef4444', t: 'Too short' },
                { w: '25%',  c: '#ef4444', t: 'Weak' },
                { w: '50%',  c: '#f59e0b', t: 'Fair' },
                { w: '75%',  c: '#3b82f6', t: 'Good' },
                { w: '100%', c: '#10b981', t: 'Strong' },
            ];
            const l = levels[score] || levels[0];
            fill.style.width = l.w;
            fill.style.background = l.c;
            text.textContent = l.t;
            text.style.color = l.c;
        }
    </script>
</body>
</html>
