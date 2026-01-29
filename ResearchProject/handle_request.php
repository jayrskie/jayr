<?php
session_start();
require_once 'connect.php';

header('Content-Type: application/json');

try {
    // Check if user is admin
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        throw new Exception('Unauthorized access');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    if (!isset($_POST['request_id']) || !isset($_POST['action'])) {
        throw new Exception('Missing required parameters');
    }

    $request_id = intval($_POST['request_id']);
    $action = $_POST['action']; // 'approve' or 'reject'

    // Validate action
    if (!in_array($action, ['approve', 'reject'])) {
        throw new Exception('Invalid action');
    }

    // Get the borrow request
    $getQuery = 'SELECT user_id, guest_name, book_id FROM borrow_requests WHERE id = ? AND status = "pending"';
    $getStmt = $conn->prepare($getQuery);
    if (!$getStmt) {
        throw new Exception('Database prepare error: ' . $conn->error);
    }
    
    $getStmt->bind_param('i', $request_id);
    $getStmt->execute();
    $borrowRequest = $getStmt->get_result()->fetch_assoc();
    
    if (!$borrowRequest) {
        throw new Exception('Borrow request not found or already processed');
    }

    if ($action === 'approve') {
        // Check if book is available before approving
        $checkAvailQuery = 'SELECT available FROM books WHERE id = ?';
        $checkAvailStmt = $conn->prepare($checkAvailQuery);
        if (!$checkAvailStmt) {
            throw new Exception('Database prepare error: ' . $conn->error);
        }
        $checkAvailStmt->bind_param('i', $borrowRequest['book_id']);
        $checkAvailStmt->execute();
        $availResult = $checkAvailStmt->get_result();
        $availBook = $availResult->fetch_assoc();

        if (!$availBook || $availBook['available'] <= 0) {
            throw new Exception('Book is not available - no copies left to lend');
        }

        // Check borrow limit - registered users can have max 2 unreturned books
        if ($borrowRequest['user_id'] !== null) {
            $borrowLimitQuery = 'SELECT COUNT(*) as unreturned_count FROM borrowed_books WHERE user_id = ? AND return_date IS NULL';
            $borrowLimitStmt = $conn->prepare($borrowLimitQuery);
            if (!$borrowLimitStmt) {
                throw new Exception('Database prepare error: ' . $conn->error);
            }
            $borrowLimitStmt->bind_param('i', $borrowRequest['user_id']);
            $borrowLimitStmt->execute();
            $borrowLimitResult = $borrowLimitStmt->get_result();
            $borrowLimitData = $borrowLimitResult->fetch_assoc();
            
            if ($borrowLimitData['unreturned_count'] >= 2) {
                throw new Exception('User has reached the borrow limit. They have ' . $borrowLimitData['unreturned_count'] . ' unreturned book(s). Please return at least one book before borrowing another.');
            }
        }

        // Update request status with request_type as Online
        $updateQuery = 'UPDATE borrow_requests SET status = "approved", approved_date = NOW(), request_type = "Online" WHERE id = ?';
        $updateStmt = $conn->prepare($updateQuery);
        if (!$updateStmt) {
            throw new Exception('Database prepare error: ' . $conn->error);
        }
        
        $updateStmt->bind_param('i', $request_id);
        
        if (!$updateStmt->execute()) {
            throw new Exception('Failed to approve request: ' . $updateStmt->error);
        }

        // Process configuration if provided
        $borrow_type = 'takehome';
        $borrow_duration = 7;
        $borrow_schedule = NULL;
        $book_copy_id = NULL;
        $dueDate = 'DATE_ADD(NOW(), INTERVAL 7 DAY)';

        if (isset($_POST['config'])) {
            $config = json_decode($_POST['config'], true);
            if ($config) {
                $borrow_type = $config['borrow_type'] ?? 'takehome';
                $book_copy_id = intval($config['book_copy_id'] ?? 0);
                
                if ($borrow_type === 'takehome') {
                    $borrow_duration = intval($config['borrow_days'] ?? 7);
                    if ($borrow_duration < 1) $borrow_duration = 1;
                    if ($borrow_duration > 7) $borrow_duration = 7;
                    $borrow_schedule = NULL;
                    $dueDate = 'DATE_ADD(NOW(), INTERVAL ' . $borrow_duration . ' DAY)';
                } else {
                    $borrow_schedule = $config['borrow_schedule'] ?? 'am';
                    $borrow_duration = NULL;
                    // For classroom use, set due date to 1pm for AM and 7pm for PM (same day)
                    if ($borrow_schedule === 'am') {
                        $dueDate = 'CONCAT(CURDATE(), " 13:00:00")'; // 1pm
                    } else {
                        $dueDate = 'CONCAT(CURDATE(), " 19:00:00")'; // 7pm
                    }
                }
            }
        }

        // Create borrowed book entry with configuration
        if ($book_copy_id) {
            $borrowedQuery = 'INSERT INTO borrowed_books (borrow_request_id, user_id, guest_name, book_id, book_copy_id, borrow_date, due_date, borrow_type, borrow_duration, borrow_schedule, return_status) VALUES (?, ?, ?, ?, ?, NOW(), ' . $dueDate . ', ?, ?, ?, "pending")';
            $borrowedStmt = $conn->prepare($borrowedQuery);
            if (!$borrowedStmt) {
                throw new Exception('Database prepare error: ' . $conn->error);
            }
            $borrowedStmt->bind_param('iisiisis', $request_id, $borrowRequest['user_id'], $borrowRequest['guest_name'], $borrowRequest['book_id'], $book_copy_id, $borrow_type, $borrow_duration, $borrow_schedule);
        } else {
            $borrowedQuery = 'INSERT INTO borrowed_books (borrow_request_id, user_id, guest_name, book_id, borrow_date, due_date, borrow_type, borrow_duration, borrow_schedule, return_status) VALUES (?, ?, ?, ?, NOW(), ' . $dueDate . ', ?, ?, ?, "pending")';
            $borrowedStmt = $conn->prepare($borrowedQuery);
            if (!$borrowedStmt) {
                throw new Exception('Database prepare error: ' . $conn->error);
            }
            $borrowedStmt->bind_param('iisisis', $request_id, $borrowRequest['user_id'], $borrowRequest['guest_name'], $borrowRequest['book_id'], $borrow_type, $borrow_duration, $borrow_schedule);
        }

        if (!$borrowedStmt->execute()) {
            throw new Exception('Failed to create borrowed book entry: ' . $borrowedStmt->error);
        }

        // Update book_copies status to "borrowed" if book_copy_id is provided
        if ($book_copy_id) {
            $updateCopyStatusQuery = 'UPDATE book_copies SET status = "borrowed" WHERE id = ?';
            $updateCopyStmt = $conn->prepare($updateCopyStatusQuery);
            if (!$updateCopyStmt) {
                throw new Exception('Database prepare error: ' . $conn->error);
            }
            $updateCopyStmt->bind_param('i', $book_copy_id);
            if (!$updateCopyStmt->execute()) {
                throw new Exception('Failed to update book copy status: ' . $updateCopyStmt->error);
            }
        }

        // Decrease available count
        $decreaseQuery = 'UPDATE books SET available = available - 1 WHERE id = ?';
        $decreaseStmt = $conn->prepare($decreaseQuery);
        if (!$decreaseStmt) {
            throw new Exception('Database prepare error: ' . $conn->error);
        }
        
        $decreaseStmt->bind_param('i', $borrowRequest['book_id']);
        
        if (!$decreaseStmt->execute()) {
            throw new Exception('Failed to update book availability: ' . $decreaseStmt->error);
        }

        // Mark user as verified if they are a registered user
        if ($borrowRequest['user_id'] !== null) {
            $verifyQuery = 'UPDATE users SET is_verified = 1 WHERE id = ?';
            $verifyStmt = $conn->prepare($verifyQuery);
            if (!$verifyStmt) {
                throw new Exception('Database prepare error: ' . $conn->error);
            }
            $verifyStmt->bind_param('i', $borrowRequest['user_id']);
            $verifyStmt->execute();
        }

        $message = 'Borrow request approved and configured successfully';
    } else {
        // Reject request
        $rejectQuery = 'UPDATE borrow_requests SET status = "rejected" WHERE id = ?';
        $rejectStmt = $conn->prepare($rejectQuery);
        if (!$rejectStmt) {
            throw new Exception('Database prepare error: ' . $conn->error);
        }
        
        $rejectStmt->bind_param('i', $request_id);
        
        if (!$rejectStmt->execute()) {
            throw new Exception('Failed to reject request: ' . $rejectStmt->error);
        }

        $message = 'Borrow request rejected';
    }

    echo json_encode([
        'success' => true,
        'message' => $message
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
