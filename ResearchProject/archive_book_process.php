<?php
session_start();
require_once 'connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $book_id = isset($_POST['book_id']) ? intval($_POST['book_id']) : 0;

    if ($book_id <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid book ID'
        ]);
        exit();
    }

    // Check current archive status
    $check_status = $conn->prepare('SELECT archived FROM books WHERE id = ?');
    $check_status->bind_param('i', $book_id);
    $check_status->execute();
    $result = $check_status->get_result();
    $book = $result->fetch_assoc();

    if (!$book) {
        echo json_encode([
            'success' => false,
            'message' => 'Book not found'
        ]);
        exit();
    }

    // Toggle archive status (if not archived, archive it; if archived, restore it)
    $new_status = $book['archived'] ? 0 : 1;

    $update_status = $conn->prepare('UPDATE books SET archived = ? WHERE id = ?');
    $update_status->bind_param('ii', $new_status, $book_id);

    if ($update_status->execute()) {
        echo json_encode([
            'success' => true,
            'message' => $new_status ? 'Book archived successfully' : 'Book restored successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error updating book status: ' . $conn->error
        ]);
    }
    $update_status->close();
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}

$conn->close();
?>
