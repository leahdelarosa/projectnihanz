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
        logAudit(null, 'login', 'failed'); // For failed, user_id null
        $error = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        :root {
            --bg-start: #4f46e5;
            --bg-end: #7c3aed;
            --panel: rgba(255, 255, 255, 0.96);
            --text: #111827;
            --muted: #6b7280;
            --border: #e5e7eb;
            --accent: #6d28d9;
            --accent-hover: #5b21b6;
            --success: #d1fae5;
            --success-text: #065f46;
            --error: #fee2e2;
            --error-text: #991b1b;
        }
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, var(--bg-start), var(--bg-end));
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            color: var(--text);
        }
        .container {
            width: min(100%, 520px);
            background: var(--panel);
            border-radius: 32px;
            padding: 36px 32px;
            box-shadow: 0 34px 100px rgba(15, 23, 42, 0.16);
            border: 1px solid rgba(255,255,255,0.4);
            backdrop-filter: blur(10px);
        }
        h1 {
            font-size: clamp(2rem, 2.4vw, 2.5rem);
            margin: 0;
        }
        p.subtitle {
            margin: 12px 0 28px;
            color: var(--muted);
            font-size: 0.98rem;
        }
        .message {
            border-radius: 18px;
            padding: 16px 18px;
            margin-bottom: 22px;
            line-height: 1.5;
            font-weight: 500;
        }
        .error {
            background: var(--error);
            color: var(--error-text);
        }
        .success {
            background: var(--success);
            color: var(--success-text);
        }
        form {
            display: grid;
            gap: 18px;
        }
        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #334155;
        }
        input {
            width: 100%;
            padding: 16px 18px;
            border: 1px solid var(--border);
            background: #f8fafc;
            border-radius: 16px;
            font-size: 1rem;
            color: var(--text);
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        input:focus {
            outline: none;
            border-color: rgba(109, 40, 217, 0.4);
            box-shadow: 0 0 0 4px rgba(109, 40, 217, 0.12);
        }
        button {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 16px;
            font-size: 1rem;
            font-weight: 700;
            color: white;
            background: linear-gradient(135deg, var(--accent), var(--accent-hover));
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 12px 30px rgba(79, 70, 229, 0.18);
        }
        button:hover {
            transform: translateY(-1px);
            box-shadow: 0 18px 40px rgba(79, 70, 229, 0.2);
        }
        .link {
            text-align: center;
            margin-top: 16px;
            color: var(--muted);
            font-size: 0.95rem;
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
        <h1>Login</h1>
        <p class="subtitle">Access your account safely using your registered email and password.</p>
        <?php if (isset($error)) echo "<div class='message error'>$error</div>"; ?>
        <?php if (isset($_GET['success'])) echo "<div class='message success'>Registration successful. Please login.</div>"; ?>
        <?php if (isset($_GET['deleted'])) echo "<div class='message success'>Account deleted successfully.</div>"; ?>
        <form method="post">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Login</button>
        </form>
        <div class="link">
            <a href="forgot_password.php">Forgot password?</a><br>
            <a href="signup.php">Don't have an account? Sign Up</a>
        </div>
    </div>
</body>
</html>