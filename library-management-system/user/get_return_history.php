<?php
session_start();
header('Content-Type: application/json');

// Check session
if (!isset($_SESSION['library_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

require_once '../config.php';

try {
    // Determine if user or admin
    $isAdmin = $_SESSION['role'] === 'admin';
    
    // Get user_id from session
    $userId = $_SESSION['user_id'] ?? null;
    
    if (!$userId) {
        // Fallback: get user_id from library_id
        $stmt = $conn->prepare('SELECT id FROM users WHERE library_id = ?');
        $stmt->bind_param('s', $_SESSION['library_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $userId = $row['id'];
        }
        $stmt->close();
    }
    
    if (!$userId) {
        throw new Exception('User not found');
    }
    
    // Get return history records
    if ($isAdmin) {
        // Admin sees all return history
        $query = 'SELECT rh.id, rh.user_id, rh.book_id, rh.book_title, rh.borrow_date, rh.return_date, rh.days_borrowed, u.library_id, u.username 
                  FROM return_history rh
                  JOIN users u ON rh.user_id = u.id
                  ORDER BY rh.return_date DESC';
        $stmt = $conn->prepare($query);
    } else {
        // User only sees their own return history
        $query = 'SELECT id, user_id, book_id, book_title, borrow_date, return_date, days_borrowed 
                  FROM return_history 
                  WHERE user_id = ?
                  ORDER BY return_date DESC';
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $userId);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $records = [];
    
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'records' => $records
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>
