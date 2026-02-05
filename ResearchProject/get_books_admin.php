<?php
session_start();
require_once 'connect.php';

header('Content-Type: application/json');

try {
    // Get all books for admin with their copies
    $query = 'SELECT 
                b.id,
                b.title,
                b.isbn,
                b.author,
                b.category,
                b.shelf_location,
                b.created_at,
                COUNT(bc.id) as quantity,
                SUM(CASE WHEN bc.status = "available" THEN 1 ELSE 0 END) as available
              FROM books b
              LEFT JOIN book_copies bc ON b.id = bc.book_id
              GROUP BY b.id, b.title, b.isbn, b.author, b.category, b.shelf_location, b.created_at
              ORDER BY b.created_at DESC';
    $result = $conn->query($query);

    if ($result) {
        $books = [];
        while ($row = $result->fetch_assoc()) {
            $books[] = $row;
        }

        echo json_encode([
            'success' => true,
            'books' => $books
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error fetching books: ' . $conn->error
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
