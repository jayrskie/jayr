<?php
session_start();
require_once 'connect.php';

header('Content-Type: application/json');

try {
    // Check if user is admin
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        throw new Exception('Unauthorized access');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Check if file was uploaded
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No file uploaded or upload error');
    }

    $file = $_FILES['csv_file'];
    
    // Validate file type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $allowed_mimes = ['text/plain', 'text/csv', 'application/vnd.ms-excel'];
    if (!in_array($mime_type, $allowed_mimes) && !preg_match('/\.csv$/i', $file['name'])) {
        throw new Exception('Invalid file type. Please upload a CSV file.');
    }

    // Open file
    $handle = fopen($file['tmp_name'], 'r');
    if (!$handle) {
        throw new Exception('Unable to open file');
    }

    $imported = 0;
    $errors = [];
    $row_num = 0;

    // Process each row
    while (($row = fgetcsv($handle)) !== false) {
        $row_num++;
        
        // Skip empty rows
        if (empty($row) || (count($row) === 1 && empty($row[0]))) {
            continue;
        }

        // Validate row has required columns (title, isbn, author, category, accession_code, shelf_location)
        if (count($row) < 5) {
            $errors[] = "Row $row_num: Missing columns (need: title, isbn, author, category, accession_code[, shelf_location])";
            continue;
        }

        // Extract and sanitize data
        $title = trim($row[0]);
        $isbn = trim($row[1]);
        $author = trim($row[2]);
        $category = trim($row[3]);
        $accession_code = trim($row[4]);
        $shelf_location = isset($row[5]) ? trim($row[5]) : '';

        // Validate required fields
        if (empty($title)) {
            $errors[] = "Row $row_num: Title is required";
            continue;
        }
        if (empty($author)) {
            $errors[] = "Row $row_num: Author is required";
            continue;
        }
        if (empty($category)) {
            $errors[] = "Row $row_num: Category is required";
            continue;
        }
        if (empty($accession_code)) {
            $errors[] = "Row $row_num: Accession code is required";
            continue;
        }

        // Validate accession code - only digits and exactly 7
        if (!preg_match('/^\d{7}$/', $accession_code)) {
            $errors[] = "Row $row_num: Accession code must be exactly 7 digits (got: $accession_code)";
            continue;
        }

        // Check if accession code already exists in book_copies
        $check_stmt = $conn->prepare('SELECT id FROM book_copies WHERE accession_code = ?');
        if (!$check_stmt) {
            throw new Exception('Database prepare error: ' . $conn->error);
        }
        $check_stmt->bind_param('s', $accession_code);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            $errors[] = "Row $row_num: Accession code $accession_code already exists";
            $check_stmt->close();
            continue;
        }
        $check_stmt->close();

        // Start transaction for this book insert
        $conn->begin_transaction();

        try {
            $book_id = null;
            
            // Check if book already exists by ISBN (if provided)
            if (!empty($isbn)) {
                $check_isbn = $conn->prepare('SELECT id FROM books WHERE isbn = ?');
                $check_isbn->bind_param('s', $isbn);
                $check_isbn->execute();
                $isbn_result = $check_isbn->get_result();
                
                if ($isbn_result->num_rows > 0) {
                    // Book exists by ISBN, get its ID
                    $existing_book = $isbn_result->fetch_assoc();
                    $book_id = $existing_book['id'];
                }
                $check_isbn->close();
            }
            
            // If still not found, create new book
            if ($book_id === null) {
                // Create new book
                $insert_stmt = $conn->prepare('INSERT INTO books (title, isbn, author, category, shelf_location) VALUES (?, ?, ?, ?, ?)');
                if (!$insert_stmt) {
                    throw new Exception('Database prepare error: ' . $conn->error);
                }

                $insert_stmt->bind_param('sssss', $title, $isbn, $author, $category, $shelf_location);

                if (!$insert_stmt->execute()) {
                    throw new Exception('Error adding book: ' . $insert_stmt->error);
                }
                $book_id = $conn->insert_id;
                $insert_stmt->close();
            }

            // Get the next copy number for this book
            $get_max_copy = $conn->prepare('SELECT MAX(copy_number) as max_copy FROM book_copies WHERE book_id = ?');
            $get_max_copy->bind_param('i', $book_id);
            $get_max_copy->execute();
            $copy_result = $get_max_copy->get_result();
            $copy_row = $copy_result->fetch_assoc();
            $next_copy_number = ($copy_row['max_copy'] ?? 0) + 1;
            $get_max_copy->close();

            // Insert book copy
            $copy_stmt = $conn->prepare('INSERT INTO book_copies (book_id, accession_code, copy_number, status) VALUES (?, ?, ?, ?)');

            if (!$copy_stmt) {
                throw new Exception('Database prepare error: ' . $conn->error);
            }
            
            $status = 'available';
            $copy_stmt->bind_param('issi', $book_id, $accession_code, $next_copy_number, $status);

            if (!$copy_stmt->execute()) {
                throw new Exception('Error adding book copy: ' . $copy_stmt->error);
            }
            $copy_stmt->close();

            // Update book quantity and available count
            $update_book = $conn->prepare('UPDATE books SET quantity = (SELECT COUNT(*) FROM book_copies WHERE book_id = ?), available = (SELECT COUNT(*) FROM book_copies WHERE book_id = ? AND status = "available") WHERE id = ?');
            if (!$update_book) {
                throw new Exception('Database prepare error: ' . $conn->error);
            }
            
            $update_book->bind_param('iii', $book_id, $book_id, $book_id);
            
            if (!$update_book->execute()) {
                throw new Exception('Error updating book counts: ' . $update_book->error);
            }
            $update_book->close();

            $conn->commit();
            $imported++;

        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Row $row_num: " . $e->getMessage();
        }
    }

    fclose($handle);

    // Prepare response
    $response = [
        'success' => true,
        'imported' => $imported,
        'total_rows' => $row_num - 1, // Subtract 1 for header row
        'errors' => $errors
    ];

    if ($imported > 0) {
        $response['message'] = "Successfully imported $imported book" . ($imported !== 1 ? 's' : '');
    } else {
        $response['message'] = 'No books were imported. Please check errors below.';
    }

    if (!empty($errors)) {
        $response['message'] .= ' (' . count($errors) . ' error' . (count($errors) !== 1 ? 's' : '') . ')';
    }

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'imported' => 0,
        'errors' => []
    ]);
}

$conn->close();
?>
