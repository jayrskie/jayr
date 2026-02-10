<?php
/**
 * Migration script to remove the Guest feature from the database
 * This script removes the guest_name column from borrow_requests and borrowed_books tables
 * 
 * Run this script once to complete the removal of the Guest feature
 */

require_once 'connect.php';

try {
    echo "Starting Guest feature removal...<br><br>";
    
    // Drop guest_name column from borrowed_books table
    echo "Removing guest_name column from borrowed_books table...<br>";
    $sql1 = "ALTER TABLE borrowed_books DROP COLUMN IF EXISTS guest_name";
    if ($conn->query($sql1)) {
        echo "✓ Successfully removed guest_name from borrowed_books table<br>";
    } else {
        echo "Error removing guest_name from borrowed_books: " . $conn->error . "<br>";
    }
    
    echo "<br>";
    
    // Drop guest_name column from borrow_requests table
    echo "Removing guest_name column from borrow_requests table...<br>";
    $sql2 = "ALTER TABLE borrow_requests DROP COLUMN IF EXISTS guest_name";
    if ($conn->query($sql2)) {
        echo "✓ Successfully removed guest_name from borrow_requests table<br>";
    } else {
        echo "Error removing guest_name from borrow_requests: " . $conn->error . "<br>";
    }
    
    echo "<br><br>";
    echo "✓ Guest feature removal completed successfully!<br>";
    echo "<p style='color: green; font-weight: bold;'>All guest-related data has been removed from the database.</p>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

$conn->close();
?>
