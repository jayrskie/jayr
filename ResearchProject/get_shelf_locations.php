<?php
session_start();
require_once 'connect.php';

header('Content-Type: application/json');

try {
    $query = 'SELECT id, location_code, description FROM shelf_locations ORDER BY location_code ASC';
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $locations = [];
    while ($row = $result->fetch_assoc()) {
        $locations[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'locations' => $locations
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
