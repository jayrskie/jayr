<?php
session_start();
header('Content-Type: application/json');

// Admin-only check
if (!isset($_SESSION['library_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden']);
    exit;
}

require_once __DIR__ . '/../config.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

$requestId = $data['request_id'] ?? null;

if (!$requestId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing request_id']);
    exit;
}

// Update request status to rejected
$stmt = $conn->prepare('UPDATE borrow_requests SET status = ? WHERE id = ?');
$status = 'rejected';
$stmt->bind_param('si', $status, $requestId);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to reject request']);
    $stmt->close();
    exit;
}

$stmt->close();

$fetchStmt = $conn->prepare('SELECT id, user_id, book_id, book_title, status, created_at, updated_at FROM borrow_requests WHERE id = ?');
$fetchStmt->bind_param('i', $requestId);
$fetchStmt->execute();
$result = $fetchStmt->get_result();
$request = $result->fetch_assoc();
$fetchStmt->close();

echo json_encode(['success' => true, 'request' => $request]);
