<?php
session_start();
require_once 'connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = isset($_POST['action']) ? $_POST['action'] : '';
        
        if ($action === 'add') {
            $location_code = isset($_POST['location_code']) ? trim($_POST['location_code']) : '';
            $description = isset($_POST['description']) ? trim($_POST['description']) : '';
            
            if (empty($location_code)) {
                throw new Exception('Location code is required');
            }
            
            if (strlen($location_code) > 50) {
                throw new Exception('Location code must be 50 characters or less');
            }
            
            // Check if location already exists
            $checkStmt = $conn->prepare('SELECT id FROM shelf_locations WHERE location_code = ?');
            $checkStmt->bind_param('s', $location_code);
            $checkStmt->execute();
            $checkStmt->store_result();
            
            if ($checkStmt->num_rows > 0) {
                throw new Exception('Location code already exists');
            }
            $checkStmt->close();
            
            // Insert new shelf location
            $insertStmt = $conn->prepare('INSERT INTO shelf_locations (location_code, description) VALUES (?, ?)');
            $insertStmt->bind_param('ss', $location_code, $description);
            
            if (!$insertStmt->execute()) {
                throw new Exception('Failed to add shelf location: ' . $insertStmt->error);
            }
            
            $insertStmt->close();
            
            echo json_encode([
                'success' => true,
                'message' => 'Shelf location added successfully'
            ]);
        } else if ($action === 'delete') {
            $location_id = isset($_POST['location_id']) ? intval($_POST['location_id']) : 0;
            
            if (!$location_id) {
                throw new Exception('Location ID is required');
            }
            
            // Delete shelf location
            $deleteStmt = $conn->prepare('DELETE FROM shelf_locations WHERE id = ?');
            $deleteStmt->bind_param('i', $location_id);
            
            if (!$deleteStmt->execute()) {
                throw new Exception('Failed to delete shelf location: ' . $deleteStmt->error);
            }
            
            $deleteStmt->close();
            
            echo json_encode([
                'success' => true,
                'message' => 'Shelf location deleted successfully'
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
