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

// Get the book_id, user_id, and borrow_date from the request
$getBookStmt = $conn->prepare('SELECT book_id, user_id, book_title, created_at FROM borrow_requests WHERE id = ?');
$getBookStmt->bind_param('i', $requestId);
$getBookStmt->execute();
$bookResult = $getBookStmt->get_result();
$bookRow = $bookResult->fetch_assoc();
$getBookStmt->close();

if ($bookRow) {
    $bookId = $bookRow['book_id'];
    $userId = $bookRow['user_id'];
    $bookTitle = $bookRow['book_title'];
    $borrowDate = $bookRow['created_at'];
    
    // Increase available count in books table
    $updateBookStmt = $conn->prepare('UPDATE books SET available = available + 1 WHERE id = ?');
    $updateBookStmt->bind_param('i', $bookId);
    $updateBookStmt->execute();
    $updateBookStmt->close();
    
    // Calculate days borrowed
    $borrowDateTime = new DateTime($borrowDate);
    $returnDateTime = new DateTime();
    $interval = $borrowDateTime->diff($returnDateTime);
    $daysBorrowed = $interval->days;

    // Create return history entry
    $historyStmt = $conn->prepare('INSERT INTO return_history (user_id, book_id, book_title, borrow_date, days_borrowed) VALUES (?, ?, ?, ?, ?)');
    $historyStmt->bind_param('iissi', $userId, $bookId, $bookTitle, $borrowDate, $daysBorrowed);
    $historyStmt->execute();
    $historyStmt->close();
    
}

// Update request status to returned
$stmt = $conn->prepare('UPDATE borrow_requests SET status = ? WHERE id = ?');
$status = 'returned';
$stmt->bind_param('si', $status, $requestId);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to return book']);
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
