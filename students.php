<?php
/**
 * CampusSync - Student Management CRUD Module (Practical 11)
 * Role-Based Access Control, MySQLi Prepared Statements, Search, Filter & CSV Export.
 */

require_once __DIR__ . '/auth.php';
requireRole('admin');

$message = "";
$messageType = "";
$editStudent = null;

// --- CSV Export (Practical 11 Intermediate Extension) ---
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=students_export_' . date('Ymd_His') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Enrollment No', 'Full Name', 'Email', 'Mobile', 'Course', 'Semester', 'Status', 'Created At']);

    $res = $conn->query("SELECT student_id, enrollment_no, full_name, email, mobile, course, semester, status, created_at FROM students ORDER BY student_id DESC");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            fputcsv($output, $row);
        }
    }
    fclose($output);
    exit();
}

// --- Form POST Handlers (Add, Update, Delete) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $studentId = (int)($_POST['student_id'] ?? 0);

    if ($action === 'delete') {
        if ($studentId > 0) {
            $stmt = $conn->prepare("DELETE FROM students WHERE student_id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $studentId);
                if ($stmt->execute()) {
                    $message = "Student record deleted successfully.";
                    $messageType = "success";
                    logAudit($conn, $_SESSION['user_id'], $_SESSION['full_name'], 'STUDENT_DELETE', "Deleted student ID: $studentId");
                } else {
                    $message = "Failed to delete student: " . $stmt->error;
                    $messageType = "error";
                }
                $stmt->close();
            }
        }
    } elseif ($action === 'add' || $action === 'update') {
        $enrollmentNo = trim($_POST['enrollment_no'] ?? '');
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $mobile = trim($_POST['mobile'] ?? '');
        $course = trim($_POST['course'] ?? '');
        $semester = (int)($_POST['semester'] ?? 1);
        $status = $_POST['status'] ?? 'Active';

        // Validation
        if (empty($enrollmentNo) || empty($fullName) || empty($email) || empty($mobile) || empty($course)) {
            $message = "All mandatory fields must be completed.";
            $messageType = "error";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "Invalid email address format.";
            $messageType = "error";
        } elseif (!preg_match('/^[6-9]\d{9}$/', $mobile)) {
            $message = "Mobile number must be a 10-digit number.";
            $messageType = "error";
        } else {
            if ($action === 'add') {
                // Check duplicate enrollment/email
                $chk = $conn->prepare("SELECT student_id FROM students WHERE enrollment_no = ? OR email = ?");
                $chk->bind_param("ss", $enrollmentNo, $email);
                $chk->execute();
                $chk->store_result();
                if ($chk->num_rows > 0) {
                    $message = "A student with this Enrollment No or Email already exists.";
                    $messageType = "error";
                } else {
                    $stmt = $conn->prepare("INSERT INTO students (enrollment_no, full_name, email, mobile, course, semester, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssssis", $enrollmentNo, $fullName, $email, $mobile, $course, $semester, $status);
                    if ($stmt->execute()) {
                        $message = "New student enrolled successfully.";
                        $messageType = "success";
                        logAudit($conn, $_SESSION['user_id'], $_SESSION['full_name'], 'STUDENT_ADD', "Added student $fullName ($enrollmentNo)");
                    } else {
                        $message = "Error adding student: " . $stmt->error;
                        $messageType = "error";
                    }
                    $stmt->close();
                }
                $chk->close();
            } elseif ($action === 'update' && $studentId > 0) {
                $stmt = $conn->prepare("UPDATE students SET enrollment_no = ?, full_name = ?, email = ?, mobile = ?, course = ?, semester = ?, status = ? WHERE student_id = ?");
                $stmt->bind_param("sssssisi", $enrollmentNo, $fullName, $email, $mobile, $course, $semester, $status, $studentId);
                if ($stmt->execute()) {
                    $message = "Student record updated successfully.";
                    $messageType = "success";
                    logAudit($conn, $_SESSION['user_id'], $_SESSION['full_name'], 'STUDENT_UPDATE', "Updated student ID: $studentId ($fullName)");
                } else {
                    $message = "Error updating student: " . $stmt->error;
                    $messageType = "error";
                }
                $stmt->close();
            }
        }
    }
}

