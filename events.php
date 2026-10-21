<?php
/**
 * Student Portal - Event Management CRUD with Poster Upload (Practical 12)
 * File type validation, size check, unique naming, old file cleanup, and MySQLi CRUD.
 */

require_once __DIR__ . '/auth.php';
requireRole('admin');

$message = "";
$messageType = "";
$editEvent = null;

$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Handle Form Submissions (Add, Update, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $eventId = (int)($_POST['event_id'] ?? 0);

    if ($action === 'delete') {
        if ($eventId > 0) {
            // Find existing poster to unlink
            $find = $conn->prepare("SELECT poster, title FROM events WHERE event_id = ?");
            $find->bind_param("i", $eventId);
            $find->execute();
            $ev = $find->get_result()->fetch_assoc();
            $find->close();

            if ($ev) {
                if (!empty($ev['poster']) && file_exists($uploadDir . basename($ev['poster']))) {
                    unlink($uploadDir . basename($ev['poster']));
                }

                $del = $conn->prepare("DELETE FROM events WHERE event_id = ?");
                $del->bind_param("i", $eventId);
                if ($del->execute()) {
                    $message = "Event '{$ev['title']}' deleted successfully.";
                    $messageType = "success";
                    logAudit($conn, $_SESSION['user_id'], $_SESSION['full_name'], 'EVENT_DELETE', "Deleted event ID: $eventId ({$ev['title']})");
                } else {
                    $message = "Error deleting event: " . $del->error;
                    $messageType = "error";
                }
                $del->close();
            }
        }
    } elseif ($action === 'add' || $action === 'update') {
        $title = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? 'Technical');
        $eventDate = $_POST['event_date'] ?? '';
        $eventTime = $_POST['event_time'] ?? '';
        $venue = trim($_POST['venue'] ?? '');
        $description = trim($_POST['event_description'] ?? '');
        $status = $_POST['status'] ?? 'Upcoming';

        // Validation
        if (empty($title) || empty($eventDate) || empty($eventTime) || empty($venue)) {
            $message = "Please fill in all required event details.";
            $messageType = "error";
        } else {
            $newPosterName = null;
            $uploadError = false;

            // Handle file upload if provided
            if (isset($_FILES['poster']) && $_FILES['poster']['error'] !== UPLOAD_ERR_NO_FILE) {
                $file = $_FILES['poster'];

                if ($file['error'] !== UPLOAD_ERR_OK) {
                    $message = "File upload failed with error code: " . $file['error'];
                    $messageType = "error";
                    $uploadError = true;
                } elseif ($file['size'] > 2 * 1024 * 1024) { // 2MB Max
                    $message = "Event poster must not exceed 2 MB.";
                    $messageType = "error";
                    $uploadError = true;
                } else {
                    $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

                    // Verify MIME type using finfo
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $file['tmp_name']);
                    finfo_close($finfo);

                    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];

                    if (!in_array($ext, $allowedExts) || !in_array($mime, $allowedMimes)) {
                        $message = "Invalid image type. Only JPG, PNG, and WEBP files are permitted.";
                        $messageType = "error";
                        $uploadError = true;
                    } else {
                        // Unique safe file name
                        $newPosterName = uniqid("event_poster_", true) . "." . $ext;
                        $dest = $uploadDir . $newPosterName;

                        if (!move_uploaded_file($file['tmp_name'], $dest)) {
                            $message = "Unable to store uploaded poster in the uploads folder.";
                            $messageType = "error";
                            $uploadError = true;
                            $newPosterName = null;
                        }
                    }
                }
            }

            if (!$uploadError) {
                if ($action === 'add') {
                    $stmt = $conn->prepare("INSERT INTO events (title, category, event_date, event_time, venue, event_description, poster, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssssssss", $title, $category, $eventDate, $eventTime, $venue, $description, $newPosterName, $status);
                    if ($stmt->execute()) {
                        $message = "New event published successfully.";
                        $messageType = "success";
                        logAudit($conn, $_SESSION['user_id'], $_SESSION['full_name'], 'EVENT_ADD', "Published event: $title");
                    } else {
                        $message = "Error creating event: " . $stmt->error;
                        $messageType = "error";
                    }
                    $stmt->close();
                } elseif ($action === 'update' && $eventId > 0) {
                    // Check if previous poster exists
                    $oldFind = $conn->prepare("SELECT poster FROM events WHERE event_id = ?");
                    $oldFind->bind_param("i", $eventId);
                    $oldFind->execute();
                    $oldRow = $oldFind->get_result()->fetch_assoc();
                    $oldFind->close();

                    if ($newPosterName !== null) {
                        // Clean up old poster
                        if (!empty($oldRow['poster']) && file_exists($uploadDir . basename($oldRow['poster']))) {
                            unlink($uploadDir . basename($oldRow['poster']));
                        }

                        $stmt = $conn->prepare("UPDATE events SET title = ?, category = ?, event_date = ?, event_time = ?, venue = ?, event_description = ?, poster = ?, status = ? WHERE event_id = ?");
                        $stmt->bind_param("ssssssssi", $title, $category, $eventDate, $eventTime, $venue, $description, $newPosterName, $status, $eventId);
                    } else {
                        $stmt = $conn->prepare("UPDATE events SET title = ?, category = ?, event_date = ?, event_time = ?, venue = ?, event_description = ?, status = ? WHERE event_id = ?");
                        $stmt->bind_param("sssssssi", $title, $category, $eventDate, $eventTime, $venue, $description, $status, $eventId);
                    }

                    if ($stmt->execute()) {
                        $message = "Event details updated successfully.";
                        $messageType = "success";
                        logAudit($conn, $_SESSION['user_id'], $_SESSION['full_name'], 'EVENT_UPDATE', "Updated event ID: $eventId ($title)");
                    } else {
                        $message = "Error updating event: " . $stmt->error;
                        $messageType = "error";
                    }
                    $stmt->close();
                }
            }
        }
    }
}

