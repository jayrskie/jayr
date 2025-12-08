<?php
header('Content-Type: application/json');

// Start session and include config
session_start();
require_once __DIR__ . '/../config.php';

// Check session authorization
if (!isset($_SESSION['library_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$id = isset($input['id']) ? intval($input['id']) : 0;
if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid id']);
    exit();
}

$stmt = $conn->prepare('DELETE FROM books WHERE id = ?');
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => $conn->error]);
    exit();
}
$stmt->bind_param('i', $id);
$ok = $stmt->execute();
if (!$ok) {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
    exit();
}
$stmt->close();

echo json_encode(['success' => true]);
exit();
?>
