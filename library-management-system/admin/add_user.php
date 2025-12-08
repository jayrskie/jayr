<?php
session_start();
header('Content-Type: application/json');

// Check session
if (!isset($_SESSION['library_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

require_once '../config.php';

try {
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || (!isset($input['name']) && !isset($input['username'])) || !isset($input['libraryId']) || !isset($input['role'])) {
        throw new Exception('Missing required fields');
    }

    // Accept either 'name' or 'username' from client
    $nameVal = isset($input['name']) ? $input['name'] : $input['username'];
    $libraryIdVal = $input['libraryId'];
    $roleVal = $input['role'];
    // optional password
    $passwordVal = isset($input['password']) ? $input['password'] : null;

    // Discover available columns in users table
    $cols = [];
    $schema = $conn->real_escape_string($dbname);
    $res = $conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '$schema' AND TABLE_NAME = 'users'");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $cols[] = $r['COLUMN_NAME'];
        }
        $res->free();
    }

    // Candidates for mapping
    $nameCandidates = ['name','full_name','username','user_name','email'];
    $libCandidates = ['library_id','libraryId','lib_id','libraryID','libraryid'];
    $roleCandidates = ['role','user_role','type','role_name'];

    $nameCol = null; $libCol = null; $roleCol = null;

    foreach ($nameCandidates as $c) { if (in_array($c, $cols)) { $nameCol = $c; break; } }
    foreach ($libCandidates as $c) { if (in_array($c, $cols)) { $libCol = $c; break; } }
    foreach ($roleCandidates as $c) { if (in_array($c, $cols)) { $roleCol = $c; break; } }
    
    // Detect password column
    $passwordCol = null;
    $passwordCandidates = ['password','passwd','pass'];
    foreach ($passwordCandidates as $c) { if (in_array($c, $cols)) { $passwordCol = $c; break; } }

    if (!$nameCol || !$libCol || !$roleCol) {
        throw new Exception('Required columns not found in users table. Found columns: ' . implode(',', $cols));
    }

    // Prepare dynamic insert (include password if provided)
    $columns = [$nameCol, $libCol, $roleCol];
    $placeholders = ['?','?','?'];
    $types = 'sss';
    $params = [$nameVal, $libraryIdVal, $roleVal];

    if (!empty($passwordVal) && $passwordCol) {
        // store a hashed password
        $columns[] = $passwordCol;
        $placeholders[] = '?';
        $types .= 's';
        $hashedPassword = password_hash($passwordVal, PASSWORD_DEFAULT);
        $params[] = $hashedPassword;
    }
    
    $sql = "INSERT INTO users (`" . implode('`,`', $columns) . "`) VALUES (" . implode(',', $placeholders) . ")";
    error_log("SQL: $sql");
    error_log("Params: " . print_r($params, true));
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    // bind params dynamically
    $bind_names = [];
    $bind_names[] = $types;
    // mysqli bind_param requires references
    for ($i = 0; $i < count($params); $i++) {
        $bind_names[] = &$params[$i];
    }

    if (!call_user_func_array([$stmt, 'bind_param'], $bind_names)) {
        throw new Exception('Bind failed: ' . $stmt->error);
    }

    if (!$stmt->execute()) {
        throw new Exception('Execute failed: ' . $stmt->error);
    }

    $stmt->close();
    echo json_encode([
        'success' => true, 
        'message' => 'User added successfully'
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>
