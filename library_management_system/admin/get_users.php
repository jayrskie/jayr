<?php
session_start();
header('Content-Type: application/json');

// Check session
if (!isset($_SESSION['library_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

require_once '../config.php';

// Try a prepared select with the expected columns; fall back to a generic select if it fails
$users = [];
$error = null;

$stmt = $conn->prepare('SELECT id, library_id, username, role FROM users ORDER BY id DESC');
if ($stmt) {
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    } else {
        $error = 'Execute failed: ' . $stmt->error;
    }
    $stmt->close();
} else {
    // prepare failed, attempt generic query (maybe different column names)
    $result = $conn->query('SELECT * FROM users ORDER BY id DESC');
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    } else {
        $error = 'Query failed: ' . $conn->error;
    }
}

if ($error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $error]);
} else {
    echo json_encode(['success' => true, 'users' => $users]);
}

$conn->close();
?>
