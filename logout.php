<?php
/**
 * Student Portal - Secure Logout Handler 
 */

require_once __DIR__ . '/auth.php';

$userId = (int)($_SESSION['user_id'] ?? 0);
$userName = $_SESSION['full_name'] ?? 'User';

if ($userId > 0) {
    logAudit($conn, $userId, $userName, 'USER_LOGOUT', 'User logged out voluntarily.');
    clearRememberMeCookie($userId, $conn);
}

$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

header("Location: Login.html?msg=logged_out");
exit();
?>
