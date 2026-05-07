<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = sanitizeInput($_POST['full_name']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $contact_number = sanitizeInput($_POST['contact_number']);
    $address = sanitizeInput($_POST['address']);
    $birthdate = sanitizeInput($_POST['birthdate']);
    $security_question = sanitizeInput($_POST['security_question']);
    $security_answer = sanitizeInput($_POST['security_answer']);

    $errors = [];

    if (empty($full_name)) $errors[] = "Full name is required.";
    if (!validateEmail($email)) $errors[] = "Invalid email.";
    if (!validatePassword($password)) $errors[] = "Password must be at least 8 characters with uppercase, lowercase, and number.";
    if (empty($contact_number)) $errors[] = "Contact number is required.";
    if (empty($address)) $errors[] = "Address is required.";
    if (empty($birthdate)) $errors[] = "Birthdate is required.";
    if (empty($security_question)) $errors[] = "Security question is required.";
    if (empty($security_answer)) $errors[] = "Security answer is required.";

    // Check if email exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) $errors[] = "Email already exists.";

    if (empty($errors)) {
        $hashed_password = hashPassword($password);
        $encrypted_contact = encryptData($contact_number);
        $encrypted_address = encryptData($address);
        $encrypted_birthdate = encryptData($birthdate);
        $hashed_answer = hashPassword($security_answer);

        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, contact_number, address, birthdate, security_question, security_answer) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$full_name, $email, $hashed_password, $encrypted_contact, $encrypted_address, $encrypted_birthdate, $security_question, $hashed_answer])) {
            logAudit($pdo->lastInsertId(), 'registration', 'success');
            header('Location: login.php?success=1');
            exit;
        } else {
            $errors[] = "Registration failed.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
    <style>
        :root {
            --bg-start: #5c6bf0;
            --bg-end: #7f53ff;
            --panel: rgba(255, 255, 255, 0.95);
            --text: #1f2937;
            --muted: #6b7280;
            --border: #dbeafe;
            --accent: #4f46e5;
            --accent-hover: #4338ca;
            --error: #fef2f2;
            --error-text: #b91c1c;
        }
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: radial-gradient(circle at top, rgba(255,255,255,0.15), transparent 40%), linear-gradient(135deg, var(--bg-start), var(--bg-end));
            color: var(--text);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .container {
            width: min(100%, 520px);
            background: var(--panel);
            border-radius: 32px;
            box-shadow: 0 34px 120px rgba(15, 23, 42, 0.15);
            padding: 36px 32px;
            border: 1px solid rgba(255,255,255,0.35);
            backdrop-filter: blur(12px);
        }
        h1 {
            font-size: clamp(2rem, 2.5vw, 2.6rem);
            margin: 0 0 8px;
            line-height: 1.05;
        }
        p.subtitle {
            color: var(--muted);
            margin: 0 0 28px;
            font-size: 0.98rem;
        }
        .error-list {
            background: var(--error);
            color: var(--error-text);
            padding: 16px 18px;
            border-radius: 18px;
            margin-bottom: 22px;
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.6);
        }
        form {
            display: grid;
            gap: 18px;
        }
        label {
            font-weight: 600;
            display: block;
            margin-bottom: 6px;
            color: #334155;
        }
        input, textarea {
            width: 100%;
            padding: 16px 18px;
            border: 1px solid var(--border);
            border-radius: 16px;
            background: #f8fbff;
            font-size: 1rem;
            color: var(--text);
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        textarea {
            resize: vertical;
            min-height: 100px;
        }
        input:focus, textarea:focus {
            outline: none;
            border-color: rgba(79, 70, 229, 0.45);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.12);
        }
        button {
            width: 100%;
            border: none;
            border-radius: 16px;
            padding: 16px;
            font-size: 1rem;
            font-weight: 700;
            color: white;
            background: linear-gradient(135deg, var(--accent), var(--accent-hover));
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 14px 30px rgba(79, 70, 229, 0.18);
        }
        button:hover {
            transform: translateY(-1px);
            box-shadow: 0 18px 40px rgba(79, 70, 229, 0.2);
        }
        .link {
            text-align: center;
            margin-top: 12px;
            font-size: 0.95rem;
            color: var(--muted);
        }
        .link a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
        }
        .link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Sign Up</h1>
        <p class="subtitle">Create your secure account with a strong password and recovery details.</p>
        <?php if (!empty($errors)) { echo '<div class="error-list"><ul>'; foreach ($errors as $error) echo "<li>$error</li>"; echo '</ul></div>'; } ?>
        <form method="post">
            <label for="full_name">Full Name</label>
            <input type="text" id="full_name" name="full_name" autocomplete="name" required>

            <label for="email">Email</label>
            <input type="email" id="email" name="email" autocomplete="email" required>

            <label for="password">Password</label>
            <input type="password" id="password" name="password" autocomplete="new-password" required>

            <label for="contact_number">Contact Number</label>
            <input type="text" id="contact_number" name="contact_number" autocomplete="tel" required>

            <label for="address">Address</label>
            <textarea id="address" name="address" autocomplete="street-address" required></textarea>

            <label for="birthdate">Birthdate</label>
            <input type="date" id="birthdate" name="birthdate" autocomplete="bday" required>

            <label for="security_question">Security Question</label>
            <input type="text" id="security_question" name="security_question" autocomplete="off" required>

            <label for="security_answer">Security Answer</label>
            <input type="password" id="security_answer" name="security_answer" autocomplete="off" required>

            <button type="submit">Sign Up</button>
        </form>
        <div class="link">
            <a href="login.php">Already have an account? Login</a>
        </div>
    </div>
</body>
</html>