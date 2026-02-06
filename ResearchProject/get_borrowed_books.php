<?php
session_start();
require_once 'connect.php';

header('Content-Type: application/json');

try {
    // Check if user is admin
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        throw new Exception('Unauthorized access');
    }

    error_log('get_borrowed_books.php called - Admin: ' . $_SESSION['user_role']);

    $query = 'SELECT 
                bb.id, 
                bb.borrow_request_id,
                bb.user_id, 
                bb.guest_name,
                bb.book_id, 
                bb.borrow_date, 
                bb.due_date,
                bb.return_date,
                bb.borrow_type,
                bb.borrow_duration,
                bb.borrow_schedule,
                bb.return_status,
                br.request_type,
                b.title as book_title, 
                b.author,
                COALESCE(u.name, bb.guest_name) as user_name,
                COALESCE(u.email, bb.guest_name) as user_email
              FROM borrowed_books bb
              LEFT JOIN books b ON bb.book_id = b.id
              LEFT JOIN users u ON bb.user_id = u.id
              LEFT JOIN borrow_requests br ON bb.borrow_request_id = br.id
              WHERE bb.return_date IS NULL
              ORDER BY bb.borrow_date DESC';

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Database prepare error: ' . $conn->error);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $books = [];
    while ($row = $result->fetch_assoc()) {
        // Add a flag to indicate if it's a guest borrow
        $row['is_guest'] = $row['user_id'] === null;
        $books[] = $row;
    }

    error_log('Borrowed books query returned ' . count($books) . ' books');

    echo json_encode([
        'success' => true,
        'books' => $books
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>
