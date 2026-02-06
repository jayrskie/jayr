<?php
session_start();
require_once 'connect.php';

// Check if user is librarian/admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $copy_id = isset($_POST['copy_id']) ? intval($_POST['copy_id']) : 0;
    $new_status = isset($_POST['status']) ? trim($_POST['status']) : '';
    
    // Validate status
    $valid_statuses = ['available', 'damaged', 'lost', 'archived'];
    if (!in_array($new_status, $valid_statuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit();
    }
    
    if ($copy_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid copy ID']);
        exit();
    }
    
    // Get the book_id for this copy
    $get_book = $conn->prepare('SELECT book_id FROM book_copies WHERE id = ?');
    $get_book->bind_param('i', $copy_id);
    $get_book->execute();
    $result = $get_book->get_result();
    $copy = $result->fetch_assoc();
    $get_book->close();
    
    if (!$copy) {
        echo json_encode(['success' => false, 'message' => 'Copy not found']);
        exit();
    }
    
    $book_id = $copy['book_id'];
    
    // Update copy status
    $update_query = $conn->prepare('UPDATE book_copies SET status = ? WHERE id = ?');
    $update_query->bind_param('si', $new_status, $copy_id);
    
    if ($update_query->execute()) {
        // Update book quantity and available count
        $update_book = $conn->prepare('UPDATE books SET quantity = (SELECT COUNT(*) FROM book_copies WHERE book_id = ?), available = (SELECT COUNT(*) FROM book_copies WHERE book_id = ? AND status = "available") WHERE id = ?');
        $update_book->bind_param('iii', $book_id, $book_id, $book_id);
        
        if ($update_book->execute()) {
            echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error updating book counts: ' . $conn->error]);
        }
        $update_book->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating status: ' . $conn->error]);
    }
    
    $update_query->close();
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>
