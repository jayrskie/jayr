<?php
// DEBUG MODE — always keep during development
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Start session first, then check authorization
session_start();
if (!isset($_SESSION['library_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../config.php';

// Verify database connection exists
if (!isset($conn) || $conn->connect_error) {
    echo json_encode([
        'success' => false,
        'error' => 'DB connection failed: ' . ($conn->connect_error ?? 'no conn')
    ]);
    exit();
}

// Try a prepared select for common columns, fall back to SELECT * if that fails
$books = [];
$error = null;

$stmt = $conn->prepare('SELECT id, title, author, isbn, quantity, category, available FROM books ORDER BY id DESC');
if ($stmt) {
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $books[] = $row;
        }
    } else {
        $error = 'Execute failed: ' . $stmt->error;
    }
    $stmt->close();
} else {
    // prepare failed, attempt generic query (maybe different column names)
    $result = $conn->query('SELECT * FROM books ORDER BY id DESC');
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $books[] = $row;
        }
    } else {
        $error = 'Query failed: ' . $conn->error;
    }
}

if ($error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $error]);
} else {
    echo json_encode(['success' => true, 'books' => $books]);
}

$conn->close();
exit();
?>
