<?php
/**
 * Student Portal - Database Connection & Helper Utilities
 * Subject: Web Development Frameworks (ITUE203) - , 9, 14
 */

$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "campussync_db";

// 1. MySQLi Connection (Used across CRUD modules)
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    error_log("Database Connection Error: " . $conn->connect_error);
    // If running in development, give friendly tip to import database.sql
    die("<div style='font-family:sans-serif;padding:30px;background:#fee2e2;color:#991b1b;border-radius:8px;max-width:600px;margin:50px auto;border:1px solid #f87171;'>
        <h2>Database Connection Error</h2>
        <p>Could not connect to MySQL database <strong>$db_name</strong>.</p>
        <p><strong>Troubleshooting:</strong></p>
        <ol>
            <li>Ensure MySQL is running in XAMPP Control Panel.</li>
            <li>Open <a href='http://localhost/phpmyadmin' target='_blank'>phpMyAdmin</a> and create database <code>$db_name</code>.</li>
            <li>Import <code>database.sql</code> from the project folder.</li>
        </ol>
        <small>Technical details: " . htmlspecialchars($conn->connect_error) . "</small>
    </div>");
}

$conn->set_charset("utf8mb4");

// 2. PDO Connection (for  demonstration)
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    error_log("PDO Connection Error: " . $e->getMessage());
}

/**
 * XSS escape shorthand
 */
function sanitize_out($value) {
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}

/**
 * Audit log helper 
 */
function logAudit($conn, $userId, $userName, $action, $details) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, user_name, action, details, ip_address) VALUES (?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("issss", $userId, $userName, $action, $details, $ip);
        $stmt->execute();
        $stmt->close();
    }
}
?>
