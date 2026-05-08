<?php
require 'config.php';
requireRole('admin');

$logs = $pdo->query("SELECT a.id, u.full_name, a.action, a.timestamp, a.status FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.timestamp DESC")->fetchAll();

$total   = count($logs);
$success = count(array_filter($logs, fn($l) => $l['status'] === 'success'));
$failed  = count(array_filter($logs, fn($l) => $l['status'] === 'failed'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SecureVault — Admin Panel</title>
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

        /* Sidebar */
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
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #334155;
            padding: 0 12px;
            margin: 12px 0 6px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 10px;
            color: var(--muted);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            transition: background 0.15s, color 0.15s;
        }

        .nav-item svg { width: 16px; height: 16px; flex-shrink: 0; }
        .nav-item:hover { background: var(--surface2); color: var(--text); }
        .nav-item.active { background: rgba(59,130,246,0.12); color: var(--accent); border: 1px solid rgba(59,130,246,0.2); }

        .sidebar-footer { padding: 20px 12px 0; border-top: 1px solid var(--border); }

        .user-chip {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 10px;
            background: var(--surface2);
            border: 1px solid var(--border);
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

        /* Main */
        .main { 
            margin-left: 240px; 
            padding: 40px; 
            position: relative; 
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

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 36px;
        }

        .page-title { font-size: 1.5rem; font-weight: 800; letter-spacing: -0.03em; }
        .page-title span { color: var(--muted); font-weight: 400; }

        .back-btn {
            display: flex;
            align-items: center;
            gap: 7px;
            padding: 9px 16px;
            background: transparent;
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--muted);
            font-size: 0.8rem;
            font-weight: 600;
            text-decoration: none;
            transition: border-color 0.2s, color 0.2s;
        }

        .back-btn svg { width: 14px; height: 14px; }
        .back-btn:hover { border-color: var(--border-hi); color: var(--text); }

        /* Stats */
        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 20px 22px;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .stat-icon { width: 42px; height: 42px; border-radius: 11px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .stat-icon svg { width: 18px; height: 18px; }
        .stat-icon.blue   { background: rgba(59,130,246,0.12); color: var(--accent); }
        .stat-icon.green  { background: rgba(16,185,129,0.12); color: var(--success); }
        .stat-icon.red    { background: rgba(239,68,68,0.1);   color: var(--danger); }

        .stat-label { font-size: 0.75rem; color: var(--muted); margin-bottom: 3px; }
        .stat-value { font-size: 1.4rem; font-weight: 800; letter-spacing: -0.02em; }

        /* Table */
        .table-wrap {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 18px;
            overflow: hidden;
        }

        .table-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
        }

        .table-title { font-size: 0.95rem; font-weight: 700; }

        .table-count {
            font-size: 0.75rem;
            color: var(--muted);
            background: var(--surface2);
            padding: 4px 10px;
            border-radius: 20px;
            border: 1px solid var(--border);
        }

        /* Search */
        .search-wrap {
            position: relative;
            padding: 16px 24px;
            border-bottom: 1px solid var(--border);
        }

        .search-wrap svg {
            position: absolute;
            left: 38px;
            top: 50%;
            transform: translateY(-50%);
            width: 14px; height: 14px;
            color: #475569;
            pointer-events: none;
        }

        #searchInput {
            width: 100%;
            padding: 9px 12px 9px 36px;
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 9px;
            color: var(--text);
            font-size: 0.85rem;
            font-family: inherit;
        }

        #searchInput::placeholder { color: #475569; }
        #searchInput:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 2px rgba(59,130,246,0.1); }

        table { width: 100%; border-collapse: collapse; }

        thead th {
            padding: 12px 20px;
            text-align: left;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--muted);
            background: var(--surface2);
            border-bottom: 1px solid var(--border);
        }

        tbody tr {
            border-bottom: 1px solid rgba(99,179,237,0.06);
            transition: background 0.12s;
        }

        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: rgba(59,130,246,0.04); }

        tbody td {
            padding: 14px 20px;
            font-size: 0.85rem;
            color: var(--text);
            vertical-align: middle;
        }

        .td-id { color: var(--muted); font-size: 0.78rem; font-family: monospace; }

        .td-user { font-weight: 600; }

        .td-action {
            font-size: 0.78rem;
            font-weight: 600;
            text-transform: capitalize;
            padding: 3px 9px;
            border-radius: 6px;
            display: inline-block;
        }

        .action-login      { background: rgba(59,130,246,0.1);  color: #93c5fd; }
        .action-logout     { background: rgba(148,163,184,0.1); color: #94a3b8; }
        .action-registration { background: rgba(16,185,129,0.1); color: #6ee7b7; }
        .action-update     { background: rgba(245,158,11,0.1);  color: #fcd34d; }
        .action-deletion   { background: rgba(239,68,68,0.1);   color: #fca5a5; }
        .action-password_reset { background: rgba(99,102,241,0.1); color: #a5b4fc; }
        .action-default    { background: rgba(148,163,184,0.08); color: #94a3b8; }

        .td-time { color: var(--muted); font-size: 0.8rem; font-family: monospace; }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 9px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-badge::before {
            content: '';
            width: 6px; height: 6px;
            border-radius: 50%;
            background: currentColor;
        }

        .status-success { background: rgba(16,185,129,0.1);  color: #6ee7b7; }
        .status-failed  { background: rgba(239,68,68,0.1);   color: #fca5a5; }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--muted);
        }

        .empty-state svg { width: 40px; height: 40px; margin-bottom: 12px; opacity: 0.3; }

        /* Responsive Design */
        /* Tablet: 560px - 899px */
        @media (max-width: 899px) and (min-width: 560px) {
            .sidebar { display: none; }
            .main { margin-left: 0; padding: 32px; }
            
            .stats { 
                grid-template-columns: repeat(2, 1fr); 
                gap: 14px;
            }
            
            .topbar { margin-bottom: 28px; }
            .page-title { font-size: 1.35rem; }
            
            /* Table adjustments */
            .table-container { 
                overflow-x: auto; 
                -webkit-overflow-scrolling: touch;
            }
        }

        /* Mobile: < 560px */
        @media (max-width: 560px) {
            .sidebar { display: none; }
            .main { margin-left: 0; padding: 20px 16px; }
            
            /* Stack stat cards vertically */
            .stats { 
                grid-template-columns: 1fr; 
                gap: 12px;
                margin-bottom: 24px;
            }
            
            .stat-card { 
                padding: 16px 18px; 
            }
            
            .stat-icon { 
                width: 38px; 
                height: 38px; 
            }
            
            .stat-icon svg { width: 16px; height: 16px; }
            .stat-label { font-size: 0.7rem; }
            .stat-value { font-size: 0.95rem; }
            
            /* Topbar adjustments */
            .topbar { 
                flex-direction: column; 
                align-items: flex-start; 
                gap: 16px;
                margin-bottom: 24px;
            }
            
            .page-title { font-size: 1.25rem; }
            
            .back-btn { 
                align-self: stretch;
                justify-content: center;
                min-height: 44px;
                padding: 12px 16px;
            }
            
            /* Filter adjustments */
            .filter-bar { 
                padding: 16px; 
                border-radius: 14px;
                margin-bottom: 16px;
            }
            
            .filter-input { 
                padding: 14px 14px 14px 40px; 
                font-size: 16px; /* Prevents zoom on iOS */
                min-height: 44px;
            }
            
            /* Table adjustments for mobile */
            .table-container { 
                overflow-x: auto; 
                -webkit-overflow-scrolling: touch;
                border-radius: 14px;
            }
            
            .audit-table { 
                font-size: 0.8rem; 
            }
            
            .audit-table th { 
                padding: 12px 10px; 
                font-size: 0.7rem;
            }
            
            .audit-table td { 
                padding: 12px 10px; 
            }
            
            /* Badge adjustments */
            .badge { 
                font-size: 0.65rem; 
                padding: 3px 7px; 
            }
            
            /* Status icon adjustments */
            .status-icon { 
                width: 16px; 
                height: 16px; 
            }
            
            /* Adjust font sizes for mobile readability */
            .empty-state { 
                padding: 48px 20px; 
            }
            
            .empty-state svg { 
                width: 32px; 
                height: 32px; 
            }
            
            .empty-state p { 
                font-size: 0.85rem; 
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
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
            <a href="profile.php" class="nav-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                My Profile
            </a>
            <div class="nav-label">Admin</div>
            <a href="admin.php" class="nav-item active">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                Audit Logs
            </a>
        </nav>
        <div class="sidebar-footer">
            <div class="user-chip">
                <div class="avatar">A</div>
                <div class="user-info">
                    <div class="user-name">Administrator</div>
                    <div class="user-role">admin</div>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main -->
    <main class="main">
        <div class="topbar">
            <div class="page-title">Admin Panel <span>/ Audit Logs</span></div>
            <a href="dashboard.php" class="back-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
                Back to Dashboard
            </a>
        </div>

        <!-- Stats -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                </div>
                <div>
                    <div class="stat-label">Total Events</div>
                    <div class="stat-value"><?php echo $total; ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <div>
                    <div class="stat-label">Successful</div>
                    <div class="stat-value" style="color:var(--success)"><?php echo $success; ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon red">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                </div>
                <div>
                    <div class="stat-label">Failed Attempts</div>
                    <div class="stat-value" style="color:var(--danger)"><?php echo $failed; ?></div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="table-wrap">
            <div class="table-header">
                <div class="table-title">Security Event Log</div>
                <div class="table-count"><?php echo $total; ?> records</div>
            </div>

            <div class="search-wrap">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" id="searchInput" placeholder="Search by user, action, or status…" oninput="filterTable()">
            </div>

            <?php if (empty($logs)): ?>
            <div class="empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                <div>No audit events recorded yet.</div>
            </div>
            <?php else: ?>
            <table id="auditTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Timestamp</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log):
                        $action = strtolower($log['action']);
                        $actionClass = in_array($action, ['login','logout','registration','update','deletion','password_reset'])
                            ? 'action-' . $action : 'action-default';
                        $statusClass = $log['status'] === 'success' ? 'status-success' : 'status-failed';
                    ?>
                    <tr>
                        <td class="td-id"><?php echo htmlspecialchars($log['id']); ?></td>
                        <td class="td-user"><?php echo htmlspecialchars($log['full_name'] ?: 'Unknown'); ?></td>
                        <td><span class="td-action <?php echo $actionClass; ?>"><?php echo htmlspecialchars($log['action']); ?></span></td>
                        <td class="td-time"><?php echo htmlspecialchars($log['timestamp']); ?></td>
                        <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($log['status']); ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </main>

    <script>
        function filterTable() {
            const q = document.getElementById('searchInput').value.toLowerCase();
            document.querySelectorAll('#auditTable tbody tr').forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        }
    </script>
</body>
</html>
