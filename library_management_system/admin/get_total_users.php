<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['library_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden']);
    exit;
}

require_once '../config.php';

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'DB connection failed']);
    exit;
}

$stmt = $conn->prepare('SELECT COUNT(*) as total FROM users');
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Prepare failed: ' . $conn->error]);
    exit;
}

$stmt->execute();
$result = $stmt->get_result();
if (!$result) {
    echo json_encode(['success' => false, 'error' => 'Execute failed: ' . $stmt->error]);
    exit;
}

$row = $result->fetch_assoc();
$totalUsers = $row['total'] ?? 0;
$stmt->close();

$conn->close();

echo json_encode(['success' => true, 'total_users' => $totalUsers]);
?>