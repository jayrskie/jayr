<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['library_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

$bookId = $data['book_id'] ?? null;
$bookTitle = $data['book_title'] ?? null;

if (!$bookId || !$bookTitle) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing book_id or book_title']);
    exit;
}

// Validate that book exists
$checkStmt = $conn->prepare('SELECT id FROM books WHERE id = ?');
$checkStmt->bind_param('i', $bookId);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows === 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Book does not exist']);
    $checkStmt->close();
    exit;
}
$checkStmt->close();

// Insert borrow request (store user_id referencing users.id)
$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    // Fallback: try to lookup by library_id
    $lib = $_SESSION['library_id'] ?? null;
    if ($lib) {
        $uStmt = $conn->prepare('SELECT id FROM users WHERE library_id = ? LIMIT 1');
        $uStmt->bind_param('s', $lib);
        $uStmt->execute();
        $uRes = $uStmt->get_result();
        if ($uRes && $uRes->num_rows > 0) {
            $uRow = $uRes->fetch_assoc();
            $userId = $uRow['id'];
        }
        $uStmt->close();
    }
}

if (!$userId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit;
}

$stmt = $conn->prepare('INSERT INTO borrow_requests (user_id, book_id, book_title, status) VALUES (?, ?, ?, ?)');
$status = 'requested';
$stmt->bind_param('iiss', $userId, $bookId, $bookTitle, $status);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to create borrow request']);
    $stmt->close();
    exit;
}

$requestId = $stmt->insert_id;
$stmt->close();

$fetchStmt = $conn->prepare('SELECT id, user_id, book_id, book_title, status, created_at, updated_at FROM borrow_requests WHERE id = ?');
$fetchStmt->bind_param('i', $requestId);
$fetchStmt->execute();
$result = $fetchStmt->get_result();
$request = $result->fetch_assoc();
$fetchStmt->close();

echo json_encode(['success' => true, 'request' => $request]);
