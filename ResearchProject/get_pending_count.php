<?php
// Start output buffering and error handlers to ensure JSON-only responses
ob_start();
header('Content-Type: application/json; charset=utf-8');
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (ob_get_length()) ob_end_clean();
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'A fatal error occurred']);
        exit;
    }
});

session_start();
require_once 'connect.php';

try {
    // Check if user is admin
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        throw new Exception('Unauthorized access');
    }

    // Get count of pending borrow requests
    $query = 'SELECT COUNT(*) as pending_count FROM borrow_requests WHERE status = "pending"';
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Database prepare error: ' . $conn->error);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $pending_count = $row['pending_count'] ?? 0;

    echo json_encode([
        'success' => true,
        'pending_count' => $pending_count
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
