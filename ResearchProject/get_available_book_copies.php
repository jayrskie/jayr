<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

session_start();
require_once 'connect.php';

header('Content-Type: application/json');

try {
    // First, verify tables exist and have data
    $debugInfo = [];
    
    // Check book_copies table
    $checkCopies = $conn->query("SELECT COUNT(*) as count FROM book_copies");
    $copiesCount = $checkCopies->fetch_assoc()['count'];
    $debugInfo['total_book_copies'] = $copiesCount;
    
    // Check available book_copies
    $checkAvailable = $conn->query("SELECT COUNT(*) as count FROM book_copies WHERE status = 'available'");
    $availableCount = $checkAvailable->fetch_assoc()['count'];
    $debugInfo['available_copies'] = $availableCount;
    
    // Check books table
    $checkBooks = $conn->query("SELECT COUNT(*) as count FROM books");
    $booksCount = $checkBooks->fetch_assoc()['count'];
    $debugInfo['total_books'] = $booksCount;
    
    // Get all available book copies with their book information
    $query = 'SELECT 
                bc.id as copy_id,
                bc.accession_code,
                bc.copy_number,
                bc.status,
                b.id as book_id,
                b.title,
                b.isbn,
                b.author,
                b.category,
                b.shelf_location
              FROM book_copies bc
              INNER JOIN books b ON bc.book_id = b.id
              WHERE bc.status = "available"
              ORDER BY b.title ASC, bc.copy_number ASC
              LIMIT 1000';
    
    $result = $conn->query($query);

    if (!$result) {
        throw new Exception('Database query error: ' . $conn->error);
    }

    $copies = [];
    while ($row = $result->fetch_assoc()) {
        $copies[] = $row;
    }

    echo json_encode([
        'success' => true,
        'copies' => $copies,
        'count' => count($copies),
        'debug' => $debugInfo
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error' => true
    ]);
}

$conn->close();
?>
