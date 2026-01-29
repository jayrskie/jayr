<?php
session_start();
require_once 'connect.php';

header('Content-Type: application/json');

try {
    // Check if user is admin
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        throw new Exception('Unauthorized access');
    }

    $query = 'SELECT 
                br.id as request_id,
                br.user_id, 
                br.guest_name,
                br.book_id, 
                br.status as request_status,
                br.request_date,
                br.approved_date,
                br.request_type,
                bb.id as borrowed_id,
                bb.borrow_date, 
                bb.due_date,
                bb.return_date,
                bb.borrow_type,
                bb.borrow_duration,
                bb.borrow_schedule,
                bb.return_status,
                bb.overdue_hours,
                b.title as book_title, 
                b.author,
                COALESCE(u.name, br.guest_name) as user_name,
                COALESCE(u.email, "Guest") as user_email
              FROM borrow_requests br
              JOIN books b ON br.book_id = b.id
              LEFT JOIN users u ON br.user_id = u.id
              LEFT JOIN borrowed_books bb ON br.id = bb.borrow_request_id
              WHERE br.status IN ("approved", "rejected")
              ORDER BY COALESCE(bb.return_date, bb.borrow_date, br.request_date) DESC';

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Database prepare error: ' . $conn->error);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $requests = [];
    while ($row = $result->fetch_assoc()) {
        // Add a flag to indicate if it's a guest borrow
        $row['is_guest'] = $row['user_id'] === null;
        
        // Set return_status to pending if not returned
        if ($row['request_status'] === 'approved' && $row['return_date'] === null) {
            $row['return_status'] = 'pending';
        }
        
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
