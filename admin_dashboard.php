<?php

require_once __DIR__ . '/auth.php';
requireRole('admin');

$adminName = $_SESSION['full_name'] ?? 'Administrator';
$adminEmail = $_SESSION['email'] ?? 'admin@campus.edu';

$totalUsers = $conn->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()['total'] ?? 0;
$totalStudents = $conn->query("SELECT COUNT(*) AS total FROM students")->fetch_assoc()['total'] ?? 0;
$totalAdmins = $conn->query("SELECT COUNT(*) AS total FROM user_roles WHERE role = 'admin'")->fetch_assoc()['total'] ?? 0;
$totalEvents = $conn->query("SELECT COUNT(*) AS total FROM events")->fetch_assoc()['total'] ?? 0;
$totalRegistrations = $conn->query("SELECT COUNT(*) AS total FROM registrations")->fetch_assoc()['total'] ?? 0;

$latestRegQuery = "
    SELECT r.registration_id, r.registered_at, u.full_name AS student_name, u.email, e.title AS event_title, e.category
    FROM registrations r
    JOIN users u ON u.user_id = r.user_id
    JOIN events e ON e.event_id = r.event_id
    ORDER BY r.registered_at DESC
    LIMIT 6
";
$latestRegistrations = $conn->query($latestRegQuery)->fetch_all(MYSQLI_ASSOC);

$auditLogs = $conn->query("
    SELECT log_id, user_name, action, details, ip_address, created_at
    FROM audit_logs
    ORDER BY created_at DESC
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

$recentAccounts = $conn->query("
    SELECT u.user_id, u.full_name, u.email, u.course, u.created_at, COALESCE(ur.role, 'student') as role
    FROM users u
    LEFT JOIN user_roles ur ON ur.user_id = u.user_id
    ORDER BY u.user_id DESC
    LIMIT 6
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard &amp; Audit Trail | Student Portal</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
</head>
<body class="page-admin">
    
    <header class="header">
        <div class="logo-section">
            <div class="brand-badge">SP</div>
            <a href="Home.html" class="brand-title">Student<span>Portal</span></a>
        </div>
        <button class="menu-button" id="menuButton" aria-label="Toggle menu">&#9776;</button>
        <nav class="navbar" id="navbar">
            <a href="admin_dashboard.php" class="active">Dashboard</a>
            <a href="students.php">Students CRUD</a>
            <a href="events.php">Events CRUD</a>
            <a href="ajax_events.html">AJAX Events</a>
            <a href="fetch.php">PDO Demo</a>
            <a href="Home.html">Portal Home</a>
            <span class="user-greeting">Admin: <?= sanitize_out($adminName) ?></span>
            <a href="logout.php" class="btn-logout">Logout</a>
            <button id="themeToggle" class="theme-btn" title="Toggle Theme">&#9790;</button>
        </nav>
    </header>

    <main class="admin-container">
        
        <div class="dashboard-banner">
            <div>
                <h2>Welcome Back, <?= sanitize_out($adminName) ?>!</h2>
                <p>Student Portal Central Administration Portal &bull; Role-Based Access Control Active</p>
            </div>
            <div class="banner-quick-actions">
                <a href="students.php" class="btn btn-primary">+ Manage Students</a>
                <a href="events.php" class="btn btn-secondary">+ Post Event</a>
            </div>
        </div>

        
        <section class="stat-grid">
            <div class="stat-card stat-indigo">
                <div class="stat-icon">&#127891;</div>
                <div class="stat-data">
                    <span class="stat-value"><?= $totalStudents ?></span>
                    <span class="stat-label">Enrolled Students</span>
                </div>
            </div>

            <div class="stat-card stat-teal">
                <div class="stat-icon">&#128197;</div>
                <div class="stat-data">
                    <span class="stat-value"><?= $totalEvents ?></span>
                    <span class="stat-label">Campus Events</span>
                </div>
            </div>

            <div class="stat-card stat-amber">
                <div class="stat-icon">&#128101;</div>
                <div class="stat-data">
                    <span class="stat-value"><?= $totalUsers ?></span>
                    <span class="stat-label">User Accounts</span>
                </div>
            </div>

            <div class="stat-card stat-emerald">
                <div class="stat-icon">&#128221;</div>
                <div class="stat-data">
                    <span class="stat-value"><?= $totalRegistrations ?></span>
                    <span class="stat-label">Event Signups</span>
                </div>
            </div>
        </section>

        
        <div class="two-col-grid">
            
            <div class="card">
                <div class="card-head-flex">
                    <h3>Latest Event Signups</h3>
                    <a href="events.php" class="text-link">Manage Events &rarr;</a>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Event</th>
                                <th>Category</th>
                                <th>Registered On</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($latestRegistrations)): ?>
                                <tr><td colspan="4" class="text-center">No registrations yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($latestRegistrations as $reg): ?>
                                    <tr>
                                        <td>
                                            <strong><?= sanitize_out($reg['student_name']) ?></strong><br>
                                            <small class="text-muted"><?= sanitize_out($reg['email']) ?></small>
                                        </td>
                                        <td><?= sanitize_out($reg['event_title']) ?></td>
                                        <td><span class="badge badge-category"><?= sanitize_out($reg['category']) ?></span></td>
                                        <td><?= date("d M, h:i A", strtotime($reg['registered_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            
            <div class="card">
                <div class="card-head-flex">
                    <h3>Recent User Signups</h3>
                    <a href="students.php" class="text-link">Full Directory &rarr;</a>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Course</th>
                                <th>Role</th>
                                <th>Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentAccounts as $u): ?>
                                <tr>
                                    <td>
                                        <strong><?= sanitize_out($u['full_name']) ?></strong><br>
                                        <small class="text-muted"><?= sanitize_out($u['email']) ?></small>
                                    </td>
                                    <td><?= sanitize_out($u['course']) ?></td>
                                    <td>
                                        <span class="badge badge-<?= $u['role'] === 'admin' ? 'admin' : 'student' ?>">
                                            <?= ucfirst($u['role']) ?>
                                        </span>
                                    </td>
                                    <td><?= date("d M Y", strtotime($u['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        
        <section class="card audit-card">
            <div class="card-head-flex">
                <div>
                    <h3>System Security &amp; Audit Trail</h3>
                    <p class="subtitle">Complete audit logs monitoring administrative actions, logins, deletions, and updates.</p>
                </div>
                <span class="badge badge-success">&#128274; Audit Tracking Active</span>
            </div>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Log ID</th>
                            <th>Timestamp</th>
                            <th>Operator</th>
                            <th>Action Type</th>
                            <th>Audit Details</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($auditLogs)): ?>
                            <tr><td colspan="6" class="text-center">No logs recorded yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($auditLogs as $log): ?>
                                <tr>
                                    <td>#<?= sanitize_out($log['log_id']) ?></td>
                                    <td><?= date("d M Y, H:i:s", strtotime($log['created_at'])) ?></td>
                                    <td><strong><?= sanitize_out($log['user_name']) ?></strong></td>
                                    <td>
                                        <span class="badge badge-log"><?= sanitize_out($log['action']) ?></span>
                                    </td>
                                    <td><?= sanitize_out($log['details']) ?></td>
                                    <td><code><?= sanitize_out($log['ip_address']) ?></code></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
