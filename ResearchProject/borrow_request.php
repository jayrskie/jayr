<?php
session_start();
require_once 'connect.php';

header('Content-Type: application/json');

try {
    if (!isset($_POST['book_id']) || empty($_POST['book_id'])) {
        throw new Exception('Book ID is required');
    }

    $book_id = intval($_POST['book_id']);
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

    // Require user to be logged in
    if ($user_id === null) {
        throw new Exception('You must be logged in to submit a borrow request. Please register and log in to continue.');
    }

    // Check if book exists
    $bookQuery = 'SELECT id, title, available FROM books WHERE id = ?';
    $stmt = $conn->prepare($bookQuery);
    if (!$stmt) {
        throw new Exception('Database prepare error: ' . $conn->error);
    }
    
    $stmt->bind_param('i', $book_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $book = $result->fetch_assoc();
    
    if (!$book) {
        throw new Exception('Book not found or archived');
    }

    // Check if book has available copies
    if ($book['available'] <= 0) {
        throw new Exception('Book is not available');
    }

    // Check if there's already a pending request for this book
    // For logged-in users
    $checkQuery = 'SELECT id FROM borrow_requests WHERE user_id = ? AND book_id = ? AND status = "pending"';
    $checkStmt = $conn->prepare($checkQuery);
    if (!$checkStmt) {
        throw new Exception('Database prepare error: ' . $conn->error);
    }
    
    $checkStmt->bind_param('ii', $user_id, $book_id);
    
    $checkStmt->execute();
    $existingRequest = $checkStmt->get_result()->fetch_assoc();
    
    if ($existingRequest) {
        throw new Exception('A pending request for this book already exists');
    }

    // Check if user already has this book borrowed (approved status with no return date)
    $borrowedQuery = 'SELECT br.id FROM borrow_requests br 
                      LEFT JOIN borrowed_books bb ON br.id = bb.borrow_request_id 
                      WHERE br.user_id = ? AND br.book_id = ? AND br.status = "approved" AND bb.return_date IS NULL';
    $borrowedStmt = $conn->prepare($borrowedQuery);
    if (!$borrowedStmt) {
        throw new Exception('Database prepare error: ' . $conn->error);
    }
    
    $borrowedStmt->bind_param('ii', $user_id, $book_id);
    $borrowedStmt->execute();
    $borrowedBook = $borrowedStmt->get_result()->fetch_assoc();
    
    if ($borrowedBook) {
        throw new Exception('You already have this book borrowed. Please return it before requesting another copy.');
    }

    // Check pending request limit - max 2 pending requests per user
    $limitQuery = 'SELECT COUNT(*) as pending_count FROM borrow_requests WHERE user_id = ? AND status = "pending"';
    $limitStmt = $conn->prepare($limitQuery);
    if (!$limitStmt) {
        throw new Exception('Database prepare error: ' . $conn->error);
    }
    
    $limitStmt->bind_param('i', $user_id);
    $limitStmt->execute();
    $limitResult = $limitStmt->get_result();
    $limitData = $limitResult->fetch_assoc();
    
    if ($limitData['pending_count'] >= 2) {
        throw new Exception('You have reached the pending request limit. You can only have a maximum of 2 pending borrow requests at a time. Please wait for your pending requests to be approved or rejected.');
    }

    // Create borrow request
    $insertQuery = 'INSERT INTO borrow_requests (user_id, book_id, status, request_date) VALUES (?, ?, "pending", NOW())';
    $insertStmt = $conn->prepare($insertQuery);
    if (!$insertStmt) {
        throw new Exception('Database prepare error: ' . $conn->error);
    }
    
    $insertStmt->bind_param('ii', $user_id, $book_id);
    
    if (!$insertStmt->execute()) {
        throw new Exception('Failed to create borrow request: ' . $insertStmt->error);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Borrow request submitted successfully'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>
