<?php
session_start();
require_once 'connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = isset($_POST['action']) ? $_POST['action'] : '';
        
        if ($action === 'add') {
            $category_name = isset($_POST['category_name']) ? trim($_POST['category_name']) : '';
            
            if (empty($category_name)) {
                throw new Exception('Category name is required');
            }
            
            if (strlen($category_name) > 100) {
                throw new Exception('Category name must be 100 characters or less');
            }
            
            // Check if category already exists
            $checkStmt = $conn->prepare('SELECT id FROM book_categories WHERE category_name = ?');
            $checkStmt->bind_param('s', $category_name);
            $checkStmt->execute();
            $checkStmt->store_result();
            
            if ($checkStmt->num_rows > 0) {
                throw new Exception('Category already exists');
            }
            $checkStmt->close();
            
            // Insert new category
            $insertStmt = $conn->prepare('INSERT INTO book_categories (category_name) VALUES (?)');
            $insertStmt->bind_param('s', $category_name);
            
            if (!$insertStmt->execute()) {
                throw new Exception('Failed to add category: ' . $insertStmt->error);
            }
            
            $insertStmt->close();
            
            echo json_encode([
                'success' => true,
                'message' => 'Category added successfully'
            ]);
        } else if ($action === 'delete') {
            $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
            
            if (!$category_id) {
                throw new Exception('Category ID is required');
            }
            
            // Delete category
            $deleteStmt = $conn->prepare('DELETE FROM book_categories WHERE id = ?');
            $deleteStmt->bind_param('i', $category_id);
            
            if (!$deleteStmt->execute()) {
                throw new Exception('Failed to delete category: ' . $deleteStmt->error);
            }
            
            $deleteStmt->close();
            
            echo json_encode([
                'success' => true,
                'message' => 'Category deleted successfully'
            ]);
        } else {
            throw new Exception('Invalid action');
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
}

$conn->close();
?>
