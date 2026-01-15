<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['library_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once '../config.php';

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    // Fallback
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
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit;
}

// Get due dates: approved borrow_requests, calculate due date as created_at + 7 days
$query = 'SELECT book_title, created_at FROM borrow_requests WHERE user_id = ? AND status = ?';
$stmt = $conn->prepare($query);
$status = 'approved';
$stmt->bind_param('is', $userId, $status);
$stmt->execute();
$result = $stmt->get_result();
$dueDates = [];

while ($row = $result->fetch_assoc()) {
    $borrowDate = new DateTime($row['created_at']);
    $dueDate = $borrowDate->add(new DateInterval('P7D')); // Add 7 days
    $now = new DateTime();
    if ($dueDate > $now) {
        $interval = $now->diff($dueDate);
        $daysLeft = $interval->days;
        if ($daysLeft <= 2) {
            $dueDates[] = [
                'book_title' => $row['book_title'],
                'expires_at' => $dueDate->format('Y-m-d H:i:s')
            ];
        }
    }
}

$stmt->close();
$conn->close();

echo json_encode(['success' => true, 'due_dates' => $dueDates]);
?>