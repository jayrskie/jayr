<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set the content type as JSON
header('Content-Type: application/json');

// Start the session and verify authorization
session_start();
if (!isset($_SESSION['library_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Include the database configuration
require_once __DIR__ . '/../config.php';

// Check the database connection
if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit();
}

// Expect JSON body or form-encoded
$input = json_decode(file_get_contents('php://input'), true);

// If input is empty (e.g., not JSON), fall back to POST
if (!$input) {
    $input = $_POST;
}

// Log the input data for debugging
file_put_contents('php://stderr', print_r($input, true));

// Validate the input data
$title = $input['title'] ?? '';
$author = $input['author'] ?? '';
$isbn = $input['isbn'] ?? '';
$quantity = isset($input['quantity']) ? intval($input['quantity']) : 0;
$category = $input['category'] ?? '';
$available = isset($input['available']) ? intval($input['available']) : $quantity;

// Check if the title is empty
if (trim($title) === '') {
    echo json_encode(['success' => false, 'error' => 'Title is required']);
    exit();
}

// Prepare SQL query to insert the data into the database
$stmt = $conn->prepare("INSERT INTO books (title, author, isbn, quantity, category, available) VALUES (?, ?, ?, ?, ?, ?)");

// Check for preparation errors
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Failed to prepare SQL statement: ' . $conn->error]);
    exit();
}

// Bind the parameters safely
$stmt->bind_param('sssisi', $title, $author, $isbn, $quantity, $category, $available);

// Execute the query
$ok = $stmt->execute();

// Check if execution was successful
if (!$ok) {
    echo json_encode(['success' => false, 'error' => 'Failed to execute SQL statement: ' . $stmt->error]);
    exit();
}

// Get the inserted ID
$insertId = $stmt->insert_id;
$stmt->close();

// Retrieve the newly inserted book to send back in the response
$res = $conn->query("SELECT * FROM books WHERE id = " . intval($insertId) . " LIMIT 1");
$book = $res ? $res->fetch_assoc() : null;

// Return success response with book data
echo json_encode(['success' => true, 'book' => $book]);

exit();
?>

