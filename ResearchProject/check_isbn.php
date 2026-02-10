<?php
header('Content-Type: application/json');
require_once 'connect.php';

try {
    $q = isset($_GET['q']) ? trim($_GET['q']) : '';
    if ($q === '') {
        echo json_encode(['success' => false, 'message' => 'No ISBN provided']);
        exit();
    }

    // Validate ISBN format (13 digits, dashes allowed, no consecutive dashes)
    if (!preg_match('/^(?!.*--)(?:[0-9]-?){12}[0-9]$/', $q)) {
        echo json_encode(['success' => false, 'message' => 'Invalid ISBN format']);
        exit();
    }

    // Normalize by removing dashes for comparison
    $normalized = str_replace('-', '', $q);

    $stmt = $conn->prepare('SELECT id, title, author FROM books WHERE REPLACE(isbn, "-", "") = ? LIMIT 1');
    if (!$stmt) {
        throw new Exception('DB prepare error: ' . $conn->error);
    }

    $stmt->bind_param('s', $normalized);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo json_encode(['success' => true, 'exists' => true, 'book' => ['id' => (int)$row['id'], 'title' => $row['title'], 'author' => $row['author']]]);
    } else {
        echo json_encode(['success' => true, 'exists' => false]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

?>
