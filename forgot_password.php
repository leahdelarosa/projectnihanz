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
            $security_answer = sanitizeInput($_POST['security_answer']);
            $new_password = $_POST['new_password'];
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
                $error = "Password must be at least 8 characters with uppercase, lowercase, and number.";
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
                        $success = "Your password has been reset successfully. You can now log in with your new password.";
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
    <title>Forgot Password</title>
    <style>
        :root {
            --bg-start: #4f46e5;
            --bg-end: #9333ea;
            --panel: rgba(255, 255, 255, 0.96);
            --text: #111827;
            --muted: #6b7280;
            --border: #e5e7eb;
            --accent: #7c3aed;
            --accent-hover: #6d28d9;
            --success: #ecfdf5;
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
            padding: 36px 32px;
            background: var(--panel);
            border-radius: 32px;
            box-shadow: 0 34px 100px rgba(15, 23, 42, 0.16);
            border: 1px solid rgba(255,255,255,0.35);
            backdrop-filter: blur(10px);
        }
        h1 {
            margin: 0;
            font-size: clamp(2rem, 2.4vw, 2.6rem);
            letter-spacing: -0.03em;
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
            border-radius: 16px;
            background: #f8fafc;
            color: var(--text);
            font-size: 1rem;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        input:focus {
            outline: none;
            border-color: rgba(124, 58, 237, 0.4);
            box-shadow: 0 0 0 4px rgba(124, 58, 237, 0.12);
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
            box-shadow: 0 12px 30px rgba(124, 58, 237, 0.2);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        button:hover {
            transform: translateY(-1px);
            box-shadow: 0 18px 40px rgba(124, 58, 237, 0.24);
        }
        .link {
            text-align: center;
            margin-top: 16px;
            color: var(--muted);
        }
        .link a {
            color: var(--accent);
            font-weight: 600;
            text-decoration: none;
        }
        .link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Forgot Password</h1>
        <p class="subtitle">Reset your password by answering your security question and setting a new one.</p>
        <?php if ($error) echo "<div class='message error'>" . htmlspecialchars($error) . "</div>"; ?>
        <?php if ($success) echo "<div class='message success'>" . htmlspecialchars($success) . "</div>"; ?>

        <?php if ($step === 1): ?>
            <form method="post">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" autocomplete="email" required>
                <button type="submit" name="send_email">Continue</button>
            </form>
        <?php else: ?>
            <form method="post">
                <label>Security Question</label>
                <input type="text" value="<?php echo htmlspecialchars($security_question); ?>" disabled>

                <label for="security_answer">Security Answer</label>
                <input type="password" id="security_answer" name="security_answer" autocomplete="off" required>

                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" autocomplete="new-password" required>

                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password" required>

                <button type="submit" name="reset_password">Reset Password</button>
            </form>
        <?php endif; ?>

        <div class="link">
            <a href="login.php">Back to Login</a>
        </div>
    </div>
</body>
</html>
