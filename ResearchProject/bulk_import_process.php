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
    $data_rows = 0; // actual data rows processed (excludes header)
    $header_skipped = false;

    // Helper: generate a unique accession code (3-7 digits)
    // Helper: generate a unique accession code (exactly 4 digits, leading zeros allowed)
    function generate_unique_accession_code($conn, $digits = 4, $tries = 100) {
        $max_val = (int) pow(10, $digits) - 1;

        for ($i = 0; $i < $tries; $i++) {
            $num = mt_rand(0, $max_val);
            $code = str_pad((string) $num, $digits, '0', STR_PAD_LEFT);

            $check = $conn->prepare('SELECT id FROM book_copies WHERE accession_code = ?');
            if (!$check) {
                continue;
            }
            $check->bind_param('s', $code);
            $check->execute();
            $check->store_result();
            $exists = $check->num_rows > 0;
            $check->close();

            if (!$exists) {
                return $code;
            }
        }

        return false;
    }

    // Process each row
    while (($row = fgetcsv($handle)) !== false) {
        $row_num++;
        
        // Skip empty rows
        if (empty($row) || (count($row) === 1 && empty($row[0]))) {
            continue;
        }

        // Detect header row on the first non-empty line (if it contains known column names)
        if (!$header_skipped && $row_num === 1) {
            $lower = array_map('strtolower', $row);
            $header_keywords = ['title', 'isbn', 'author', 'category', 'accession', 'accession_code', 'shelf', 'shelf_location'];
            $found_header = false;
            foreach ($lower as $cell) {
                foreach ($header_keywords as $kw) {
                    if (strpos($cell, $kw) !== false) {
                        $found_header = true;
                        break 2;
                    }
                }
            }

            if ($found_header) {
                // treat this row as header and skip it
                $header_skipped = true;
                continue;
            }
        }

        // Validate row has required columns (title, isbn, author, category, accession_code, shelf_location)
        if (count($row) < 5) {
            $errors[] = "Row $row_num: Missing columns (need: title, isbn, author, category, accession_code[, shelf_location])";
            continue;
        }

        // This is a data row
        $data_rows++;

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
        // If accession code not provided, auto-generate one (exactly 4 digits)
        if (empty($accession_code)) {
            $generated = generate_unique_accession_code($conn);
            if ($generated === false) {
                $errors[] = "Row $row_num: Unable to generate a unique accession code";
                continue;
            }
            $accession_code = $generated;
        } else {
            // Validate accession code - only digits and exactly 4 digits long, no spaces
            if (!preg_match('/^\d{4}$/', $accession_code)) {
                $errors[] = "Row $row_num: Accession code must be exactly 4 digits (numbers only, no spaces) (got: $accession_code)";
                continue;
            }
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
            
            // Resolve category_id and shelf_location_id from text values
            $category_id = null;
            $shelf_location_id = null;
            
            if (!empty($category)) {
                $cat_stmt = $conn->prepare('SELECT id FROM book_categories WHERE category_name = ?');
                if ($cat_stmt) {
                    $cat_stmt->bind_param('s', $category);
                    $cat_stmt->execute();
                    $cat_res = $cat_stmt->get_result();
                    if ($cat_res && $cat_res->num_rows > 0) {
                        $cat_row = $cat_res->fetch_assoc();
                        $category_id = $cat_row['id'];
                    }
                    $cat_stmt->close();
                }
            }
            
            if (!empty($shelf_location)) {
                $shelf_stmt = $conn->prepare('SELECT id FROM shelf_locations WHERE location_code = ?');
                if ($shelf_stmt) {
                    $shelf_stmt->bind_param('s', $shelf_location);
                    $shelf_stmt->execute();
                    $shelf_res = $shelf_stmt->get_result();
                    if ($shelf_res && $shelf_res->num_rows > 0) {
                        $shelf_row = $shelf_res->fetch_assoc();
                        $shelf_location_id = $shelf_row['id'];
                    }
                    $shelf_stmt->close();
                }
            }
            
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
                // Create new book with category_id, shelf_location_id, and text values
                $insert_stmt = $conn->prepare('INSERT INTO books (title, isbn, author, category_id, shelf_location_id, category, shelf_location) VALUES (?, ?, ?, ?, ?, ?, ?)');
                if (!$insert_stmt) {
                    throw new Exception('Database prepare error: ' . $conn->error);
                }

                $insert_stmt->bind_param('sssiiss', $title, $isbn, $author, $category_id, $shelf_location_id, $category, $shelf_location);

                if (!$insert_stmt->execute()) {
                    throw new Exception('Error adding book: ' . $insert_stmt->error);
                }
                $book_id = $conn->insert_id;
                $insert_stmt->close();
            } else {
                // Book exists; update its category and shelf_location metadata
                $update_meta = $conn->prepare('UPDATE books SET category_id = ?, shelf_location_id = ?, category = ?, shelf_location = ? WHERE id = ?');
                if ($update_meta) {
                    $update_meta->bind_param('iissi', $category_id, $shelf_location_id, $category, $shelf_location, $book_id);
                    $update_meta->execute();
                    $update_meta->close();
                }
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
            // types: book_id (i), accession_code (s), copy_number (i), status (s)
            $copy_stmt->bind_param('isis', $book_id, $accession_code, $next_copy_number, $status);

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
        'total_rows' => $data_rows,
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
