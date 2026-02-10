<?php
session_start();
require_once 'connect.php';

// Handle AJAX request for book data
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    header('Content-Type: application/json');
    
    $book_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($book_id <= 0) {
        echo json_encode(['error' => 'Invalid book ID']);
        exit();
    }
    
    $query = $conn->prepare('SELECT b.id, b.title, b.isbn, b.author, COALESCE(bc_cat.category_name, b.category, "Uncategorized") as category, COALESCE(sl.location_code, b.shelf_location, "N/A") as shelf_location, 
                                     COUNT(bc.id) as quantity, 
                                     SUM(CASE WHEN bc.status = "available" THEN 1 ELSE 0 END) as available
                              FROM books b
                              LEFT JOIN book_copies bc ON b.id = bc.book_id
                              LEFT JOIN book_categories bc_cat ON b.category_id = bc_cat.id
                              LEFT JOIN shelf_locations sl ON b.shelf_location_id = sl.id
                              WHERE b.id = ?
                              GROUP BY b.id');
    $query->bind_param('i', $book_id);
    $query->execute();
    $result = $query->get_result();
    $book = $result->fetch_assoc();
    
    if (!$book) {
        echo json_encode(['error' => 'Book not found']);
        exit();
    }
    
    // Check if user already has this book borrowed
    $isBorrowed = false;
    if (isset($_SESSION['user_id'])) {
        $borrowCheck = $conn->prepare('SELECT br.id FROM borrow_requests br 
                                       LEFT JOIN borrowed_books bb ON br.id = bb.borrow_request_id 
                                       WHERE br.user_id = ? AND br.book_id = ? AND br.status = "approved" AND bb.return_date IS NULL');
        $borrowCheck->bind_param('ii', $_SESSION['user_id'], $book_id);
        $borrowCheck->execute();
        $borrowResult = $borrowCheck->get_result();
        $isBorrowed = $borrowResult->num_rows > 0;
        $borrowCheck->close();
    }
    
    $book['is_borrowed'] = $isBorrowed;
    
    echo json_encode(['success' => true, 'book' => $book]);
    $query->close();
    exit();
}

// Get book ID from URL (for direct page access)
$book_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($book_id <= 0) {
    $_SESSION['error'] = 'Invalid book ID';
    header('Location: index.php');
    exit();
}

// Fetch book details with copy information
$query = $conn->prepare('SELECT b.id, b.title, b.isbn, b.author, COALESCE(bc_cat.category_name, b.category, "Uncategorized") as category, COALESCE(sl.location_code, b.shelf_location, "N/A") as shelf_location, 
                                 COUNT(bc.id) as quantity, 
                                 SUM(CASE WHEN bc.status = "available" THEN 1 ELSE 0 END) as available
                          FROM books b
                          LEFT JOIN book_copies bc ON b.id = bc.book_id
                          LEFT JOIN book_categories bc_cat ON b.category_id = bc_cat.id
                          LEFT JOIN shelf_locations sl ON b.shelf_location_id = sl.id
                          WHERE b.id = ?
                          GROUP BY b.id');
$query->bind_param('i', $book_id);
$query->execute();
$result = $query->get_result();
$book = $result->fetch_assoc();

if (!$book) {
    $_SESSION['error'] = 'Book not found';
    header('Location: index.php');
    exit();
}

$query->close();
?>