// Check if edit mode requested
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 1) {
        $editStudent = $res->fetch_assoc();
    }
    $stmt->close();
}

// Search and Filter logic
$searchQuery = trim($_GET['search'] ?? '');
$filterCourse = trim($_GET['course'] ?? '');
$filterStatus = trim($_GET['status'] ?? '');

$sql = "SELECT * FROM students WHERE 1=1";
$params = [];
$types = "";

if (!empty($searchQuery)) {
    $sql .= " AND (full_name LIKE ? OR email LIKE ? OR enrollment_no LIKE ? OR mobile LIKE ?)";
    $like = "%" . $searchQuery . "%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "ssss";
}

if (!empty($filterCourse)) {
    $sql .= " AND course = ?";
    $params[] = $filterCourse;
    $types .= "s";
}

if (!empty($filterStatus)) {
    $sql .= " AND status = ?";
    $params[] = $filterStatus;
    $types .= "s";
}

$sql .= " ORDER BY student_id DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Distinct courses for dropdown
$coursesList = ['B.Tech CSE', 'B.Tech IT', 'B.Tech CE', 'B.Tech AIML', 'B.Tech EC'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management CRUD | CampusSync Admin</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
</head>
<body class="page-admin">
    <!-- Navbar -->
    <header class="header">
        <div class="logo-section">
            <div class="brand-badge">CS</div>
            <a href="Home.html" class="brand-title">Campus<span>Sync</span></a>
        </div>
        <button class="menu-button" id="menuButton" aria-label="Toggle menu">&#9776;</button>
        <nav class="navbar" id="navbar">
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="students.php" class="active">Students CRUD</a>
            <a href="events.php">Events CRUD</a>
            <a href="ajax_events.html">AJAX Events</a>
            <a href="Home.html">Portal Home</a>
            <span class="user-greeting">Admin: <?= sanitize_out($_SESSION['full_name'] ?? 'Admin') ?></span>
            <a href="logout.php" class="btn-logout">Logout</a>
            <button id="themeToggle" class="theme-btn" title="Toggle Theme">&#9790;</button>
        </nav>
    </header>

    <main class="admin-container">
        <div class="admin-header-row">
            <div>
                <h2>Student Management System (Practical 11)</h2>
                <p class="subtitle">Add, edit, view, search, filter, and delete student records with MySQLi prepared queries.</p>
            </div>
            <div>
                <a href="students.php?action=export_csv" class="btn btn-secondary">&#128196; Export to CSV</a>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $messageType ?>">
                <?= sanitize_out($message) ?>
            </div>
        <?php endif; ?>

        <!-- Form Card (Add / Edit) -->
        <section class="card form-card">
            <h3><?= $editStudent ? "Edit Student Record (#" . sanitize_out($editStudent['student_id']) . ")" : "Add New Student Record" ?></h3>
            <form action="students.php" method="POST" class="grid-form">
                <input type="hidden" name="action" value="<?= $editStudent ? 'update' : 'add' ?>">
                <input type="hidden" name="student_id" value="<?= sanitize_out($editStudent['student_id'] ?? 0) ?>">

                <div class="form-group">
                    <label for="enrollment_no">Enrollment No *</label>
                    <input type="text" id="enrollment_no" name="enrollment_no" required placeholder="e.g. 26DCS045" value="<?= sanitize_out($editStudent['enrollment_no'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="full_name">Full Name *</label>
                    <input type="text" id="full_name" name="full_name" required placeholder="e.g. Rahul Sharma" value="<?= sanitize_out($editStudent['full_name'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" required placeholder="e.g. rahul@campus.edu" value="<?= sanitize_out($editStudent['email'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="mobile">Mobile Number (10 Digits) *</label>
                    <input type="text" id="mobile" name="mobile" required pattern="[6-9][0-9]{9}" placeholder="e.g. 9825012345" value="<?= sanitize_out($editStudent['mobile'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="course">Course / Department *</label>
                    <select id="course" name="course" required>
                        <option value="">Select Course</option>
                        <?php foreach ($coursesList as $c): ?>
                            <option value="<?= $c ?>" <?= (isset($editStudent['course']) && $editStudent['course'] === $c) ? 'selected' : '' ?>><?= $c ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="semester">Semester *</label>
                    <select id="semester" name="semester" required>
                        <?php for ($s = 1; $s <= 8; $s++): ?>
                            <option value="<?= $s ?>" <?= (isset($editStudent['semester']) && (int)$editStudent['semester'] === $s) ? 'selected' : '' ?>>Semester <?= $s ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="status">Academic Status *</label>
                    <select id="status" name="status" required>
                        <option value="Active" <?= (isset($editStudent['status']) && $editStudent['status'] === 'Active') ? 'selected' : '' ?>>Active</option>
                        <option value="Graduated" <?= (isset($editStudent['status']) && $editStudent['status'] === 'Graduated') ? 'selected' : '' ?>>Graduated</option>
                        <option value="Suspended" <?= (isset($editStudent['status']) && $editStudent['status'] === 'Suspended') ? 'selected' : '' ?>>Suspended</option>
                    </select>
                </div>

                <div class="form-actions-bar">
                    <button type="submit" class="btn btn-primary"><?= $editStudent ? 'Update Student' : 'Save Student' ?></button>
                    <?php if ($editStudent): ?>
                        <a href="students.php" class="btn btn-secondary">Cancel Edit</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>

        <!-- Search & Filter Controls -->
        <section class="card filter-card">
            <form action="students.php" method="GET" class="filter-flex">
                <input type="text" name="search" placeholder="Search by name, email, roll no..." value="<?= sanitize_out($searchQuery) ?>" class="filter-input">
                
                <select name="course" class="filter-select">
                    <option value="">All Courses</option>
                    <?php foreach ($coursesList as $c): ?>
                        <option value="<?= $c ?>" <?= ($filterCourse === $c) ? 'selected' : '' ?>><?= $c ?></option>
                    <?php endforeach; ?>
                </select>

                <select name="status" class="filter-select">
                    <option value="">All Statuses</option>
                    <option value="Active" <?= ($filterStatus === 'Active') ? 'selected' : '' ?>>Active</option>
                    <option value="Graduated" <?= ($filterStatus === 'Graduated') ? 'selected' : '' ?>>Graduated</option>
                    <option value="Suspended" <?= ($filterStatus === 'Suspended') ? 'selected' : '' ?>>Suspended</option>
                </select>

                <button type="submit" class="btn btn-primary">Filter</button>
                <?php if (!empty($searchQuery) || !empty($filterCourse) || !empty($filterStatus)): ?>
                    <a href="students.php" class="btn btn-secondary">Reset</a>
                <?php endif; ?>
            </form>
        </section>

        <!-- Student Records Table -->
        <section class="card table-card">
            <div class="table-meta">
                <h3>Enrolled Students List</h3>
                <span class="count-pill">Total: <?= count($students) ?> Records</span>
            </div>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Enrollment</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Mobile</th>
                            <th>Course</th>
                            <th>Sem</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr>
                                <td colspan="9" class="text-center">No student records found matching criteria.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($students as $stu): ?>
                                <tr>
                                    <td><?= sanitize_out($stu['student_id']) ?></td>
                                    <td><strong><?= sanitize_out($stu['enrollment_no']) ?></strong></td>
                                    <td><?= sanitize_out($stu['full_name']) ?></td>
                                    <td><?= sanitize_out($stu['email']) ?></td>
                                    <td><?= sanitize_out($stu['mobile']) ?></td>
                                    <td><?= sanitize_out($stu['course']) ?></td>
                                    <td>Sem <?= sanitize_out($stu['semester']) ?></td>
                                    <td>
                                        <span class="badge badge-<?= strtolower($stu['status']) ?>">
                                            <?= sanitize_out($stu['status']) ?>
                                        </span>
                                    </td>
                                    <td class="action-buttons">
                                        <a href="students.php?edit=<?= $stu['student_id'] ?>" class="btn-sm btn-edit" title="Edit Record">&#9998; Edit</a>
                                        
                                        <form action="students.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to permanently delete student <?= sanitize_out($stu['full_name']) ?>?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="student_id" value="<?= $stu['student_id'] ?>">
                                            <button type="submit" class="btn-sm btn-delete" title="Delete Record">&#128465; Delete</button>
                                        </form>
                                    </td>
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
