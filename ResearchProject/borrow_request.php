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
    $guest_name = isset($_POST['guest_name']) ? trim($_POST['guest_name']) : null;

    // Check if either user is logged in or guest name is provided
    if ($user_id === null && empty($guest_name)) {
        throw new Exception('User login or guest name is required');
    }

    // If guest, validate guest name
    if ($user_id === null && empty($guest_name)) {
        throw new Exception('Please provide a name');
    }

    if ($user_id === null && strlen($guest_name) < 5) {
        throw new Exception('Guest name must be at least 5 characters');
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
    if ($user_id !== null) {
        // For logged-in users
        $checkQuery = 'SELECT id FROM borrow_requests WHERE user_id = ? AND book_id = ? AND status = "pending"';
        $checkStmt = $conn->prepare($checkQuery);
        if (!$checkStmt) {
            throw new Exception('Database prepare error: ' . $conn->error);
        }
        
        $checkStmt->bind_param('ii', $user_id, $book_id);
    } else {
        // For guests
        $checkQuery = 'SELECT id FROM borrow_requests WHERE guest_name = ? AND book_id = ? AND status = "pending"';
        $checkStmt = $conn->prepare($checkQuery);
        if (!$checkStmt) {
            throw new Exception('Database prepare error: ' . $conn->error);
        }
        
        $checkStmt->bind_param('si', $guest_name, $book_id);
    }
    
    $checkStmt->execute();
    $existingRequest = $checkStmt->get_result()->fetch_assoc();
    
    if ($existingRequest) {
        throw new Exception('A pending request for this book already exists');
    }

    // Check pending request limit - max 2 pending requests per user
    if ($user_id !== null) {
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
            throw new Exception('You have reached the pending request limit. You can have a maximum of 2 pending borrow requests at a time. Please wait for your pending requests to be approved or rejected.');
        }
    } else {
        $limitQuery = 'SELECT COUNT(*) as pending_count FROM borrow_requests WHERE guest_name = ? AND status = "pending"';
        $limitStmt = $conn->prepare($limitQuery);
        if (!$limitStmt) {
            throw new Exception('Database prepare error: ' . $conn->error);
        }
        
        $limitStmt->bind_param('s', $guest_name);
        $limitStmt->execute();
        $limitResult = $limitStmt->get_result();
        $limitData = $limitResult->fetch_assoc();
        
        if ($limitData['pending_count'] >= 2) {
            throw new Exception('You have reached the pending request limit. You can have a maximum of 2 pending borrow requests at a time. Please wait for your pending requests to be approved or rejected.');
        }
    }

    // Create borrow request
    if ($user_id !== null) {
        $insertQuery = 'INSERT INTO borrow_requests (user_id, book_id, status, request_date) VALUES (?, ?, "pending", NOW())';
        $insertStmt = $conn->prepare($insertQuery);
        if (!$insertStmt) {
            throw new Exception('Database prepare error: ' . $conn->error);
        }
        
        $insertStmt->bind_param('ii', $user_id, $book_id);
    } else {
        $insertQuery = 'INSERT INTO borrow_requests (guest_name, book_id, status, request_date) VALUES (?, ?, "pending", NOW())';
        $insertStmt = $conn->prepare($insertQuery);
        if (!$insertStmt) {
            throw new Exception('Database prepare error: ' . $conn->error);
        }
        
        $insertStmt->bind_param('si', $guest_name, $book_id);
    }
    
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
