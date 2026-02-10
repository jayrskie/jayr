<?php
require_once 'connect.php';

// First, let's check what the current state is
echo "<h2>Current Users Table Structure:</h2>";

$result = $conn->query("SHOW CREATE TABLE users");
if ($result) {
    $row = $result->fetch_assoc();
    echo "<pre style='background: #f4f4f4; padding: 20px; border-radius: 5px; overflow-x: auto;'>";
    echo htmlspecialchars($row['Create Table']);
    echo "</pre>";
}

// Check current AUTO_INCREMENT
$result2 = $conn->query("SELECT AUTO_INCREMENT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'users' AND TABLE_SCHEMA = 'researchproject'");
if ($result2) {
    $row2 = $result2->fetch_assoc();
    echo "<h3>Current AUTO_INCREMENT value: " . $row2['AUTO_INCREMENT'] . "</h3>";
}

// Check if there are any existing users
$result3 = $conn->query("SELECT COUNT(*) as count FROM users");
$row3 = $result3->fetch_assoc();
echo "<h3>Number of existing users: " . $row3['count'] . "</h3>";

// Show existing user IDs
$result4 = $conn->query("SELECT id FROM users ORDER BY id");
if ($result4->num_rows > 0) {
    echo "<h3>Existing User IDs:</h3>";
    echo "<ul>";
    while ($user = $result4->fetch_assoc()) {
        echo "<li>User ID: " . $user['id'] . "</li>";
    }
    echo "</ul>";
}

$conn->close();
?>
