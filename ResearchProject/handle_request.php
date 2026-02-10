<?php
session_start();
require_once 'connect.php';
require_once 'email_helper.php';

// Start output buffering and ensure fatal errors return JSON
ob_start();
header('Content-Type: application/json');
register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        // Capture and clear any output buffer
        $buf = '';
        if (ob_get_length()) {
            $buf = ob_get_clean();
        }

        // Log detailed error information for debugging
        error_log("FATAL ERROR in handle_request.php: " . print_r($err, true));
        if ($buf) {
            error_log("Output buffer before shutdown:\n" . $buf);
        }

        // Return JSON with a safe message and include debug info for local troubleshooting
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'A fatal error occurred on the server',
            'debug' => [
                'error' => $err,
                'output' => $buf
            ]
        ]);
        exit;
    }
});

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
    $getQuery = 'SELECT user_id, book_id FROM borrow_requests WHERE id = ? AND status = "pending"';
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

    // Since guest feature has been removed, all requests must have user_id
    if (!$borrowRequest['user_id']) {
        throw new Exception('Invalid borrow request - user ID is missing. This may be a legacy guest request that cannot be processed.');
    }

    // Get book title, author and user email (if registered user)
    $bookQuery = 'SELECT title, author FROM books WHERE id = ?';
    $bookStmt = $conn->prepare($bookQuery);
    $bookStmt->bind_param('i', $borrowRequest['book_id']);
    $bookStmt->execute();
    $bookData = $bookStmt->get_result()->fetch_assoc();
    $bookTitle = $bookData['title'] ?? 'Unknown Book';
    $bookAuthor = $bookData['author'] ?? null;

    $userEmail = null;
    $userName = null;
    $userQuery = 'SELECT name, email FROM users WHERE id = ?';
    $userStmt = $conn->prepare($userQuery);
    $userStmt->bind_param('i', $borrowRequest['user_id']);
    $userStmt->execute();
    $userData = $userStmt->get_result()->fetch_assoc();
    $userEmail = $userData['email'] ?? null;
    $userName = $userData['name'] ?? null;

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

        // Check borrow limit - users can have max 2 unreturned books
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
                $book_copy_id = isset($config['book_copy_id']) && intval($config['book_copy_id']) > 0 ? intval($config['book_copy_id']) : null;
                
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
            $borrowedQuery = 'INSERT INTO borrowed_books (borrow_request_id, user_id, book_id, book_copy_id, borrow_date, due_date, borrow_type, borrow_duration, borrow_schedule, return_status) VALUES (?, ?, ?, ?, NOW(), ' . $dueDate . ', ?, ?, ?, "pending")';
            $borrowedStmt = $conn->prepare($borrowedQuery);
            if (!$borrowedStmt) {
                throw new Exception('Database prepare error: ' . $conn->error);
            }
            $borrowedStmt->bind_param('iiiissi', $request_id, $borrowRequest['user_id'], $borrowRequest['book_id'], $book_copy_id, $borrow_type, $borrow_duration, $borrow_schedule);
        } else {
            $borrowedQuery = 'INSERT INTO borrowed_books (borrow_request_id, user_id, book_id, borrow_date, due_date, borrow_type, borrow_duration, borrow_schedule, return_status) VALUES (?, ?, ?, NOW(), ' . $dueDate . ', ?, ?, ?, "pending")';
            $borrowedStmt = $conn->prepare($borrowedQuery);
            if (!$borrowedStmt) {
                throw new Exception('Database prepare error: ' . $conn->error);
            }
            $borrowedStmt->bind_param('iiissi', $request_id, $borrowRequest['user_id'], $borrowRequest['book_id'], $borrow_type, $borrow_duration, $borrow_schedule);
        }

        if (!$borrowedStmt->execute()) {
            throw new Exception('Failed to create borrowed book entry: ' . $borrowedStmt->error);
        }

        error_log('Successfully created borrowed book entry for request ' . $request_id);

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
            error_log('Updated book copy ' . $book_copy_id . ' status to borrowed');
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

        error_log('Decreased availability for book ' . $borrowRequest['book_id']);

        // Calculate formatted due date for email
        $emailDueDate = null;
        if ($borrow_type === 'takehome') {
            // Preserve the borrow time when adding days so due time matches borrow time
            $emailDueDate = date('F j, Y \\a\\t g:i A', strtotime('+' . $borrow_duration . ' days'));
        } else {
            // Classroom returns: use exact 13:00 / 19:00 today with formatted time
            if ($borrow_schedule === 'am') {
                $emailDueDate = date('F j, Y \\a\\t g:i A', strtotime('today 13:00'));
            } else {
                $emailDueDate = date('F j, Y \\a\\t g:i A', strtotime('today 19:00'));
            }
        }

        // Send approval email to user if they have an email
        if ($userEmail && $userName) {
            try {
                $approvedDate = date('F j, Y \a\t g:i A');
                // Get accession code if book copy is selected
                $accessionCode = null;
                if ($book_copy_id) {
                    $accessionQuery = 'SELECT accession_code FROM book_copies WHERE id = ?';
                    $accessionStmt = $conn->prepare($accessionQuery);
                    if ($accessionStmt) {
                        $accessionStmt->bind_param('i', $book_copy_id);
                        $accessionStmt->execute();
                        $accessionResult = $accessionStmt->get_result();
                        $accessionRow = $accessionResult->fetch_assoc();
                        if ($accessionRow) {
                            $accessionCode = $accessionRow['accession_code'];
                        }
                    }
                }
                sendBorrowApprovalEmail($userEmail, $userName, $bookTitle, $bookAuthor, $emailDueDate, $borrow_type, $borrow_duration, $borrow_schedule, 'Online', $approvedDate, $accessionCode);
            } catch (Exception $emailEx) {
                // Log email error but don't fail the approval
                error_log('Email sending failed for request ' . $request_id . ': ' . $emailEx->getMessage());
            }
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

        // Send rejection email
        if ($userEmail) {
            $rejectionReason = $_POST['rejection_reason'] ?? 'Your borrow request has not been approved at this time.';
            sendBorrowRejectionEmail($userEmail, $userName, $bookTitle, $bookAuthor, $rejectionReason);
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
} finally {
    // Close all statements properly
    if (isset($getStmt) && $getStmt) $getStmt->close();
    if (isset($bookStmt) && $bookStmt) $bookStmt->close();
    if (isset($userStmt) && $userStmt) $userStmt->close();
    if (isset($checkAvailStmt) && $checkAvailStmt) $checkAvailStmt->close();
    if (isset($borrowLimitStmt) && $borrowLimitStmt) $borrowLimitStmt->close();
    if (isset($updateStmt) && $updateStmt) $updateStmt->close();
    if (isset($borrowedStmt) && $borrowedStmt) $borrowedStmt->close();
    if (isset($updateCopyStmt) && $updateCopyStmt) $updateCopyStmt->close();
    if (isset($decreaseStmt) && $decreaseStmt) $decreaseStmt->close();
    if (isset($accessionStmt) && $accessionStmt) $accessionStmt->close();
    if (isset($rejectStmt) && $rejectStmt) $rejectStmt->close();
}

$conn->close();
?>
