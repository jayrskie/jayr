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

    $query = 'SELECT id as copy_id, copy_number, accession_code, status 
              FROM book_copies 
              WHERE book_id = ? AND status = "available"
              ORDER BY copy_number ASC';

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Database prepare error: ' . $conn->error);
    }

    $stmt->bind_param('i', $book_id);
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
