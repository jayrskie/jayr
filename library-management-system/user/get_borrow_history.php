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
    
    // Delete expired records (older than 7 days)
    $deleteStmt = $conn->prepare('DELETE FROM borrow_history WHERE expires_at < NOW()');
    $deleteStmt->execute();
    $deleteStmt->close();
    
    // Get history records (include whether a corresponding return exists)
    // Get history records
    if ($isAdmin) {
        // Admin sees all borrow history
        $query = 'SELECT bh.id, bh.user_id, bh.book_id, bh.book_title, bh.borrow_date, bh.expires_at, u.library_id, u.username '
                  . 'FROM borrow_history bh '
                  . 'JOIN users u ON bh.user_id = u.id '
                  . 'WHERE bh.expires_at >= NOW() '
                  . 'ORDER BY bh.borrow_date DESC';
        $stmt = $conn->prepare($query);
    } else {
        // User only sees their own history
        $query = 'SELECT id, user_id, book_id, book_title, borrow_date, expires_at '
                  . 'FROM borrow_history '
                  . 'WHERE user_id = ? AND expires_at >= NOW() '
                  . 'ORDER BY borrow_date DESC';
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
