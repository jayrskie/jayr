<?php
session_start();
header('Content-Type: application/json');

// Check session
if (!isset($_SESSION['library_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

require_once '../config.php';

try {
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['id'])) {
        throw new Exception('Missing user ID');
    }

    $userId = intval($input['id']);
    $password = isset($input['password']) ? $input['password'] : null;

    // Check if users table has password column
    $passwordCol = null;
    $schema = $conn->real_escape_string($dbname);
    $resCols = $conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '$schema' AND TABLE_NAME = 'users'");
    if ($resCols) {
        while ($r = $resCols->fetch_assoc()) {
            $col = $r['COLUMN_NAME'];
            $lower = strtolower($col);
            if (in_array($lower, ['password','passwd','pass'])) {
                $passwordCol = $col;
                break;
            }
        }
        $resCols->free();
    }

    // If password provided and column exists, update it
    if (!empty($password) && $passwordCol) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $updateStmt = $conn->prepare("UPDATE users SET `$passwordCol` = ? WHERE id = ?");
        if (!$updateStmt) {
            throw new Exception('Prepare failed: ' . $conn->error);
        }

        $updateStmt->bind_param('si', $hash, $userId);
        if (!$updateStmt->execute()) {
            throw new Exception('Execute failed: ' . $updateStmt->error);
        }
        $updateStmt->close();
    }

    echo json_encode(['success' => true, 'message' => 'User updated successfully']);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>
