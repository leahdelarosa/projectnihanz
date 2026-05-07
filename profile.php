<?php
require 'config.php';
requireLogin();

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update'])) {
        // Verify security answer
        $security_answer = sanitizeInput($_POST['security_answer']);
        $stmt = $pdo->prepare("SELECT security_answer FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $stored_answer = $stmt->fetch()['security_answer'];
        if (verifyPassword($security_answer, $stored_answer)) {
            $full_name = sanitizeInput($_POST['full_name']);
            $email = sanitizeInput($_POST['email']);
            $contact_number = sanitizeInput($_POST['contact_number']);
            $address = sanitizeInput($_POST['address']);
            $birthdate = sanitizeInput($_POST['birthdate']);

            $encrypted_contact = encryptData($contact_number);
            $encrypted_address = encryptData($address);
            $encrypted_birthdate = encryptData($birthdate);

            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, contact_number = ?, address = ?, birthdate = ? WHERE id = ?");
            if ($stmt->execute([$full_name, $email, $encrypted_contact, $encrypted_address, $encrypted_birthdate, $user_id])) {
                logAudit($user_id, 'update', 'success');
                $success = "Profile updated.";
            } else {
                $error = "Update failed.";
            }
        } else {
            $error = "Invalid security answer.";
        }
    } elseif (isset($_POST['delete'])) {
        // For delete, perhaps require password or security answer
        $security_answer = sanitizeInput($_POST['security_answer_delete']);
        $stmt = $pdo->prepare("SELECT security_answer FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $stored_answer = $stmt->fetch()['security_answer'];
        if (verifyPassword($security_answer, $stored_answer)) {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            if ($stmt->execute([$user_id])) {
                logAudit($user_id, 'deletion', 'success');
                session_destroy();
                header('Location: login.php?deleted=1');
                exit;
            } else {
                $error = "Deletion failed.";
            }
        } else {
            $error = "Invalid security answer.";
        }
    }
}

$stmt = $pdo->prepare("SELECT full_name, email, contact_number, address, birthdate, security_question FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$decrypted_contact = decryptData($user['contact_number']);
$decrypted_address = decryptData($user['address']);
$decrypted_birthdate = decryptData($user['birthdate']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <style>
        :root {
            --bg: #eef2ff;
            --panel: #ffffff;
            --text: #111827;
            --muted: #64748b;
            --border: #e2e8f0;
            --accent: #4338ca;
            --accent-light: #6366f1;
            --success: #dcfce7;
            --success-text: #166534;
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
            background: radial-gradient(circle at top left, rgba(99, 102, 241, 0.18), transparent 24%), var(--bg);
            color: var(--text);
            padding: 24px;
        }
        .container {
            width: min(100%, 900px);
            margin: 0 auto;
            background: var(--panel);
            border-radius: 32px;
            padding: 36px;
            box-shadow: 0 30px 80px rgba(15, 23, 42, 0.12);
            border: 1px solid rgba(148, 163, 184, 0.2);
        }
        h1 {
            margin: 0 0 8px;
            font-size: clamp(2rem, 2.4vw, 2.4rem);
        }
        .message {
            border-radius: 20px;
            padding: 16px 20px;
            margin-bottom: 24px;
            line-height: 1.6;
            font-weight: 500;
        }
        .success {
            background: var(--success);
            color: var(--success-text);
        }
        .error {
            background: var(--error);
            color: var(--error-text);
        }
        form {
            display: grid;
            gap: 24px;
        }
        .form-grid {
            display: grid;
            gap: 20px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .form-group {
            display: grid;
            gap: 8px;
        }
        label {
            font-weight: 700;
            color: #334155;
        }
        input, textarea {
            width: 100%;
            padding: 16px 18px;
            border: 1px solid var(--border);
            border-radius: 18px;
            background: #f8fafc;
            color: var(--text);
            font-size: 1rem;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        textarea {
            min-height: 120px;
            resize: vertical;
        }
        input:focus, textarea:focus {
            outline: none;
            border-color: rgba(67, 56, 202, 0.42);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.12);
        }
        .full-width {
            grid-column: 1 / -1;
        }
        .button-row {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
        }
        button {
            border: none;
            border-radius: 18px;
            padding: 16px 22px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            color: white;
            background: linear-gradient(135deg, var(--accent-light), var(--accent));
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 14px 36px rgba(67, 56, 202, 0.18);
        }
        button:hover {
            transform: translateY(-1px);
            box-shadow: 0 18px 44px rgba(67, 56, 202, 0.2);
        }
        .delete-btn {
            background: #dc2626;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
            color: #4338ca;
            text-decoration: none;
            font-weight: 600;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        @media (max-width: 720px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Your Profile</h1>
        <p class="subtitle">Update your contact details and personal information securely from here.</p>
        <?php if (isset($success)) echo "<div class='message success'>$success</div>"; ?>
        <?php if (isset($error)) echo "<div class='message error'>$error</div>"; ?>
        <form method="post">
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>
            <div class="form-group">
                <label for="contact_number">Contact Number</label>
                <input type="text" id="contact_number" name="contact_number" value="<?php echo htmlspecialchars($decrypted_contact); ?>" required>
            </div>
            <div class="form-group">
                <label for="address">Address</label>
                <textarea id="address" name="address" required><?php echo htmlspecialchars($decrypted_address); ?></textarea>
            </div>
            <div class="form-group">
                <label for="birthdate">Birthdate</label>
                <input type="date" id="birthdate" name="birthdate" value="<?php echo htmlspecialchars($decrypted_birthdate); ?>" required>
            </div>
            <div class="form-group">
                <label>Security Question: <?php echo htmlspecialchars($user['security_question']); ?></label>
                <input type="password" name="security_answer" placeholder="Enter security answer to verify" required>
            </div>
            <button type="submit" name="update">Update Profile</button>
        </form>
        <hr>
        <h2>Delete Account</h2>
        <form method="post">
            <div class="form-group">
                <label for="security_answer_delete">Security Answer</label>
                <input type="password" id="security_answer_delete" name="security_answer_delete" required>
            </div>
            <button type="submit" name="delete" class="delete-btn" onclick="return confirm('Are you sure you want to delete your account?')">Delete Account</button>
        </form>
        <a href="dashboard.php" class="back-link">Back to Dashboard</a>
    </div>
</body>
</html>