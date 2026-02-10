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

    if (!isset($_POST['borrowed_book_id']) || !isset($_POST['config'])) {
        throw new Exception('Missing required parameters');
    }

    $borrowed_book_id = intval($_POST['borrowed_book_id']);
    $config = json_decode($_POST['config'], true);

    if (!$config || !isset($config['borrow_type'])) {
        throw new Exception('Invalid configuration data');
    }

    // Prepare update query based on borrow type
    $borrow_type = $config['borrow_type'];
    $borrow_duration = null;
    $borrow_schedule = null;
    $book_copy_id = isset($config['book_copy_id']) && intval($config['book_copy_id']) > 0 ? intval($config['book_copy_id']) : null;

    if ($borrow_type === 'takehome') {
        if (!isset($config['borrow_days']) || $config['borrow_days'] < 1 || $config['borrow_days'] > 7) {
            throw new Exception('Invalid borrow days');
        }
        $borrow_duration = intval($config['borrow_days']);
    } else if ($borrow_type === 'classroom') {
        if (!isset($config['borrow_schedule']) || !in_array($config['borrow_schedule'], ['am', 'pm'])) {
            throw new Exception('Invalid schedule');
        }
        $borrow_schedule = $config['borrow_schedule'];
    } else {
        throw new Exception('Invalid borrow type');
    }

    // Get current borrow date to calculate new due date
    $getQuery = 'SELECT borrow_date FROM borrowed_books WHERE id = ?';
    $getStmt = $conn->prepare($getQuery);
    if (!$getStmt) {
        throw new Exception('Database prepare error: ' . $conn->error);
    }

    $getStmt->bind_param('i', $borrowed_book_id);
    if (!$getStmt->execute()) {
        throw new Exception('Failed to fetch borrow date: ' . $getStmt->error);
    }

    $result = $getStmt->get_result();
    $book = $result->fetch_assoc();
    if (!$book) {
        throw new Exception('Borrowed book not found');
    }

    $new_due_date = null;

    // Calculate new due date based on borrow type
    if ($borrow_type === 'takehome') {
        // Add duration days to borrow date
        $borrow_date_time = new DateTime($book['borrow_date']);
        $borrow_date_time->add(new DateInterval("P{$borrow_duration}D"));
        $new_due_date = $borrow_date_time->format('Y-m-d H:i:s');
    } else if ($borrow_type === 'classroom') {
        // For classroom use, due date is today at 1pm (am) or 7pm (pm)
        if ($borrow_schedule === 'am') {
            $new_due_date = date('Y-m-d 13:00:00'); // 1pm
        } else {
            $new_due_date = date('Y-m-d 19:00:00'); // 7pm
        }
    }

    // Update borrowed books table with configuration and new due date
    if ($book_copy_id) {
        $updateQuery = 'UPDATE borrowed_books SET borrow_type = ?, borrow_duration = ?, borrow_schedule = ?, due_date = ?, book_copy_id = ? WHERE id = ?';
        $updateStmt = $conn->prepare($updateQuery);
        if (!$updateStmt) {
            throw new Exception('Database prepare error: ' . $conn->error);
        }
        $updateStmt->bind_param('sissii', $borrow_type, $borrow_duration, $borrow_schedule, $new_due_date, $book_copy_id, $borrowed_book_id);
    } else {
        $updateQuery = 'UPDATE borrowed_books SET borrow_type = ?, borrow_duration = ?, borrow_schedule = ?, due_date = ? WHERE id = ?';
        $updateStmt = $conn->prepare($updateQuery);
        if (!$updateStmt) {
            throw new Exception('Database prepare error: ' . $conn->error);
        }
        $updateStmt->bind_param('sissi', $borrow_type, $borrow_duration, $borrow_schedule, $new_due_date, $borrowed_book_id);
    }

    if (!$updateStmt->execute()) {
        throw new Exception('Failed to save configuration: ' . $updateStmt->error);
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

    echo json_encode([
        'success' => true,
        'message' => 'Borrow configuration saved successfully'
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
