<?php
// Ensure any PHP notices/warnings/fatals are returned as JSON
// Start output buffering and set error handlers BEFORE including other files
ob_start();
header('Content-Type: application/json; charset=utf-8');

// Convert PHP errors/warnings/notices into ErrorException so they are catchable
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Shutdown handler to catch fatal errors and return JSON
register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        // Clean any buffered output that might contain HTML
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
                COALESCE(u.email, br.guest_name, "Guest") as user_email
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

    // Clean any accidental buffered output before sending JSON
    if (ob_get_length()) ob_end_clean();
    echo json_encode([
        'success' => true,
        'requests' => $requests
    ]);

} catch (Exception $e) {
    if (ob_get_length()) ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    // Restore previous error handler
    restore_error_handler();
    if (isset($stmt) && $stmt) $stmt->close();
    $conn->close();
}
?>
