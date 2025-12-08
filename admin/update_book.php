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
$title = $input['title'] ?? '';
$author = $input['author'] ?? '';
$isbn = $input['isbn'] ?? '';
$quantity = isset($input['quantity']) ? intval($input['quantity']) : 0;
$category = $input['category'] ?? '';
$available = isset($input['available']) ? intval($input['available']) : $quantity;

if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid id']);
    exit();
}

$stmt = $conn->prepare("UPDATE books SET title = ?, author = ?, isbn = ?, quantity = ?, category = ?, available = ? WHERE id = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => $conn->error]);
    exit();
}
$stmt->bind_param('sssisii', $title, $author, $isbn, $quantity, $category, $available, $id);
$ok = $stmt->execute();
if (!$ok) {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
    exit();
}
$stmt->close();

$res = $conn->query("SELECT * FROM books WHERE id = " . intval($id) . " LIMIT 1");
$book = $res ? $res->fetch_assoc() : null;

echo json_encode(['success' => true, 'book' => $book]);
exit();
?>
