<?php

require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: Login.html");
    exit();
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$remember = isset($_POST['remember_me']);

if (empty($email) || empty($password)) {
    header("Location: Login.html?error=" . urlencode("Email and password are required."));
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: Login.html?error=" . urlencode("Please enter a valid email address."));
    exit();
}

$sql = "
    SELECT u.user_id, u.full_name, u.email, u.password, COALESCE(ur.role, 'student') AS role
    FROM users u
    LEFT JOIN user_roles ur ON ur.user_id = u.user_id
    WHERE u.email = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    header("Location: Login.html?error=" . urlencode("System database query error."));
    exit();
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    if (password_verify($password, $user['password'])) {
        
        session_regenerate_id(true);

        $_SESSION['user_id'] = (int)$user['user_id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['last_activity'] = time();

        if ($remember) {
            setRememberMeCookie($user['user_id'], $conn);
        }

        
        logAudit($conn, $user['user_id'], $user['full_name'], 'USER_LOGIN', "Successful login as " . $user['role']);

        $stmt->close();

        
        if ($user['role'] === 'admin') {
            header("Location: admin_dashboard.php");
        } else {
            header("Location: dashboard.php");
        }
        exit();
    }
}

$stmt->close();

$ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$failStmt = $conn->prepare("INSERT INTO audit_logs (user_name, action, details, ip_address) VALUES (?, 'LOGIN_FAILED', ?, ?)");
if ($failStmt) {
    $detail = "Failed login attempt for email: " . substr($email, 0, 50);
    $uname = "Anonymous";
    $failStmt->bind_param("sss", $uname, $detail, $ip);
    $failStmt->execute();
    $failStmt->close();
}

header("Location: Login.html?error=" . urlencode("Invalid email address or password."));
exit();
?>
