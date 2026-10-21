<?php
/**
 * CampusSync - Practical 8: Database Connectivity & PDO Query Demonstration
 */

require_once __DIR__ . '/db.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Practical 8 - Database Connectivity & PDO Demo | CampusSync</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .demo-container {
            max-width: 900px;
            margin: 40px auto;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
        }
        .code-box {
            background: #0f172a;
            color: #38bdf8;
            padding: 15px;
            border-radius: 8px;
            font-family: monospace;
            overflow-x: auto;
            margin: 15px 0;
        }
        .badge-conn {
            display: inline-block;
            padding: 6px 14px;
            background: #dcfce7;
            color: #166534;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
        }
    </style>
</head>
<body class="page-p8">
    <div class="demo-container">
        <a href="Home.html" class="btn btn-secondary" style="margin-bottom: 20px; display:inline-block;">&larr; Back to Portal</a>
        <h2>Practical 8: MySQL Schema &amp; PDO Connectivity Demonstration</h2>
        <p>This page validates the active database connection using <strong>PHP Data Objects (PDO)</strong> with prepared statements and utf8mb4 encoding.</p>

        <div style="margin: 20px 0;">
            Status: <span class="badge-conn">✓ Connected to Database: <?php echo htmlspecialchars($db_name); ?></span>
        </div>

        <h3>Active Table Records (First 5 Students):</h3>
        <?php
        try {
            $stmt = $pdo->prepare("SELECT student_id, enrollment_no, full_name, email, mobile, course, semester, status FROM students LIMIT 5");
            $stmt->execute();
            $students = $stmt->fetchAll();

            if (empty($students)) {
                echo "<p>No records found. Please import <code>database.sql</code>.</p>";
            } else {
                echo "<table class='data-table'>";
                echo "<thead><tr><th>Enrollment No</th><th>Full Name</th><th>Email</th><th>Course</th><th>Status</th></tr></thead>";
                echo "<tbody>";
                foreach ($students as $s) {
                    echo "<tr>";
                    echo "<td><strong>" . sanitize_out($s['enrollment_no']) . "</strong></td>";
                    echo "<td>" . sanitize_out($s['full_name']) . "</td>";
                    echo "<td>" . sanitize_out($s['email']) . "</td>";
                    echo "<td>" . sanitize_out($s['course']) . "</td>";
                    echo "<td><span class='badge badge-success'>" . sanitize_out($s['status']) . "</span></td>";
                    echo "</tr>";
                }
                echo "</tbody></table>";
            }
        } catch (Exception $ex) {
            echo "<p style='color:red;'>PDO Error: " . htmlspecialchars($ex->getMessage()) . "</p>";
        }
        ?>

        <div class="code-box">
// PDO Connection Snippet<br>
$pdo = new PDO("mysql:host=localhost;dbname=campussync_db;charset=utf8mb4", "root", "", [<br>
&nbsp;&nbsp;PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,<br>
&nbsp;&nbsp;PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC<br>
]);
        </div>
    </div>
</body>
</html>
