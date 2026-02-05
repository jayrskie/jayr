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

    if (!isset($_POST['borrowed_book_id'])) {
        throw new Exception('Borrowed book ID is required');
    }

    $borrowed_book_id = intval($_POST['borrowed_book_id']);

    // Get the borrowed book details to calculate return status
    $getDetailsQuery = 'SELECT due_date, borrow_type FROM borrowed_books WHERE id = ?';
    $getDetailsStmt = $conn->prepare($getDetailsQuery);
    if (!$getDetailsStmt) {
        throw new Exception('Database prepare error: ' . $conn->error);
    }

    $getDetailsStmt->bind_param('i', $borrowed_book_id);
    $getDetailsStmt->execute();
    $borrowedDetails = $getDetailsStmt->get_result()->fetch_assoc();

    if (!$borrowedDetails) {
        throw new Exception('Borrowed book not found');
    }

    // Calculate return status
    try {
        $due_date = new DateTime($borrowedDetails['due_date']);
        $return_date = new DateTime();
        
        // Use the server's local timezone (don't force UTC conversion)
        // This ensures consistent comparison between database times and current time
        
        // Calculate timestamp difference in hours (positive = late, negative = early)
        $due_timestamp = $due_date->getTimestamp();
        $return_timestamp = $return_date->getTimestamp();
        $seconds_diff = $return_timestamp - $due_timestamp;
        $hours_diff = $seconds_diff / 3600;
        
        error_log("Due Date: " . $due_date->format('Y-m-d H:i:s T') . " (ts: $due_timestamp)");
        error_log("Return Date: " . $return_date->format('Y-m-d H:i:s T') . " (ts: $return_timestamp)");
        error_log("Difference in seconds: $seconds_diff, hours: $hours_diff");
        error_log("Borrow Type: " . $borrowedDetails['borrow_type']);
        
        $return_status = 'on_time';
        $on_time_before = 4; // 4 hours before due date counts as on time
        $on_time_after = 4; // 4 hours after due date counts as on time
        
        // Determine return status based on time difference
        if ($hours_diff < -$on_time_before) {
            // Returned more than 4 hours before due date
            $return_status = 'early';
        } elseif ($hours_diff > $on_time_after) {
            // Returned after 12 hours past due date - check if late or overdue
            if ($borrowedDetails['borrow_type'] === 'takehome') {
                // For Take Home: 12 hours late max before overdue
                if ($hours_diff <= 12) {
                    $return_status = 'late';
                } else {
                    $return_status = 'overdue';
                }
            } else {
                // For Classroom Use: 2 hours late max before overdue
                if ($hours_diff <= 2) {
                    $return_status = 'late';
                } else {
                    $return_status = 'overdue';
                }
            }
        }
        // else: -4 hours ≤ hours_diff ≤ 12 hours means on time (16-hour window)
        
        error_log("Calculated Return Status: $return_status");
    } catch (Exception $dateException) {
        error_log("Date calculation error: " . $dateException->getMessage());
        throw new Exception('Error calculating return status: ' . $dateException->getMessage());
    }

    // Calculate overdue hours if applicable
    $overdue_hours = null;
    if ($return_status === 'late' || $return_status === 'overdue') {
        // Calculate hours beyond the grace period
        if ($borrowedDetails['borrow_type'] === 'takehome') {
            // Grace period is 12 hours for take-home
            $grace_period = 12;
        } else {
            // Grace period is 2 hours for classroom
            $grace_period = 2;
        }
        
        // Overdue hours = hours past the grace period
        if ($hours_diff > $grace_period) {
            $overdue_hours = $hours_diff - $grace_period;
        } else {
            $overdue_hours = 0; // For 'late' status, no overdue yet
        }
        
        error_log("Grace Period: $grace_period hours, Overdue Hours: $overdue_hours");
    }

    // Update the borrowed book with return date, status, and overdue hours
    $updateQuery = 'UPDATE borrowed_books SET return_date = NOW(), return_status = ?, overdue_hours = ? WHERE id = ?';
    $updateStmt = $conn->prepare($updateQuery);
    if (!$updateStmt) {
        throw new Exception('Database prepare error: ' . $conn->error);
    }

    $updateStmt->bind_param('sdi', $return_status, $overdue_hours, $borrowed_book_id);
    
    if (!$updateStmt->execute()) {
        throw new Exception('Failed to mark book as returned: ' . $updateStmt->error);
    }

    // Get the borrowed book to increase availability
    $getQuery = 'SELECT book_id, book_copy_id FROM borrowed_books WHERE id = ?';
    $getStmt = $conn->prepare($getQuery);
    if (!$getStmt) {
        throw new Exception('Database prepare error: ' . $conn->error);
    }

    $getStmt->bind_param('i', $borrowed_book_id);
    $getStmt->execute();
    $borrowedBook = $getStmt->get_result()->fetch_assoc();

    if ($borrowedBook) {
        // Update book_copies status back to "available" if book_copy_id exists
        if ($borrowedBook['book_copy_id']) {
            $updateCopyStatusQuery = 'UPDATE book_copies SET status = "available" WHERE id = ?';
            $updateCopyStmt = $conn->prepare($updateCopyStatusQuery);
            if (!$updateCopyStmt) {
                throw new Exception('Database prepare error: ' . $conn->error);
            }
            $updateCopyStmt->bind_param('i', $borrowedBook['book_copy_id']);
            if (!$updateCopyStmt->execute()) {
                throw new Exception('Failed to update book copy status: ' . $updateCopyStmt->error);
            }
        }

        // Increase availability count
        $increaseQuery = 'UPDATE books SET available = available + 1 WHERE id = ?';
        $increaseStmt = $conn->prepare($increaseQuery);
        if (!$increaseStmt) {
            throw new Exception('Database prepare error: ' . $conn->error);
        }

        $increaseStmt->bind_param('i', $borrowedBook['book_id']);
        
        if (!$increaseStmt->execute()) {
            throw new Exception('Failed to update book availability: ' . $increaseStmt->error);
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Book marked as returned successfully',
        'return_status' => $return_status
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
