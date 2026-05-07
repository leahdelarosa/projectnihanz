<?php
require 'config.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

$stmt = $pdo->prepare("SELECT full_name, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <style>
        :root {
            --bg: #eef2ff;
            --header: linear-gradient(135deg, #4f46e5, #8b5cf6);
            --surf: #ffffff;
            --text: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;
            --accent: #4338ca;
            --accent-soft: #c7d2fe;
        }
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: radial-gradient(circle at top left, rgba(99, 102, 241, 0.2), transparent 24%), var(--bg);
            color: var(--text);
            padding: 24px;
        }
        .container {
            width: min(100%, 1080px);
            margin: 0 auto;
            display: grid;
            gap: 24px;
        }
        .header {
            background: var(--header);
            color: white;
            padding: 36px 32px;
            border-radius: 32px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 32px 80px rgba(15, 23, 42, 0.15);
        }
        .header::after {
            content: '';
            position: absolute;
            width: 260px;
            height: 260px;
            background: rgba(255, 255, 255, 0.14);
            border-radius: 50%;
            top: -60px;
            right: -60px;
            pointer-events: none;
            z-index: 0;
        }
        .header h1 {
            margin: 0;
            font-size: clamp(2rem, 2.8vw, 3rem);
            line-height: 1.05;
            position: relative;
            z-index: 1;
        }
        .header p {
            margin: 14px 0 0;
            color: rgba(255,255,255,0.8);
            font-size: 1rem;
            max-width: 620px;
            position: relative;
            z-index: 1;
        }
        .header .logout {
            position: absolute;
            top: 24px;
            right: 28px;
            z-index: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: rgba(255,255,255,0.15);
            padding: 12px 18px;
            border-radius: 999px;
            backdrop-filter: blur(8px);
            color: white;
            text-decoration: none;
            border: 1px solid rgba(255,255,255,0.28);
        }
        .header .logout:hover {
            background: rgba(255,255,255,0.24);
        }
        .grid {
            display: grid;
            gap: 24px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .card {
            background: var(--surf);
            padding: 28px;
            border-radius: 28px;
            box-shadow: 0 20px 50px rgba(15, 23, 42, 0.08);
            border: 1px solid rgba(148, 163, 184, 0.16);
        }
        .card h2 {
            margin-top: 0;
            margin-bottom: 12px;
            font-size: 1.2rem;
        }
        .card p {
            margin: 0 0 18px;
            color: var(--muted);
            line-height: 1.75;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 20px;
            border-radius: 16px;
            text-decoration: none;
            font-weight: 700;
            color: white;
            background: linear-gradient(135deg, var(--accent), #7c3aed);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 18px 34px rgba(67, 56, 202, 0.22);
        }
        @media (max-width: 860px) {
            .grid {
                grid-template-columns: 1fr;
            }
            .header {
                padding: 28px 24px;
            }
            .header .logout {
                position: static;
                margin-top: 18px;
                width: fit-content;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Welcome, <?php echo htmlspecialchars($user['full_name']); ?> (<?php echo ucfirst($role); ?>)</h1>
            <a href="logout.php" class="btn logout">Logout</a>
        </div>
        <div class="card">
            <h2>Profile Management</h2>
            <p>View and update your personal information.</p>
            <a href="profile.php" class="btn">View Profile</a>
        </div>
        <?php if ($role == 'admin') { ?>
        <div class="card">
            <h2>Admin Panel</h2>
            <p>Access administrative features and audit logs.</p>
            <a href="admin.php" class="btn">Admin Panel</a>
        </div>
        <?php } ?>
    </div>
</body>
</html>