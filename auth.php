<?php
/**
 * Student Portal - Authentication Middleware & Role-Based Access Control (RBAC)
 * Subject: Web Development Frameworks (ITUE203) - , 14
 */

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/remember.php';

// Attempt persistent login restoration if cookie is present
restoreRememberLogin($conn);

// Session Inactivity Timeout: 15 minutes (900 seconds)
$timeout_limit = 900;

if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout_limit)) {
        // Timed out
        $uid = $_SESSION['user_id'];
        $uname = $_SESSION['full_name'] ?? 'User';
        logAudit($conn, $uid, $uname, 'SESSION_TIMEOUT', 'Session expired due to inactivity.');

        session_unset();
        session_destroy();
        clearRememberMeCookie($uid, $conn);

        header("Location: Login.html?msg=timeout");
        exit();
    }
    $_SESSION['last_activity'] = time();
}

/**
 * Check if current visitor is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Enforce login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: Login.html?msg=login_required");
        exit();
    }
}

/**
 * Enforce specific role (e.g. 'admin')
 */
function requireRole($requiredRole) {
    requireLogin();
    $currentRole = $_SESSION['role'] ?? 'student';
    if ($currentRole !== $requiredRole) {
        http_response_code(403);
        die("<!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <title>403 - Access Denied | Student Portal</title>
            <link rel='stylesheet' href='style.css'>
            <style>
                .denied-card {
                    max-width: 500px;
                    margin: 80px auto;
                    background: #fff;
                    padding: 35px;
                    border-radius: 14px;
                    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
                    text-align: center;
                    border-top: 5px solid #ef4444;
                }
                .denied-card h2 { color: #dc2626; margin-bottom: 10px; }
                .denied-card p { color: #475569; line-height: 1.6; margin-bottom: 25px; }
                .btn-back {
                    display: inline-block;
                    padding: 10px 22px;
                    background: #4f46e5;
                    color: #fff;
                    text-decoration: none;
                    border-radius: 8px;
                    font-weight: 600;
                }
                .btn-back:hover { background: #4338ca; }
            </style>
        </head>
        <body class='page-error'>
            <div class='denied-card'>
                <div style='font-size: 50px; margin-bottom: 10px;'>🛡️</div>
                <h2>403 - Access Restricted</h2>
                <p>You do not have administrative permission to view this section. This page requires elevated <strong>" . htmlspecialchars($requiredRole) . "</strong> credentials.</p>
                <a href='dashboard.php' class='btn-back'>Return to Student Dashboard</a>
            </div>
        </body>
        </html>");
    }
}
?>
