<?php
/**
 * Migration script to remove is_verified column from users table
 * This removes the verification requirement from the system
 */

require_once 'connect.php';

try {
    // Check if the is_verified column exists
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'is_verified'");
    
    if ($result && $result->num_rows > 0) {
        // Column exists, drop it
        $sql = "ALTER TABLE users DROP COLUMN is_verified";
        
        if ($conn->query($sql) === TRUE) {
            echo "✓ Successfully removed is_verified column from users table<br>";
        } else {
            echo "✗ Error removing column: " . $conn->error . "<br>";
        }
    } else {
        echo "ℹ Column is_verified does not exist in users table (already removed)<br>";
    }
    
    // Verify the table structure
    $result = $conn->query("DESCRIBE users");
    echo "<h3>Updated Users Table Structure:</h3>";
    echo "<table border='1' cellpadding='10'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
<!doctype html>
<html>
<head>
    <title>Database Migration - Remove Verified Field</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        h3 {
            color: #333;
            margin-top: 2rem;
        }
        
        table {
            background: #f9fafb;
            margin-top: 1rem;
        }
        
        td {
            padding: 0.75rem;
        }
    </style>
</head>
<body>
    <h1>Database Migration: Remove Verification Requirement</h1>
    <p>This script removes the is_verified column from the users table, making verification no longer required for users to access borrow history.</p>
</body>
</html>
