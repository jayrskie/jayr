<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['library_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once '../config.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

$feedbackText = trim($data['feedback'] ?? '');

if (empty($feedbackText)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Feedback cannot be empty']);
    exit;
}

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    // Fallback
    $stmt = $conn->prepare('SELECT id FROM users WHERE library_id = ?');
    $stmt->bind_param('s', $_SESSION['library_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $userId = $row['id'];
    }
    $stmt->close();
}

if (!$userId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit;
}

$stmt = $conn->prepare('INSERT INTO feedback (user_id, feedback_text) VALUES (?, ?)');
$stmt->bind_param('is', $userId, $feedbackText);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to submit feedback']);
}

$stmt->close();
$conn->close();
?>