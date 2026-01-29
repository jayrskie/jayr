<?php
session_start();
require_once 'connect.php';

header('Content-Type: application/json');

try {
    // Check if user is admin
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        throw new Exception('Unauthorized access');
    }

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
                bb.return_status,
                b.title as book_title, 
                b.author,
                COALESCE(u.name, bb.guest_name) as user_name,
                COALESCE(u.email, "Guest") as user_email
              FROM borrowed_books bb
              JOIN books b ON bb.book_id = b.id
              LEFT JOIN users u ON bb.user_id = u.id
              WHERE bb.return_date IS NOT NULL
              ORDER BY bb.return_date DESC';

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
