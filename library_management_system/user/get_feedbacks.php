<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['library_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden']);
    exit;
}

require_once '../config.php';

$query = 'SELECT f.id, f.user_id, f.feedback_text, f.created_at, u.library_id, u.username
          FROM feedback f
          JOIN users u ON f.user_id = u.id
          ORDER BY f.created_at DESC';

$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();
$feedbacks = [];

while ($row = $result->fetch_assoc()) {
    $feedbacks[] = $row;
}

$stmt->close();
$conn->close();

echo json_encode(['success' => true, 'feedbacks' => $feedbacks]);
?>