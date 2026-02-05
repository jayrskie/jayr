<?php
// Start session to use $_SESSION variables
session_start();

// Include database connection
require_once 'connect.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $library_id = isset($_POST['library_id']) ? trim($_POST['library_id']) : '';
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    // Validate inputs
    if (empty($library_id) || empty($name)) {
        $_SESSION['error'] = 'Library ID and Full Name are required';
        header('Location: register_page.php');
        exit();
    }

    // Validate library_id length (maximum 15 characters)
    if (strlen($library_id) > 15) {
        $_SESSION['error'] = 'Library ID cannot exceed 15 characters';
        header('Location: register_page.php');
        exit();
    }

    // Validate library_id contains only alphanumeric and dash
    if (!preg_match('/^[a-zA-Z0-9\-]+$/', $library_id)) {
        $_SESSION['error'] = 'Library ID can only contain letters, numbers, and dash (-)';
        header('Location: register_page.php');
        exit();
    }

    // Validate library_id format (AU- followed by 12 numbers)
    if (!preg_match('/^AU-\d{12}$/', $library_id)) {
        $_SESSION['error'] = 'Library ID must be in format AU-XXXXXXXXXXXX (12 numbers after AU-)';
        header('Location: register_page.php');
        exit();
    }

    // If password is provided, use it; otherwise generate a default one
    if (empty($password)) {
        $password = $library_id; // Use library_id as default password
    }

    // If email is provided, validate it; but it's optional
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = 'Invalid email format';
        header('Location: register_page.php');
        exit();
    }

    // Check if library_id already exists
    $check_library_id = $conn->prepare('SELECT id FROM users WHERE library_id = ?');
    $check_library_id->bind_param('s', $library_id);
    $check_library_id->execute();
    $check_library_id->store_result();

    if ($check_library_id->num_rows > 0) {
        $_SESSION['error'] = 'Library ID already taken';
        header('Location: register_page.php');
        exit();
    }
    $check_library_id->close();

    // Check if email already exists (only if email is provided)
    if (!empty($email)) {
        $check_email = $conn->prepare('SELECT id FROM users WHERE email = ?');
        $check_email->bind_param('s', $email);
        $check_email->execute();
        $check_email->store_result();

        if ($check_email->num_rows > 0) {
            $_SESSION['error'] = 'Email already registered';
            header('Location: register_page.php');
            exit();
        }
        $check_email->close();
    }

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Set default role to 'user'
    $role = 'user';

    // Insert user into database with default role
    $insert_user = $conn->prepare('INSERT INTO users (library_id, name, email, password, role) VALUES (?, ?, ?, ?, ?)');
    $insert_user->bind_param('sssss', $library_id, $name, $email, $hashed_password, $role);

    if ($insert_user->execute()) {
        // Get the inserted user ID
        $user_id = $insert_user->insert_id;
        
        // Automatically log in the user by setting session variables
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role'] = $role;
        
        $_SESSION['success'] = 'Account created successfully! You are now logged in.';
        header('Location: index.php');
        exit();
    } else {
        $_SESSION['error'] = 'Error creating account: ' . $conn->error;
        header('Location: register_page.php');
        exit();
    }
    $insert_user->close();
} else {
    // If not POST request, redirect to register page
    header('Location: register_page.php');
    exit();
}

$conn->close();
?>