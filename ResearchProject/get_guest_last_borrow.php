<?php
session_start();
require_once 'connect.php';

header('Content-Type: application/json');

try {
    // Check if user is admin
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        throw new Exception('Unauthorized access');
    }

    if (!isset($_GET['guest_name'])) {
        throw new Exception('Guest name is required');
    }

    $guest_name = trim($_GET['guest_name']);

    // Get guest's last borrow to use as template for renewal
    $query = 'SELECT 
                bb.borrow_type,
                bb.borrow_duration,
                bb.borrow_schedule,
                bb.book_copy_id,
                b.title,
                bc.accession_code
              FROM borrowed_books bb
              JOIN borrow_requests br ON bb.borrow_request_id = br.id
              JOIN books b ON bb.book_id = b.id
              LEFT JOIN book_copies bc ON bb.book_copy_id = bc.id
              WHERE br.guest_name = ? AND br.user_id IS NULL
              ORDER BY bb.borrow_date DESC
              LIMIT 1';

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Database prepare error: ' . $conn->error);
    }

    $stmt->bind_param('s', $guest_name);
    $stmt->execute();
    $result = $stmt->get_result();
    $lastBorrow = $result->fetch_assoc();

    if ($lastBorrow) {
        echo json_encode([
            'success' => true,
            'borrow_type' => $lastBorrow['borrow_type'],
            'borrow_duration' => $lastBorrow['borrow_duration'],
            'borrow_schedule' => $lastBorrow['borrow_schedule'],
            'book_title' => $lastBorrow['title'],
            'accession_code' => $lastBorrow['accession_code']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No previous borrow found for this guest'
        ]);
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
