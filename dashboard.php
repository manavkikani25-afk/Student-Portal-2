<?php
/**
 * CampusSync - Student Portal Dashboard
 * Role-Based Access: Student profile, enrolled courses, timetable preview, and registered events.
 */

require_once __DIR__ . '/auth.php';
requireLogin();

$userId = (int)$_SESSION['user_id'];
$userName = $_SESSION['full_name'] ?? 'Student';
$userEmail = $_SESSION['email'] ?? '';
$userRole = $_SESSION['role'] ?? 'student';

// Handle Event Registration
$regMsg = "";
$regType = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register_event') {
    $eventId = (int)($_POST['event_id'] ?? 0);
    if ($eventId > 0) {
        // Check if already registered
        $chk = $conn->prepare("SELECT registration_id FROM registrations WHERE event_id = ? AND user_id = ?");
        $chk->bind_param("ii", $eventId, $userId);
        $chk->execute();
        $chk->store_result();

        if ($chk->num_rows > 0) {
            $regMsg = "You are already registered for this campus event.";
            $regType = "info";
        } else {
            $regStmt = $conn->prepare("INSERT INTO registrations (event_id, user_id) VALUES (?, ?)");
            $regStmt->bind_param("ii", $eventId, $userId);
            if ($regStmt->execute()) {
                $regMsg = "Successfully registered for the event! Confirmation has been logged.";
                $regType = "success";
                logAudit($conn, $userId, $userName, 'EVENT_REGISTER', "Student registered for event ID: $eventId");
            } else {
                $regMsg = "Registration failed. Please try again.";
                $regType = "error";
            }
            $regStmt->close();
        }
        $chk->close();
    }
}

// Fetch user profile info
$userQuery = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$userQuery->bind_param("i", $userId);
$userQuery->execute();
$profile = $userQuery->get_result()->fetch_assoc();
$userQuery->close();

// Fetch events registered by this student
$myEventsStmt = $conn->prepare("
    SELECT e.event_id, e.title, e.category, e.event_date, e.event_time, e.venue, r.registered_at
    FROM registrations r
    JOIN events e ON e.event_id = r.event_id
    WHERE r.user_id = ?
    ORDER BY e.event_date ASC
");
$myEventsStmt->bind_param("i", $userId);
$myEventsStmt->execute();
$myEvents = $myEventsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$myEventsStmt->close();

// Fetch upcoming events available for registration
$availEventsStmt = $conn->prepare("
    SELECT * FROM events
    WHERE event_date >= CURDATE()
    AND event_id NOT IN (SELECT event_id FROM registrations WHERE user_id = ?)
    ORDER BY event_date ASC
    LIMIT 4
");
$availEventsStmt->bind_param("i", $userId);
$availEventsStmt->execute();
$availEvents = $availEventsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$availEventsStmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | CampusSync</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
</head>
<body class="page-student-dash">
    <!-- Navbar -->
    <header class="header">
        <div class="logo-section">
            <div class="brand-badge">CS</div>
            <a href="Home.html" class="brand-title">Campus<span>Sync</span></a>
        </div>
        <button class="menu-button" id="menuButton" aria-label="Toggle menu">&#9776;</button>
        <nav class="navbar" id="navbar">
            <a href="dashboard.php" class="active">My Dashboard</a>
            <a href="Timetable.html">Timetable</a>
            <a href="StudyMaterial.html">Study Material</a>
            <a href="events.html">Campus Events</a>
            <a href="Help.html">Helpdesk</a>
            <span class="user-greeting">&#127891; <?= sanitize_out($userName) ?></span>
            <a href="logout.php" class="btn-logout">Logout</a>
            <button id="themeToggle" class="theme-btn" title="Toggle Theme">&#9790;</button>
        </nav>
    </header>

    <main class="admin-container">
        <!-- Welcome Banner -->
        <div class="dashboard-banner">
            <div>
                <h2>Hello, <?= sanitize_out($userName) ?>! &#128075;</h2>
                <p>Course: <strong><?= sanitize_out($profile['course'] ?? 'Engineering') ?></strong> &bull; Year: <strong><?= sanitize_out($profile['year'] ?? 1) ?></strong> &bull; Status: <span class="badge badge-success">Active Enrolled</span></p>
            </div>
            <div class="banner-quick-actions">
                <a href="Timetable.html" class="btn btn-primary">&#128197; View Weekly Timetable</a>
                <a href="StudyMaterial.html" class="btn btn-secondary">&#128218; Study Resources</a>
            </div>
        </div>

        <?php if (!empty($regMsg)): ?>
            <div class="alert alert-<?= $regType ?>">
                <?= sanitize_out($regMsg) ?>
            </div>
        <?php endif; ?>

        <!-- Quick Access Shortcuts Grid -->
        <section class="quick-grid">
            <a href="Timetable.html" class="quick-card">
                <div class="quick-icon">&#128197;</div>
                <h4>Class Timetable</h4>
                <p>Check daily lectures, labs, and classroom allocations.</p>
            </a>

            <a href="StudyMaterial.html" class="quick-card">
                <div class="quick-icon">&#128218;</div>
                <h4>Study Material</h4>
                <p>Download subject notes, question banks, and lab manuals.</p>
            </a>

            <a href="events.html" class="quick-card">
                <div class="quick-icon">&#127881;</div>
                <h4>Campus Events</h4>
                <p>Explore cultural fests, technical hackathons, and sports.</p>
            </a>

            <a href="Help.html" class="quick-card">
                <div class="quick-icon">&#128222;</div>
                <h4>Student Helpdesk</h4>
                <p>Submit inquiries, feedback, or academic assistance requests.</p>
            </a>
        </section>

        <!-- Two Columns: My Registered Events vs Available Events -->
        <div class="two-col-grid" style="margin-top: 30px;">
            <!-- Registered Events -->
            <div class="card">
                <h3>My Registered Events (<?= count($myEvents) ?>)</h3>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Event</th>
                                <th>Category</th>
                                <th>Date &amp; Time</th>
                                <th>Venue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($myEvents)): ?>
                                <tr>
                                    <td colspan="4" class="text-center">You have not registered for any events yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($myEvents as $me): ?>
                                    <tr>
                                        <td><strong><?= sanitize_out($me['title']) ?></strong></td>
                                        <td><span class="badge badge-category"><?= sanitize_out($me['category']) ?></span></td>
                                        <td><?= date("d M Y", strtotime($me['event_date'])) ?> <?= date("h:i A", strtotime($me['event_time'])) ?></td>
                                        <td><?= sanitize_out($me['venue']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Available Events to Register -->
            <div class="card">
                <h3>Upcoming Events Open for Registration</h3>
                <?php if (empty($availEvents)): ?>
                    <p class="text-muted" style="padding: 20px 0;">No new events available right now or you have already registered for all upcoming events.</p>
                <?php else: ?>
                    <div class="event-signup-list">
                        <?php foreach ($availEvents as $ae): ?>
                            <div class="event-signup-item">
                                <div>
                                    <h4 style="margin: 0 0 5px 0;"><?= sanitize_out($ae['title']) ?></h4>
                                    <span class="badge badge-category"><?= sanitize_out($ae['category']) ?></span> &bull; 
                                    <small><?= date("d M Y", strtotime($ae['event_date'])) ?> &bull; <?= sanitize_out($ae['venue']) ?></small>
                                </div>
                                <form action="dashboard.php" method="POST">
                                    <input type="hidden" name="action" value="register_event">
                                    <input type="hidden" name="event_id" value="<?= $ae['event_id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-primary">Register Now</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>
