<?php
session_start();
require_once 'connect.php';

header('Content-Type: application/json');

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not authenticated');
    }

    $user_id = intval($_SESSION['user_id']);

    $query = 'SELECT 
                br.id as request_id,
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
                b.author
                                , bc.accession_code as book_copy
              FROM borrow_requests br
              JOIN books b ON br.book_id = b.id
                            LEFT JOIN borrowed_books bb ON br.id = bb.borrow_request_id
                            LEFT JOIN book_copies bc ON bb.book_copy_id = bc.id
              WHERE br.user_id = ? AND br.status IN ("approved", "rejected")
              ORDER BY COALESCE(bb.return_date, bb.borrow_date, br.request_date) DESC';

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Database prepare error: ' . $conn->error);
    }

    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $history = [];
    while ($row = $result->fetch_assoc()) {
        // Set return_status to pending if not returned
        if ($row['request_status'] === 'approved' && $row['return_date'] === null) {
            $row['return_status'] = 'pending';
        }
        
        $history[] = $row;
    }

    echo json_encode([
        'success' => true,
        'history' => $history
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
