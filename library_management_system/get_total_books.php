<?php
session_start();
header('Content-Type: application/json');

require_once 'config.php';

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'DB connection failed']);
    exit;
}

$stmt = $conn->prepare('SELECT SUM(quantity) as total FROM books');
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
$totalBooks = $row['total'] ?? 0;
$stmt->close();

$conn->close();

echo json_encode(['success' => true, 'total_books' => $totalBooks]);
?>