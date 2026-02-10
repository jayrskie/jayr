<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

session_start();
require_once 'connect.php';
require_once 'email_helper.php';

header('Content-Type: application/json');

try {
    // Check if user is admin
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        throw new Exception('Unauthorized access');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $book_id = intval($_POST['book_id'] ?? 0);
    $borrow_type = $_POST['borrow_type'] ?? null;
    $borrow_duration = intval($_POST['borrow_duration'] ?? 0);
    $borrow_schedule = $_POST['borrow_schedule'] ?? 'am';
    $book_copy_id = intval($_POST['book_copy_id'] ?? 0);
    $user_id = intval($_POST['user_id'] ?? 0);

    if (!$user_id || !$book_id || !$borrow_type) {
        throw new Exception('Missing required fields');
    }

    if ($borrow_type === 'takehome' && !$borrow_duration) {
        throw new Exception('Borrow duration is required for take-home books');
    }

    // Set irrelevant fields to NULL based on borrow type
    if ($borrow_type === 'takehome') {
        $borrow_schedule = NULL;
    } else if ($borrow_type === 'classroom') {
        $borrow_duration = NULL;
    }

    // Enforce maximum borrow duration of 7 days for take-home
    if ($borrow_type === 'takehome') {
        if ($borrow_duration < 1) {
            throw new Exception('Borrow duration must be at least 1 day');
        }
        if ($borrow_duration > 7) {
            throw new Exception('Borrow duration cannot exceed 7 days');
        }
    }
    // Check if book exists and is available
    $bookQuery = 'SELECT id, available FROM books WHERE id = ?';
    $bookStmt = $conn->prepare($bookQuery);
    if (!$bookStmt) {
        throw new Exception('Database prepare error: ' . $conn->error);
    }
    $bookStmt->bind_param('i', $book_id);
    $bookStmt->execute();
    $bookResult = $bookStmt->get_result();
    $book = $bookResult->fetch_assoc();

    if (!$book) {
        throw new Exception('Book not found');
    }

    if ($book['available'] <= 0) {
        throw new Exception('Book is not available');
    }

    // Check borrow limit - users can have max 2 unreturned books
    $borrowLimitQuery = 'SELECT COUNT(*) as unreturned_count FROM borrowed_books WHERE user_id = ? AND return_date IS NULL';
    $borrowLimitStmt = $conn->prepare($borrowLimitQuery);
    if (!$borrowLimitStmt) {
        throw new Exception('Database prepare error: ' . $conn->error);
    }
    $borrowLimitStmt->bind_param('i', $user_id);
    $borrowLimitStmt->execute();
    $borrowLimitResult = $borrowLimitStmt->get_result();
    $borrowLimitData = $borrowLimitResult->fetch_assoc();
    
    if ($borrowLimitData['unreturned_count'] >= 2) {
        throw new Exception('User has reached the borrow limit. They have ' . $borrowLimitData['unreturned_count'] . ' unreturned book(s). Please return at least one book before borrowing another.');
    }
    // Prevent lending the same book if the user already has it borrowed (not returned)
    $alreadyBorrowedQuery = 'SELECT bb.id FROM borrowed_books bb WHERE bb.user_id = ? AND bb.book_id = ? AND bb.return_date IS NULL';
    $alreadyBorrowedStmt = $conn->prepare($alreadyBorrowedQuery);
    if (!$alreadyBorrowedStmt) {
        throw new Exception('Database prepare error: ' . $conn->error);
    }
    $alreadyBorrowedStmt->bind_param('ii', $user_id, $book_id);
    $alreadyBorrowedStmt->execute();
    $alreadyBorrowedResult = $alreadyBorrowedStmt->get_result();
    if ($alreadyBorrowedResult && $alreadyBorrowedResult->num_rows > 0) {
        throw new Exception('User already has this book borrowed. Return it before lending again.');
    }
    $requestQuery = 'INSERT INTO borrow_requests (user_id, book_id, status, request_date, approved_date, request_type) VALUES (?, ?, "approved", NOW(), NOW(), "CCT")';
    $requestStmt = $conn->prepare($requestQuery);
    if (!$requestStmt) {
        throw new Exception('Database prepare error: ' . $conn->error);
    }

    $requestStmt->bind_param('ii', $user_id, $book_id);
    if (!$requestStmt->execute()) {
        throw new Exception('Failed to create borrow request: ' . $requestStmt->error);
    }

    $request_id = $requestStmt->insert_id;

    // Calculate due date
    $borrow_date = new DateTime();
    $due_date = clone $borrow_date;

    if ($borrow_type === 'takehome') {
        // Preserve borrow time when adding days so due time matches borrow time
        $due_date->add(new DateInterval('P' . $borrow_duration . 'D'));
    } else {
        // For classroom, due the same day at end of schedule
        if ($borrow_schedule === 'am') {
            $due_date->setTime(13, 0, 0); // 1 PM
        } else {
            $due_date->setTime(19, 0, 0); // 7 PM
        }
    }

    // Create borrowed book entry
    $due_date_str = $due_date->format('Y-m-d H:i:s');
    if ($book_copy_id) {
        $borrowQuery = 'INSERT INTO borrowed_books (borrow_request_id, user_id, book_id, book_copy_id, borrow_date, due_date, borrow_type, borrow_duration, borrow_schedule, return_status) VALUES (?, ?, ?, ?, NOW(), ?, ?, ?, ?, "pending")';
        $borrowStmt = $conn->prepare($borrowQuery);
        if (!$borrowStmt) {
            throw new Exception('Database prepare error: ' . $conn->error);
        }

        // Types: i (request_id), i (user_id), i (book_id), i (book_copy_id), s (due_date), s (borrow_type), i (borrow_duration), s (borrow_schedule)
        $borrowStmt->bind_param('iiiissis', $request_id, $user_id, $book_id, $book_copy_id, $due_date_str, $borrow_type, $borrow_duration, $borrow_schedule);
    } else {
        $borrowQuery = 'INSERT INTO borrowed_books (borrow_request_id, user_id, book_id, borrow_date, due_date, borrow_type, borrow_duration, borrow_schedule, return_status) VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, "pending")';
        $borrowStmt = $conn->prepare($borrowQuery);
        if (!$borrowStmt) {
            throw new Exception('Database prepare error: ' . $conn->error);
        }

        // Types: i (request_id), i (user_id), i (book_id), s (due_date), s (borrow_type), i (borrow_duration), s (borrow_schedule)
        $borrowStmt->bind_param('iiissis', $request_id, $user_id, $book_id, $due_date_str, $borrow_type, $borrow_duration, $borrow_schedule);
    }
    if (!$borrowStmt->execute()) {
        throw new Exception('Failed to create borrowed book entry: ' . $borrowStmt->error);
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

    // Decrease book availability
    $updateQuery = 'UPDATE books SET available = available - 1 WHERE id = ?';
    $updateStmt = $conn->prepare($updateQuery);
    if (!$updateStmt) {
        throw new Exception('Database prepare error: ' . $conn->error);
    }

    $updateStmt->bind_param('i', $book_id);
    if (!$updateStmt->execute()) {
        throw new Exception('Failed to update book availability: ' . $updateStmt->error);
    }

    // Get user email and send approval email
    $userStmt = $conn->prepare('SELECT name, email FROM users WHERE id = ?');
    $userStmt->bind_param('i', $user_id);
    $userStmt->execute();
    $userData = $userStmt->get_result()->fetch_assoc();
    
    $bookQuery = 'SELECT title, author FROM books WHERE id = ?';
    $bookStmt = $conn->prepare($bookQuery);
    $bookStmt->bind_param('i', $book_id);
    $bookStmt->execute();
    $bookData = $bookStmt->get_result()->fetch_assoc();
    
    if ($userData && $userData['email'] && $bookData) {
        // Format due date for email
        $emailDueDate = null;
        if ($borrow_type === 'takehome') {
            $emailDueDate = $due_date->format('F j, Y \a\t g:i A');
        } else {
            if ($borrow_schedule === 'am') {
                $emailDueDate = $due_date->format('F j, Y \\a\\t g:i A');
            } else {
                $emailDueDate = $due_date->format('F j, Y \\a\\t g:i A');
            }
        }
        
        $approvedDate = date('F j, Y \a\t g:i A');
        // Get accession code
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
        sendBorrowApprovalEmail($userData['email'], $userData['name'], $bookData['title'], $bookData['author'] ?? null, $emailDueDate, $borrow_type, $borrow_duration, $borrow_schedule, 'CCT', $approvedDate, $accessionCode);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Book lent successfully',
        'borrow_id' => $borrowStmt->insert_id,
        'request_id' => $request_id,
        'due_date' => $due_date_str
    ]);

} catch (Exception $e) {
    // Log error for debugging
    $logFile = __DIR__ . DIRECTORY_SEPARATOR . 'lend_book_direct_debug.log';
    $logEntry = "[" . date('Y-m-d H:i:s') . "] ERROR: " . $e->getMessage() . "\n";
    $logEntry .= "POST: " . json_encode($_POST) . "\n";
    $logEntry .= "\n";
    @file_put_contents($logFile, $logEntry, FILE_APPEND);

    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug_log' => basename($logFile)
    ]);
}

$conn->close();
?>
