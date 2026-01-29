<?php
session_start();
require_once 'connect.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $library_id = isset($_POST['library_id']) ? trim($_POST['library_id']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    // Validate inputs
    if (empty($library_id)) {
        $_SESSION['error'] = 'Library ID is required';
        header('Location: login_page.php');
        exit();
    }

    // If no password provided, use library_id as default
    if (empty($password)) {
        $password = $library_id;
    }

    // Prepare statement to fetch user
    $stmt = $conn->prepare('SELECT id, library_id, name, email, password, role FROM users WHERE library_id = ?');
    $stmt->bind_param('s', $library_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Password is correct, set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            
            // Redirect based on role
            if ($user['role'] === 'user') {
                header('Location: index.php');
            } else {
                header('Location: admin.php');
            }
            exit();
        } else {
            $_SESSION['error'] = 'Invalid library ID or password';
            header('Location: login_page.php');
            exit();
        }
    } else {
        $_SESSION['error'] = 'Invalid library ID or password';
        header('Location: login_page.php');
        exit();
    }
    $stmt->close();
} else {
    // If not POST request, redirect to login page
    header('Location: login_page.php');
    exit();
}

$conn->close();
?>