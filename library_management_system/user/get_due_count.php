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

// Count approved requests with due date within 3 days
$query = 'SELECT COUNT(*) as count FROM borrow_requests WHERE user_id = ? AND status = ?';
$stmt = $conn->prepare($query);
$status = 'approved';
$stmt->bind_param('is', $userId, $status);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$totalApproved = $row['count'] ?? 0;

$stmt->close();

// Now calculate how many have due within 3 days
$count = 0;
if ($totalApproved > 0) {
    $query2 = 'SELECT created_at FROM borrow_requests WHERE user_id = ? AND status = ?';
    $stmt2 = $conn->prepare($query2);
    $stmt2->bind_param('is', $userId, $status);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    while ($row2 = $result2->fetch_assoc()) {
        $borrowDate = new DateTime($row2['created_at']);
        $dueDate = $borrowDate->add(new DateInterval('P7D'));
        $now = new DateTime();
        $future3Days = $now->add(new DateInterval('P3D'));
        if ($dueDate <= $future3Days && $dueDate > $now) {
            $count++;
        }
    }
    $stmt2->close();
}

$conn->close();

echo json_encode(['success' => true, 'count' => $count]);
?>