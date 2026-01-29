<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

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

    $user_type = $_POST['user_type'] ?? null;
    $book_id = intval($_POST['book_id'] ?? 0);
    $borrow_type = $_POST['borrow_type'] ?? null;
    $borrow_duration = intval($_POST['borrow_duration'] ?? 0);
    $borrow_schedule = $_POST['borrow_schedule'] ?? 'am';
    $book_copy_id = intval($_POST['book_copy_id'] ?? 0);
    $user_id = null;
    $guest_name = null;

    if (!$user_type || !$book_id || !$borrow_type) {
        throw new Exception('Missing required fields');
    }

    if ($user_type === 'registered') {
        $user_id = intval($_POST['user_id'] ?? 0);
        if (!$user_id) {
            throw new Exception('User ID is required for registered users');
        }
    } elseif ($user_type === 'guest') {
        $guest_name = $_POST['guest_name'] ?? null;
        if (!$guest_name) {
            throw new Exception('Guest name is required');
        }
    } else {
        throw new Exception('Invalid user type');
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

    // Check borrow limit - registered users can have max 2 unreturned books
    if ($user_id !== null) {
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
    }
    $requestQuery = 'INSERT INTO borrow_requests (user_id, guest_name, book_id, status, request_date, approved_date, request_type) VALUES (?, ?, ?, "approved", NOW(), NOW(), "CCT")';
    $requestStmt = $conn->prepare($requestQuery);
    if (!$requestStmt) {
        throw new Exception('Database prepare error: ' . $conn->error);
    }

    $requestStmt->bind_param('isi', $user_id, $guest_name, $book_id);
    if (!$requestStmt->execute()) {
        throw new Exception('Failed to create borrow request: ' . $requestStmt->error);
    }

    $request_id = $requestStmt->insert_id;

    // Calculate due date
    $borrow_date = new DateTime();
    $due_date = clone $borrow_date;

    if ($borrow_type === 'takehome') {
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
    if ($book_copy_id) {
        $borrowQuery = 'INSERT INTO borrowed_books (borrow_request_id, user_id, guest_name, book_id, book_copy_id, borrow_date, due_date, borrow_type, borrow_duration, borrow_schedule, return_status) VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, "pending")';
        $borrowStmt = $conn->prepare($borrowQuery);
        if (!$borrowStmt) {
            throw new Exception('Database prepare error: ' . $conn->error);
        }

        $due_date_str = $due_date->format('Y-m-d H:i:s');
        $borrowStmt->bind_param('iisiissis', $request_id, $user_id, $guest_name, $book_id, $book_copy_id, $due_date_str, $borrow_type, $borrow_duration, $borrow_schedule);
    } else {
        $borrowQuery = 'INSERT INTO borrowed_books (borrow_request_id, user_id, guest_name, book_id, borrow_date, due_date, borrow_type, borrow_duration, borrow_schedule, return_status) VALUES (?, ?, ?, ?, NOW(), ?, ?, ?, ?, "pending")';
        $borrowStmt = $conn->prepare($borrowQuery);
        if (!$borrowStmt) {
            throw new Exception('Database prepare error: ' . $conn->error);
        }

        $due_date_str = $due_date->format('Y-m-d H:i:s');
        $borrowStmt->bind_param('iisissis', $request_id, $user_id, $guest_name, $book_id, $due_date_str, $borrow_type, $borrow_duration, $borrow_schedule);
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

    // Mark registered user as verified
    if ($user_type === 'registered') {
        $verifyQuery = 'UPDATE users SET is_verified = 1 WHERE id = ?';
        $verifyStmt = $conn->prepare($verifyQuery);
        if (!$verifyStmt) {
            throw new Exception('Database prepare error: ' . $conn->error);
        }
        $verifyStmt->bind_param('i', $user_id);
        $verifyStmt->execute();
    }

    echo json_encode([
        'success' => true,
        'message' => 'Book lent successfully',
        'borrow_id' => $borrowStmt->insert_id,
        'request_id' => $request_id,
        'due_date' => $due_date_str
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
