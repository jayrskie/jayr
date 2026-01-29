<?php
session_start();
require_once 'connect.php';

header('Content-Type: application/json');

try {
    // Check if user is admin
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        throw new Exception('Unauthorized access');
    }

    if (!isset($_GET['status'])) {
        throw new Exception('Status parameter is required');
    }

    $status = $_GET['status'];
    
    // Validate status
    if (!in_array($status, ['pending', 'approved', 'rejected'])) {
        throw new Exception('Invalid status');
    }

    $query = 'SELECT 
                br.id, 
                br.user_id, 
                br.guest_name,
                br.book_id, 
                br.status, 
                br.request_date, 
                br.approved_date,
                b.title as book_title, 
                b.author,
                COALESCE(u.name, br.guest_name) as user_name,
                COALESCE(u.email, "Guest") as user_email
              FROM borrow_requests br
              JOIN books b ON br.book_id = b.id
              LEFT JOIN users u ON br.user_id = u.id
              WHERE br.status = ?
              ORDER BY br.request_date DESC';

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Database prepare error: ' . $conn->error);
    }

    $stmt->bind_param('s', $status);
    $stmt->execute();
    $result = $stmt->get_result();

    $requests = [];
    while ($row = $result->fetch_assoc()) {
        // Add a flag to indicate if it's a guest request
        $row['is_guest'] = $row['user_id'] === null;
        $requests[] = $row;
    }

    echo json_encode([
        'success' => true,
        'requests' => $requests
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>
