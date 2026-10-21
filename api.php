<?php
/**
 * CampusSync - RESTful JSON API Endpoint (Practical 13)
 * Supports GET (list/single), POST (create), PUT (update), and DELETE without page reload.
 */

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/db.php';

function jsonResponse($statusCode, $success, $message, $data = null) {
    http_response_code($statusCode);
    $response = [
        "success"   => $success,
        "message"   => $message,
        "timestamp" => date("c")
    ];
    if ($data !== null) {
        $response["data"] = $data;
    }
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

// Parse incoming JSON body for POST/PUT/DELETE
$rawBody = file_get_contents("php://input");
$input = json_decode($rawBody, true) ?? [];

// 1. GET Request: List all events or get single event
if ($method === 'GET') {
    if (isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $stmt = $conn->prepare("SELECT * FROM events WHERE event_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $event = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($event) {
            jsonResponse(200, true, "Event fetched successfully.", $event);
        } else {
            jsonResponse(404, false, "Event not found with ID: $id");
        }
    } else {
        // Query with optional category filter
        $cat = trim($_GET['category'] ?? '');
        if (!empty($cat)) {
            $stmt = $conn->prepare("SELECT * FROM events WHERE category = ? ORDER BY event_date ASC");
            $stmt->bind_param("s", $cat);
            $stmt->execute();
            $events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        } else {
            $result = $conn->query("SELECT * FROM events ORDER BY event_date ASC");
            $events = $result->fetch_all(MYSQLI_ASSOC);
        }
        jsonResponse(200, true, "Events retrieved successfully.", $events);
    }
}

// 2. POST Request: Add new event
elseif ($method === 'POST') {
    $title = trim($input['title'] ?? $_POST['title'] ?? '');
    $category = trim($input['category'] ?? $_POST['category'] ?? 'Technical');
    $eventDate = trim($input['event_date'] ?? $_POST['event_date'] ?? '');
    $eventTime = trim($input['event_time'] ?? $_POST['event_time'] ?? '10:00:00');
    $venue = trim($input['venue'] ?? $_POST['venue'] ?? '');
    $description = trim($input['event_description'] ?? $_POST['event_description'] ?? '');

    if (empty($title) || empty($eventDate) || empty($venue)) {
        jsonResponse(400, false, "Title, event_date, and venue fields are required.");
    }

    $stmt = $conn->prepare("INSERT INTO events (title, category, event_date, event_time, venue, event_description, status) VALUES (?, ?, ?, ?, ?, ?, 'Upcoming')");
    $stmt->bind_param("ssssss", $title, $category, $eventDate, $eventTime, $venue, $description);

    if ($stmt->execute()) {
        $newId = $conn->insert_id;
        $stmt->close();
        jsonResponse(201, true, "Event created asynchronously via JSON API.", ["event_id" => $newId, "title" => $title]);
    } else {
        jsonResponse(500, false, "Database insert failure: " . $stmt->error);
    }
}

// 3. PUT Request: Update event
elseif ($method === 'PUT') {
    $id = (int)($input['event_id'] ?? $_GET['id'] ?? 0);
    if ($id <= 0) {
        jsonResponse(400, false, "Valid event_id is required for update.");
    }

    $title = trim($input['title'] ?? '');
    $category = trim($input['category'] ?? 'Technical');
    $eventDate = trim($input['event_date'] ?? '');
    $eventTime = trim($input['event_time'] ?? '');
    $venue = trim($input['venue'] ?? '');
    $description = trim($input['event_description'] ?? '');
    $status = trim($input['status'] ?? 'Upcoming');

    if (empty($title) || empty($eventDate) || empty($venue)) {
        jsonResponse(400, false, "Title, event_date, and venue cannot be empty.");
    }

    $stmt = $conn->prepare("UPDATE events SET title = ?, category = ?, event_date = ?, event_time = ?, venue = ?, event_description = ?, status = ? WHERE event_id = ?");
    $stmt->bind_param("sssssssi", $title, $category, $eventDate, $eventTime, $venue, $description, $status, $id);

    if ($stmt->execute()) {
        $stmt->close();
        jsonResponse(200, true, "Event updated successfully via JSON API.", ["event_id" => $id]);
    } else {
        jsonResponse(500, false, "Update query error: " . $stmt->error);
    }
}

// 4. DELETE Request: Delete event
elseif ($method === 'DELETE') {
    $id = (int)($input['event_id'] ?? $_GET['id'] ?? 0);
    if ($id <= 0) {
        jsonResponse(400, false, "Valid event_id is required for deletion.");
    }

    $stmt = $conn->prepare("DELETE FROM events WHERE event_id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $stmt->close();
        jsonResponse(200, true, "Event ID $id deleted successfully via JSON API.");
    } else {
        jsonResponse(500, false, "Deletion error: " . $stmt->error);
    }
}

else {
    jsonResponse(405, false, "Method $method not allowed.");
}
?>
