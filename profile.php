<?php
require 'config.php';
requireLogin();

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update'])) {
        $security_answer = sanitizeInput($_POST['security_answer']);
        $stmt = $pdo->prepare("SELECT security_answer FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $stored_answer = $stmt->fetch()['security_answer'];
        if (verifyPassword($security_answer, $stored_answer)) {
            $full_name      = sanitizeInput($_POST['full_name']);
            $email          = sanitizeInput($_POST['email']);
            $contact_number = sanitizeInput($_POST['contact_number']);
            $address        = sanitizeInput($_POST['address']);
            $birthdate      = sanitizeInput($_POST['birthdate']);

            $encrypted_contact  = encryptData($contact_number);
            $encrypted_address  = encryptData($address);
            $encrypted_birthdate= encryptData($birthdate);

            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, contact_number = ?, address = ?, birthdate = ? WHERE id = ?");
            if ($stmt->execute([$full_name, $email, $encrypted_contact, $encrypted_address, $encrypted_birthdate, $user_id])) {
                logAudit($user_id, 'update', 'success');
                $success = "Profile updated successfully.";
            } else {
                $error = "Update failed. Please try again.";
            }
        } else {
            $error = "Invalid security answer.";
        }
    } elseif (isset($_POST['delete'])) {
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
$decrypted_contact  = decryptData($user['contact_number']);
$decrypted_address  = decryptData($user['address']);
$decrypted_birthdate= decryptData($user['birthdate']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SecureVault — Profile</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:        #0a0e1a;
            --surface:   #111827;
            --surface2:  #1a2235;
            --border:    rgba(99,179,237,0.1);
            --border-hi: rgba(99,179,237,0.25);
            --accent:    #3b82f6;
            --accent2:   #6366f1;
            --glow:      rgba(59,130,246,0.2);
            --text:      #f1f5f9;
            --muted:     #94a3b8;
            --success:   #10b981;
            --danger:    #ef4444;
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(59,130,246,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(59,130,246,0.03) 1px, transparent 1px);
            background-size: 48px 48px;
            pointer-events: none;
        }

        .sidebar {
            position: fixed;
            top: 0; left: 0;
            width: 240px;
            height: 100vh;
            background: var(--surface);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            padding: 28px 0;
            z-index: 10;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0 24px 28px;
            border-bottom: 1px solid var(--border);
        }

        .brand-icon {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 0 16px var(--glow);
        }

        .brand-icon svg { width: 18px; height: 18px; fill: white; }
        .brand-name { font-size: 1rem; font-weight: 700; letter-spacing: -0.02em; }
        .brand-name span { color: var(--accent); }

        .nav { flex: 1; padding: 20px 12px; display: flex; flex-direction: column; gap: 4px; }

        .nav-label {
            font-size: 0.65rem; font-weight: 700; letter-spacing: 0.1em;
            text-transform: uppercase; color: #334155;
            padding: 0 12px; margin: 12px 0 6px;
        }

        .nav-item {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 12px; border-radius: 10px;
            color: var(--muted); text-decoration: none;
            font-size: 0.875rem; font-weight: 500;
            transition: background 0.15s, color 0.15s;
        }

        .nav-item svg { width: 16px; height: 16px; flex-shrink: 0; }
        .nav-item:hover { background: var(--surface2); color: var(--text); }
        .nav-item.active { background: rgba(59,130,246,0.12); color: var(--accent); border: 1px solid rgba(59,130,246,0.2); }

        .sidebar-footer { padding: 20px 12px 0; border-top: 1px solid var(--border); }

        .user-chip {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 12px; border-radius: 10px;
            background: var(--surface2); border: 1px solid var(--border);
        }

        .avatar {
            width: 32px; height: 32px;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.8rem; font-weight: 700; color: white; flex-shrink: 0;
        }

        .user-info { flex: 1; min-width: 0; }
        .user-name { font-size: 0.8rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .user-role { font-size: 0.7rem; color: var(--muted); }

        .main { margin-left: 240px; padding: 40px; position: relative; z-index: 1; }

        .topbar {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 36px;
        }

        .page-title { font-size: 1.5rem; font-weight: 800; letter-spacing: -0.03em; }
        .page-title span { color: var(--muted); font-weight: 400; }

        .back-btn {
            display: flex; align-items: center; gap: 7px;
            padding: 9px 16px; background: transparent;
            border: 1px solid var(--border); border-radius: 10px;
            color: var(--muted); font-size: 0.8rem; font-weight: 600;
            text-decoration: none; transition: border-color 0.2s, color 0.2s;
        }

        .back-btn svg { width: 14px; height: 14px; }
        .back-btn:hover { border-color: var(--border-hi); color: var(--text); }

        .layout { display: grid; grid-template-columns: 1fr 340px; gap: 24px; align-items: start; }

        .panel {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 18px;
            overflow: hidden;
        }

        .panel-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; gap: 10px;
        }

        .panel-icon {
            width: 34px; height: 34px; border-radius: 9px;
            display: flex; align-items: center; justify-content: center;
        }

        .panel-icon svg { width: 15px; height: 15px; }
        .panel-icon.blue   { background: rgba(59,130,246,0.12); color: var(--accent); }
        .panel-icon.red    { background: rgba(239,68,68,0.1);   color: var(--danger); }

        .panel-title { font-size: 0.9rem; font-weight: 700; }
        .panel-sub   { font-size: 0.75rem; color: var(--muted); }

        .panel-body { padding: 24px; }

        .alert {
            display: flex; align-items: flex-start; gap: 10px;
            padding: 12px 14px; border-radius: 10px;
            font-size: 0.85rem; font-weight: 500;
            margin-bottom: 20px; line-height: 1.5;
        }

        .alert svg { width: 15px; height: 15px; flex-shrink: 0; margin-top: 1px; }
        .alert-success { background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.2); color: #6ee7b7; }
        .alert-error   { background: rgba(239,68,68,0.1);  border: 1px solid rgba(239,68,68,0.2);  color: #fca5a5; }

        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

        .field { margin-bottom: 0; }
        .field.full { grid-column: 1 / -1; }

        label {
            display: block;
            font-size: 0.75rem; font-weight: 600;
            letter-spacing: 0.05em; text-transform: uppercase;
            color: var(--muted); margin-bottom: 7px;
        }

        .input-wrap { position: relative; }

        .input-wrap svg {
            position: absolute; left: 12px; top: 50%;
            transform: translateY(-50%);
            width: 14px; height: 14px; color: #475569; pointer-events: none;
        }

        .input-wrap.textarea-wrap svg { top: 14px; transform: none; }

        input, textarea {
            width: 100%;
            padding: 11px 11px 11px 36px;
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text);
            font-size: 0.875rem;
            font-family: inherit;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        textarea { resize: vertical; min-height: 80px; padding-top: 11px; }
        input::placeholder, textarea::placeholder { color: #334155; }

        input:focus, textarea:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }

        .security-q {
            background: rgba(59,130,246,0.06);
            border: 1px solid rgba(59,130,246,0.15);
            border-radius: 10px;
            padding: 12px 14px;
            font-size: 0.85rem;
            color: #93c5fd;
            margin-bottom: 16px;
            display: flex; align-items: center; gap: 8px;
        }

        .security-q svg { width: 14px; height: 14px; flex-shrink: 0; }

        .btn {
            width: 100%;
            padding: 12px;
            border: none; border-radius: 10px;
            font-size: 0.875rem; font-weight: 700;
            font-family: inherit; cursor: pointer;
            transition: opacity 0.2s, transform 0.15s;
            margin-top: 20px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            color: white;
            box-shadow: 0 4px 16px var(--glow);
        }

        .btn-primary:hover { opacity: 0.9; transform: translateY(-1px); }

        .btn-danger {
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.25);
            color: #fca5a5;
        }

        .btn-danger:hover { background: rgba(239,68,68,0.18); }

        .danger-warning {
            background: rgba(239,68,68,0.06);
            border: 1px solid rgba(239,68,68,0.15);
            border-radius: 10px;
            padding: 12px 14px;
            font-size: 0.8rem;
            color: #fca5a5;
            margin-bottom: 16px;
            line-height: 1.6;
        }

        @media (max-width: 1100px) { .layout { grid-template-columns: 1fr; } }
        @media (max-width: 900px)  { .sidebar { display: none; } .main { margin-left: 0; padding: 24px; } }
        @media (max-width: 560px)  { .grid-2 { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon">
                <svg viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
            </div>
            <div class="brand-name">Secure<span>Vault</span></div>
        </div>
        <nav class="nav">
            <div class="nav-label">Main</div>
            <a href="dashboard.php" class="nav-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                Dashboard
            </a>
            <a href="profile.php" class="nav-item active">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                My Profile
            </a>
        </nav>
        <div class="sidebar-footer">
            <div class="user-chip">
                <div class="avatar"><?php echo strtoupper(substr($user['full_name'], 0, 1)); ?></div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                    <div class="user-role"><?php echo htmlspecialchars($_SESSION['role']); ?></div>
                </div>
            </div>
        </div>
    </aside>

    <main class="main">
        <div class="topbar">
            <div class="page-title">My Profile <span>/ Settings</span></div>
            <a href="dashboard.php" class="back-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
                Back
            </a>
        </div>

        <div class="layout">
            <!-- Update form -->
            <div class="panel">
                <div class="panel-header">
                    <div class="panel-icon blue">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </div>
                    <div>
                        <div class="panel-title">Personal Information</div>
                        <div class="panel-sub">Update your profile details</div>
                    </div>
                </div>
                <div class="panel-body">
                    <?php if (isset($success)): ?>
                    <div class="alert alert-success">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                    <?php endif; ?>
                    <?php if (isset($error)): ?>
                    <div class="alert alert-error">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="grid-2">
                            <div class="field full">
                                <label for="full_name">Full Name</label>
                                <div class="input-wrap">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                    <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                </div>
                            </div>
                            <div class="field full">
                                <label for="email">Email Address</label>
                                <div class="input-wrap">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                            </div>
                            <div class="field">
                                <label for="contact_number">Phone Number</label>
                                <div class="input-wrap">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                    <input type="text" id="contact_number" name="contact_number" value="<?php echo htmlspecialchars($decrypted_contact); ?>" required>
                                </div>
                            </div>
                            <div class="field">
                                <label for="birthdate">Date of Birth</label>
                                <div class="input-wrap">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                    <input type="date" id="birthdate" name="birthdate" value="<?php echo htmlspecialchars($decrypted_birthdate); ?>" required>
                                </div>
                            </div>
                            <div class="field full">
                                <label for="address">Address</label>
                                <div class="input-wrap textarea-wrap">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                    <textarea id="address" name="address" required><?php echo htmlspecialchars($decrypted_address); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="security-q" style="margin-top:20px;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                            Security question: <strong><?php echo htmlspecialchars($user['security_question']); ?></strong>
                        </div>

                        <div class="field">
                            <label for="security_answer">Security Answer (required to save)</label>
                            <div class="input-wrap">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                <input type="password" id="security_answer" name="security_answer" placeholder="Your security answer" required>
                            </div>
                        </div>

                        <button type="submit" name="update" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
            </div>

            <!-- Danger zone -->
            <div class="panel">
                <div class="panel-header">
                    <div class="panel-icon red">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                    </div>
                    <div>
                        <div class="panel-title">Danger Zone</div>
                        <div class="panel-sub">Irreversible actions</div>
                    </div>
                </div>
                <div class="panel-body">
                    <div class="danger-warning">
                        Deleting your account is permanent and cannot be undone. All your data will be removed immediately.
                    </div>
                    <form method="post" onsubmit="return confirm('Are you absolutely sure? This cannot be undone.')">
                        <div class="field">
                            <label for="security_answer_delete">Confirm with security answer</label>
                            <div class="input-wrap">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                <input type="password" id="security_answer_delete" name="security_answer_delete" placeholder="Your security answer" required>
                            </div>
                        </div>
                        <button type="submit" name="delete" class="btn btn-danger">Delete My Account</button>
                    </form>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
