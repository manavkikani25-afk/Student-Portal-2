<?php

require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: Signup.html");
    exit();
}

$fullName = trim($_POST['full_name'] ?? '');
$email = strtolower(trim($_POST['email'] ?? ''));
$mobile = trim($_POST['mobile'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';
$course = trim($_POST['course'] ?? '');
$year = (int)($_POST['year'] ?? 1);
$gender = trim($_POST['gender'] ?? 'Other');
$terms = isset($_POST['terms']);

$errors = [];

if (empty($fullName) || strlen($fullName) < 3 || strlen($fullName) > 50) {
    $errors[] = "Full name must be between 3 and 50 characters.";
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Please provide a valid academic/personal email address.";
}

if (!preg_match('/^[6-9]\d{9}$/', $mobile)) {
    $errors[] = "Mobile number must be a valid 10-digit Indian phone number starting with 6-9.";
}

if (strlen($password) < 8) {
    $errors[] = "Password must be at least 8 characters long.";
}

if ($password !== $confirmPassword) {
    $errors[] = "Password and confirmation password do not match.";
}

if (empty($course)) {
    $errors[] = "Please select your enrolled academic program/course.";
}

if (!$terms) {
    $errors[] = "You must agree to the Campus Portal terms and privacy guidelines.";
}

if (!empty($errors)) {
    $errStr = urlencode(implode("<br>", $errors));
    header("Location: Signup.html?error=" . $errStr);
    exit();
}

$checkStmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
$checkStmt->bind_param("s", $email);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    $checkStmt->close();
    header("Location: Signup.html?error=" . urlencode("An account with this email address already exists. Please login instead."));
    exit();
}
$checkStmt->close();

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$insertUser = $conn->prepare("
    INSERT INTO users (full_name, email, mobile, password, course, year, gender)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

if (!$insertUser) {
    header("Location: Signup.html?error=" . urlencode("Database preparation failure."));
    exit();
}

$insertUser->bind_param("sssssis", $fullName, $email, $mobile, $hashedPassword, $course, $year, $gender);

if ($insertUser->execute()) {
    $newUserId = $conn->insert_id;
    $insertUser->close();

    
    $roleStmt = $conn->prepare("INSERT INTO user_roles (user_id, role) VALUES (?, 'student')");
    if ($roleStmt) {
        $roleStmt->bind_param("i", $newUserId);
        $roleStmt->execute();
        $roleStmt->close();
    }

    
    $enrollmentNo = "26D" . strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $course), 0, 2)) . str_pad($newUserId + 25, 3, '0', STR_PAD_LEFT);
    $sem = ($year * 2) - 1;
    $studentStmt = $conn->prepare("
        INSERT INTO students (enrollment_no, full_name, email, mobile, course, semester, status)
        VALUES (?, ?, ?, ?, ?, ?, 'Active')
        ON DUPLICATE KEY UPDATE full_name = VALUES(full_name)
    ");
    if ($studentStmt) {
        $studentStmt->bind_param("sssssi", $enrollmentNo, $fullName, $email, $mobile, $course, $sem);
        $studentStmt->execute();
        $studentStmt->close();
    }

    
    logAudit($conn, $newUserId, $fullName, 'USER_REGISTER', "New student registered with email: $email and course: $course");

    header("Location: Login.html?msg=registered_successfully");
    exit();
} else {
    $insertUser->close();
    header("Location: Signup.html?error=" . urlencode("Account creation failed. Please try again."));
    exit();
}
?>
