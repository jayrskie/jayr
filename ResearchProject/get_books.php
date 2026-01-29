<?php
session_start();
require_once 'connect.php';

header('Content-Type: application/json');

try {
    // Get all books for users with copy counts
    $query = 'SELECT 
                b.id,
                b.title,
                b.isbn,
                b.author,
                b.category,
                b.shelf_location,
                COUNT(bc.id) as quantity,
                SUM(CASE WHEN bc.status = "available" THEN 1 ELSE 0 END) as available
              FROM books b
              LEFT JOIN book_copies bc ON b.id = bc.book_id
              GROUP BY b.id
              ORDER BY b.category ASC, b.title ASC';
    $result = $conn->query($query);

    if (!$result) {
        throw new Exception('Query failed: ' . $conn->error);
    }

    $books = [];
    while ($row = $result->fetch_assoc()) {
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
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
