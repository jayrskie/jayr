<?php
require_once 'connect.php';

// Default categories
$defaultCategories = [
    'Fiction',
    'Non-Fiction',
    'Science',
    'History',
    'Biography',
    'Mystery',
    'Romance',
    'Children\'s',
    'Reference',
    'Technology'
];

// Default shelf locations
$defaultLocations = [
    ['FIC-A1', 'Fiction Section - Shelf A1'],
    ['FIC-A2', 'Fiction Section - Shelf A2'],
    ['NF-B1', 'Non-Fiction Section - Shelf B1'],
    ['NF-B2', 'Non-Fiction Section - Shelf B2'],
    ['SCI-C1', 'Science Section - Shelf C1'],
    ['REF-D1', 'Reference Section - Shelf D1']
];

$conn->begin_transaction();

try {
    // Insert default categories if they don't exist
    foreach ($defaultCategories as $category) {
        $checkStmt = $conn->prepare('SELECT id FROM book_categories WHERE category_name = ?');
        $checkStmt->bind_param('s', $category);
        $checkStmt->execute();
        $checkStmt->store_result();
        
        if ($checkStmt->num_rows === 0) {
            $insertStmt = $conn->prepare('INSERT INTO book_categories (category_name) VALUES (?)');
            $insertStmt->bind_param('s', $category);
            $insertStmt->execute();
            $insertStmt->close();
        }
        $checkStmt->close();
    }

    // Insert default shelf locations if they don't exist
    foreach ($defaultLocations as $location) {
        $code = $location[0];
        $desc = $location[1];
        
        $checkStmt = $conn->prepare('SELECT id FROM shelf_locations WHERE location_code = ?');
        $checkStmt->bind_param('s', $code);
        $checkStmt->execute();
        $checkStmt->store_result();
        
        if ($checkStmt->num_rows === 0) {
            $insertStmt = $conn->prepare('INSERT INTO shelf_locations (location_code, description) VALUES (?, ?)');
            $insertStmt->bind_param('ss', $code, $desc);
            $insertStmt->execute();
            $insertStmt->close();
        }
        $checkStmt->close();
    }

    $conn->commit();
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Default categories and shelf locations initialized successfully'
    ]);

} catch (Exception $e) {
    $conn->rollback();
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