// If Edit requested
if (isset($_GET['edit'])) {
    $eId = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM events WHERE event_id = ?");
    $stmt->bind_param("i", $eId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 1) {
        $editEvent = $res->fetch_assoc();
    }
    $stmt->close();
}

// Fetch all events
$events = $conn->query("SELECT * FROM events ORDER BY event_date ASC, event_time ASC")->fetch_all(MYSQLI_ASSOC);
$categories = ['Technical', 'Cultural', 'Sports', 'Workshop', 'Seminar', 'Hackathon'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Management CRUD with File Upload | Student Portal Admin</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
</head>
<body class="page-admin">
    <!-- Navbar -->
    <header class="header">
        <div class="logo-section">
            <div class="brand-badge">SP</div>
            <a href="Home.html" class="brand-title">Student<span>Portal</span></a>
        </div>
        <button class="menu-button" id="menuButton" aria-label="Toggle menu">&#9776;</button>
        <nav class="navbar" id="navbar">
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="students.php">Students CRUD</a>
            <a href="events.php" class="active">Events CRUD</a>
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
                <h2>Event Management CRUD (Practical 12)</h2>
                <p class="subtitle">Create and manage campus events with validated poster image upload (max 2MB, JPG/PNG/WEBP).</p>
            </div>
            <div>
                <a href="events.html" target="_blank" class="btn btn-secondary">&#127760; View Student Events Page</a>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $messageType ?>">
                <?= sanitize_out($message) ?>
            </div>
        <?php endif; ?>

        <!-- Form Card (Add/Edit) -->
        <section class="card form-card">
            <h3><?= $editEvent ? "Edit Event (#" . sanitize_out($editEvent['event_id']) . ")" : "Add New Campus Event" ?></h3>
            <form action="events.php" method="POST" enctype="multipart/form-data" class="grid-form">
                <input type="hidden" name="action" value="<?= $editEvent ? 'update' : 'add' ?>">
                <input type="hidden" name="event_id" value="<?= sanitize_out($editEvent['event_id'] ?? 0) ?>">

                <div class="form-group span-2">
                    <label for="title">Event Title *</label>
                    <input type="text" id="title" name="title" required placeholder="e.g. National Cyber Security Summit 2026" value="<?= sanitize_out($editEvent['title'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="category">Category *</label>
                    <select id="category" name="category" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat ?>" <?= (isset($editEvent['category']) && $editEvent['category'] === $cat) ? 'selected' : '' ?>><?= $cat ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="status">Event Status *</label>
                    <select id="status" name="status" required>
                        <option value="Upcoming" <?= (isset($editEvent['status']) && $editEvent['status'] === 'Upcoming') ? 'selected' : '' ?>>Upcoming</option>
                        <option value="Ongoing" <?= (isset($editEvent['status']) && $editEvent['status'] === 'Ongoing') ? 'selected' : '' ?>>Ongoing</option>
                        <option value="Completed" <?= (isset($editEvent['status']) && $editEvent['status'] === 'Completed') ? 'selected' : '' ?>>Completed</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="event_date">Date *</label>
                    <input type="date" id="event_date" name="event_date" required value="<?= sanitize_out($editEvent['event_date'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="event_time">Time *</label>
                    <input type="time" id="event_time" name="event_time" required value="<?= sanitize_out($editEvent['event_time'] ?? '') ?>">
                </div>

                <div class="form-group span-2">
                    <label for="venue">Campus Venue *</label>
                    <input type="text" id="venue" name="venue" required placeholder="e.g. Auditorium Hall 1 / Computer Labs" value="<?= sanitize_out($editEvent['venue'] ?? '') ?>">
                </div>

                <div class="form-group span-2">
                    <label for="event_description">Event Description</label>
                    <textarea id="event_description" name="event_description" rows="3" placeholder="Provide event schedule, rules, eligibility, prizes..."><?= sanitize_out($editEvent['event_description'] ?? '') ?></textarea>
                </div>

                <div class="form-group span-2">
                    <label for="poster">Event Poster Image (JPG, PNG, WEBP &bull; Max 2MB)</label>
                    <input type="file" id="poster" name="poster" accept=".jpg,.jpeg,.png,.webp">
                    <?php if (!empty($editEvent['poster']) && file_exists($uploadDir . basename($editEvent['poster']))): ?>
                        <div class="current-poster-preview">
                            <p>Current Poster:</p>
                            <img src="uploads/<?= rawurlencode(basename($editEvent['poster'])) ?>" alt="Poster preview" style="max-height: 100px; border-radius: 6px;">
                            <small>(Select a new file only if you wish to replace this poster)</small>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-actions-bar span-2">
                    <button type="submit" class="btn btn-primary"><?= $editEvent ? 'Update Event' : 'Publish Event' ?></button>
                    <?php if ($editEvent): ?>
                        <a href="events.php" class="btn btn-secondary">Cancel Edit</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>

        <!-- Events List Table -->
        <section class="card table-card">
            <h3>Published Events (<?= count($events) ?>)</h3>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Poster</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Date &amp; Time</th>
                            <th>Venue</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($events)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No events created yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($events as $ev): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($ev['poster']) && file_exists($uploadDir . basename($ev['poster']))): ?>
                                            <img src="uploads/<?= rawurlencode(basename($ev['poster'])) ?>" alt="Poster" class="table-thumb">
                                        <?php else: ?>
                                            <div class="no-thumb">&#128197;</div>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?= sanitize_out($ev['title']) ?></strong></td>
                                    <td><span class="badge badge-category"><?= sanitize_out($ev['category']) ?></span></td>
                                    <td><?= date("d M Y", strtotime($ev['event_date'])) ?> at <?= date("h:i A", strtotime($ev['event_time'])) ?></td>
                                    <td><?= sanitize_out($ev['venue']) ?></td>
                                    <td>
                                        <span class="badge badge-<?= strtolower($ev['status']) ?>">
                                            <?= sanitize_out($ev['status']) ?>
                                        </span>
                                    </td>
                                    <td class="action-buttons">
                                        <a href="events.php?edit=<?= $ev['event_id'] ?>" class="btn-sm btn-edit" title="Edit">&#9998; Edit</a>
                                        <form action="events.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete event <?= sanitize_out($ev['title']) ?>?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="event_id" value="<?= $ev['event_id'] ?>">
                                            <button type="submit" class="btn-sm btn-delete" title="Delete">&#128465; Delete</button>
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
