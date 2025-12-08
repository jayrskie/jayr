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

// Update request status to approved
$stmt = $conn->prepare('UPDATE borrow_requests SET status = ? WHERE id = ?');
$status = 'approved';
$stmt->bind_param('si', $status, $requestId);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to approve request']);
    $stmt->close();
    exit;
}

$stmt->close();

// Get the book_id and user_id from the request to update availability
$getBookStmt = $conn->prepare('SELECT book_id, user_id, book_title FROM borrow_requests WHERE id = ?');
$getBookStmt->bind_param('i', $requestId);
$getBookStmt->execute();
$bookResult = $getBookStmt->get_result();
$bookRow = $bookResult->fetch_assoc();
$getBookStmt->close();

if ($bookRow) {
    $bookId = $bookRow['book_id'];
    $userId = $bookRow['user_id'];
    $bookTitle = $bookRow['book_title'];
    
    // Decrease available count in books table (only if available > 0)
    $updateBookStmt = $conn->prepare('UPDATE books SET available = available - 1 WHERE id = ? AND available > 0');
    $updateBookStmt->bind_param('i', $bookId);
    $updateBookStmt->execute();
    $updateBookStmt->close();
    
    // Create borrow history entry (will auto-expire after 7 days)
    $historyStmt = $conn->prepare('INSERT INTO borrow_history (user_id, book_id, book_title, expires_at) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 7 DAY))');
    $historyStmt->bind_param('iis', $userId, $bookId, $bookTitle);
    $historyStmt->execute();
    $historyStmt->close();
}

// Fetch updated request
$fetchStmt = $conn->prepare('SELECT id, user_id, book_id, book_title, status, created_at, updated_at FROM borrow_requests WHERE id = ?');
$fetchStmt->bind_param('i', $requestId);
$fetchStmt->execute();
$result = $fetchStmt->get_result();
$request = $result->fetch_assoc();
$fetchStmt->close();

echo json_encode(['success' => true, 'request' => $request]);
