<?php
session_start();
require_once 'connect.php';

header('Content-Type: application/json');

try {
    // Check if user is admin
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        throw new Exception('Unauthorized access');
    }

    if (!isset($_GET['book_id'])) {
        throw new Exception('Book ID is required');
    }

    $book_id = intval($_GET['book_id']);
    $current_copy_id = isset($_GET['current_copy_id']) ? intval($_GET['current_copy_id']) : null;

    // Get available copies and the currently assigned copy (if any)
    if ($current_copy_id) {
        $query = 'SELECT id as copy_id, copy_number, accession_code, status 
                  FROM book_copies 
                  WHERE book_id = ? AND (status = "available" OR id = ?)
                  ORDER BY copy_number ASC';
    } else {
        $query = 'SELECT id as copy_id, copy_number, accession_code, status 
                  FROM book_copies 
                  WHERE book_id = ? AND status = "available"
                  ORDER BY copy_number ASC';
    }

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Database prepare error: ' . $conn->error);
    }

    if ($current_copy_id) {
        $stmt->bind_param('ii', $book_id, $current_copy_id);
    } else {
        $stmt->bind_param('i', $book_id);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $copies = [];
    while ($row = $result->fetch_assoc()) {
        $copies[] = $row;
    }

    $stmt->close();

    echo json_encode([
        'success' => true,
        'copies' => $copies
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
