<?php
session_start();

// Track logout time if user is logged in
if (isset($_SESSION['user_id'])) {
    require_once 'connect.php';
    
    $updateLogoutStmt = $conn->prepare('UPDATE users SET last_logout = NOW() WHERE id = ?');
    $updateLogoutStmt->bind_param('i', $_SESSION['user_id']);
    $updateLogoutStmt->execute();
    $updateLogoutStmt->close();
    $conn->close();
}

// Destroy the session
session_destroy();

// Redirect to home page
header('Location: index.php');
exit();
?>