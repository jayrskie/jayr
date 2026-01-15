<?php
session_start();
header('Content-Type: application/json');

// Disable error display for JSON responses
ini_set('display_errors', 0);
error_reporting(0);

try {
    // Admin-only check
    if (!isset($_SESSION['library_id']) || $_SESSION['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Forbidden']);
        exit;
    }

    require_once __DIR__ . '/../config.php';

    // Check database connection
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database connection failed']);
        exit;
    }

    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);

    $requestId = $data['request_id'] ?? null;

    if (!$requestId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing request_id']);
        exit;
    }

    // Get the book_id, user_id, and borrow_date from the request
    $getBookStmt = $conn->prepare('SELECT book_id, user_id, book_title, created_at, requested_duration FROM borrow_requests WHERE id = ?');
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

        // Get expires_at from borrow_history
        $getExpiresStmt = $conn->prepare('SELECT borrow_date, expires_at FROM borrow_history WHERE user_id = ? AND book_id = ? ORDER BY borrow_date DESC LIMIT 1');
        $getExpiresStmt->bind_param('ii', $userId, $bookId);
        $getExpiresStmt->execute();
        $expiresResult = $getExpiresStmt->get_result();
        $expiresRow = $expiresResult->fetch_assoc();
        $getExpiresStmt->close();

        if ($expiresRow) {
            $actualBorrowDate = $expiresRow['borrow_date'];
            $expiresAt = $expiresRow['expires_at'];
        } else {
            // Fallback
            $actualBorrowDate = $borrowDate;
            $expiresAt = date('Y-m-d H:i:s', strtotime($borrowDate . ' + ' . $bookRow['requested_duration'] . ' days'));
        }

        // Calculate return status
        $returnDate = date('Y-m-d H:i:s');
        if (strtotime($returnDate) < strtotime($expiresAt)) {
            $returnStatus = 'Early';
        } elseif (strtotime($returnDate) > strtotime($expiresAt)) {
            $returnStatus = 'Late';
        } else {
            $returnStatus = 'On Time';
        }

        error_log('Processing return for book ID: ' . $bookId . ', user ID: ' . $userId . ', status: ' . $returnStatus);

        // Increase available count in books table
        $updateBookStmt = $conn->prepare('UPDATE books SET available = available + 1 WHERE id = ?');
        $updateBookStmt->bind_param('i', $bookId);
        if (!$updateBookStmt->execute()) {
            error_log('Failed to update book availability: ' . $updateBookStmt->error);
        }
        $updateBookStmt->close();

        // Create return history entry (let return_date use DEFAULT CURRENT_TIMESTAMP)
        $historyStmt = $conn->prepare('INSERT INTO return_history (user_id, book_id, book_title, borrow_date, return_status) VALUES (?, ?, ?, ?, ?)');
        $historyStmt->bind_param('iisss', $userId, $bookId, $bookTitle, $actualBorrowDate, $returnStatus);
        if (!$historyStmt->execute()) {
            error_log('Failed to insert return history: ' . $historyStmt->error);
        }
        $historyStmt->close();

    }

    // Update request status to returned (optional - will not fail if not supported)
    $stmt = $conn->prepare('UPDATE borrow_requests SET status = ? WHERE id = ?');
    $status = 'returned';
    $stmt->bind_param('si', $status, $requestId);

    if (!$stmt->execute()) {
        error_log('Failed to update borrow request status: ' . $stmt->error . ' (this may be expected if status enum doesn\'t include \'returned\')');
        // Don't fail the entire operation for this
    }

    $stmt->close();

    $fetchStmt = $conn->prepare('SELECT id, user_id, book_id, book_title, status, created_at, updated_at, requested_duration FROM borrow_requests WHERE id = ?');
    $fetchStmt->bind_param('i', $requestId);
    $fetchStmt->execute();
    $result = $fetchStmt->get_result();
    $request = $result->fetch_assoc();
    $fetchStmt->close();

    echo json_encode(['success' => true, 'request' => $request]);

} catch (Exception $e) {
    error_log('Return book exception: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error: ' . $e->getMessage()]);
}
