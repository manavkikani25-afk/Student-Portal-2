<?php
/**
 * CampusSync - Form Processor with File Storage (Practical 7)
 * Validates inquiries/feedback, sanitizes input, and saves records into CSV and JSON.
 */

require_once __DIR__ . '/db.php';

$dataDir = __DIR__ . '/data';
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

$csvFile = $dataDir . '/inquiries.csv';
$jsonFile = $dataDir . '/inquiries.json';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senderName = trim($_POST['name'] ?? '');
    $senderEmail = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? 'General Inquiry');
    $category = trim($_POST['category'] ?? 'Academic');
    $message = trim($_POST['message'] ?? '');

    $errors = [];

    if (empty($senderName) || strlen($senderName) < 3) {
        $errors[] = "Full name is required (minimum 3 characters).";
    }

    if (!filter_var($senderEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email address is required.";
    }

    if (empty($message) || strlen($message) < 10) {
        $errors[] = "Message must be at least 10 characters long.";
    }

    if (!empty($errors)) {
        $msg = urlencode(implode("<br>", $errors));
        header("Location: Help.html?status=error&msg=" . $msg);
        exit();
    }

    // Sanitize values
    $cleanName = filter_var($senderName, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $cleanEmail = filter_var($senderEmail, FILTER_SANITIZE_EMAIL);
    $cleanSubject = filter_var($subject, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $cleanCategory = filter_var($category, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $cleanMsg = filter_var($message, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $timestamp = date("Y-m-d H:i:s");

    // 1. Store in CSV format (Practical 7)
    $csvRow = [$timestamp, $cleanName, $cleanEmail, $cleanCategory, $cleanSubject, $cleanMsg];
    $csvHandle = fopen($csvFile, 'a');
    if ($csvHandle) {
        // If file is newly created, write header row
        if (filesize($csvFile) === 0) {
            fputcsv($csvHandle, ['Timestamp', 'Full Name', 'Email', 'Category', 'Subject', 'Message']);
        }
        fputcsv($csvHandle, $csvRow);
        fclose($csvHandle);
    }

    // 2. Store in JSON format (Practical 7)
    $existingData = [];
    if (file_exists($jsonFile)) {
        $jsonRaw = file_get_contents($jsonFile);
        $decoded = json_decode($jsonRaw, true);
        if (is_array($decoded)) {
            $existingData = $decoded;
        }
    }

    $existingData[] = [
        'id'        => uniqid('inq_'),
        'timestamp' => $timestamp,
        'name'      => $cleanName,
        'email'     => $cleanEmail,
        'category'  => $cleanCategory,
        'subject'   => $cleanSubject,
        'message'   => $cleanMsg
    ];

    file_put_contents($jsonFile, json_encode($existingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    // Audit log if logged in
    if (isset($_SESSION['user_id'])) {
        logAudit($conn, $_SESSION['user_id'], $_SESSION['full_name'], 'HELP_INQUIRY', "Submitted contact request: $cleanSubject");
    }

    header("Location: Help.html?status=success&msg=" . urlencode("Thank you! Your inquiry has been submitted and safely recorded. Our team will contact you shortly."));
    exit();
}

header("Location: Help.html");
exit();
?>
