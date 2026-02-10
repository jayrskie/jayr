<?php
session_start();
require_once 'connect.php';

header('Content-Type: application/json');

try {
    $query = 'SELECT id, category_name FROM book_categories ORDER BY category_name ASC';
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'categories' => $categories
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>
