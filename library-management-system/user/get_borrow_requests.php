<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config.php';

// Optional search filter via query param
$searchQuery = $_GET['q'] ?? '';
$requests = [];

if (empty($searchQuery)) {
    // Get all requests (newest first) and include user info
    $stmt = $conn->prepare("SELECT br.id, br.user_id, u.library_id, u.username, br.book_id, br.book_title, br.status, br.created_at, br.updated_at
        FROM borrow_requests br
        LEFT JOIN users u ON br.user_id = u.id
        ORDER BY br.created_at DESC LIMIT 100");
    $stmt->execute();
} else {
    // Search by library_id, username, book_title, or book_id
    $searchTerm = '%' . $searchQuery . '%';
    $stmt = $conn->prepare("SELECT br.id, br.user_id, u.library_id, u.username, br.book_id, br.book_title, br.status, br.created_at, br.updated_at
        FROM borrow_requests br
        LEFT JOIN users u ON br.user_id = u.id
        WHERE u.library_id LIKE ? OR u.username LIKE ? OR br.book_title LIKE ? OR br.book_id LIKE ?
        ORDER BY br.created_at DESC LIMIT 100");
    $stmt->bind_param('ssss', $searchTerm, $searchTerm, $searchTerm, $searchTerm);
    $stmt->execute();
}

$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $requests[] = $row;
}
$stmt->close();

echo json_encode(['success' => true, 'requests' => $requests]);
