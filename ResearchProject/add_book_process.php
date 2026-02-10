<?php
session_start();
require_once 'connect.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $isbn = isset($_POST['isbn']) ? trim($_POST['isbn']) : '';
    $author = isset($_POST['author']) ? trim($_POST['author']) : '';
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    $shelf_location_id = isset($_POST['shelf_location_id']) ? intval($_POST['shelf_location_id']) : null;
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;
    $accession_codes = isset($_POST['accession_codes']) ? explode("\n", trim($_POST['accession_codes'])) : [];
    $auto_generate = isset($_POST['auto_generate']) && $_POST['auto_generate'] ? true : false;

    // Validate required fields
    if (empty($title) || empty($author) || $category_id <= 0 || $quantity <= 0) {
        $_SESSION['error'] = 'Title, Author, Category, and Quantity are required';
        header('Location: catalog_page.php');
        exit();
    }

    // ISBN is required and must be ISBN-13 (13 digits total). Dashes allowed but not consecutive.
    if (empty($isbn)) {
        $_SESSION['error'] = 'ISBN is required';
        header('Location: catalog_page.php');
        exit();
    }

    if (!preg_match('/^(?!.*--)(?:[0-9]-?){12}[0-9]$/', $isbn)) {
        $_SESSION['error'] = 'ISBN must contain exactly 13 digits (dashes allowed, but not consecutive). Example: 978-0-553-38016-3';
        header('Location: catalog_page.php');
        exit();
    }

    // Normalize manual codes and/or generate if requested
    $accession_codes = array_filter(array_map('trim', $accession_codes));

    if ($auto_generate) {
        // Generate $quantity unique 4-digit accession codes
        $generated = [];
        $check_accession = $conn->prepare('SELECT id FROM book_copies WHERE accession_code = ?');
        if (!$check_accession) {
            $_SESSION['error'] = 'Database error preparing accession check: ' . $conn->error;
            header('Location: catalog_page.php');
            exit();
        }

        while (count($generated) < $quantity) {
            $code = str_pad((string)mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
            if (in_array($code, $generated, true)) continue;

            $check_accession->bind_param('s', $code);
            $check_accession->execute();
            $check_accession->store_result();
            if ($check_accession->num_rows === 0) {
                $generated[] = $code;
            }
            $check_accession->free_result();
        }
        $check_accession->close();
        $accession_codes = $generated;
    } else {
        // Manual: ensure count matches and validate codes
        if (count($accession_codes) !== $quantity) {
            $_SESSION['error'] = 'Number of accession codes must match quantity';
            header('Location: catalog_page.php');
            exit();
        }

        // Validate all accession codes and uniqueness
        $check_accession = $conn->prepare('SELECT id FROM book_copies WHERE accession_code = ?');
        if (!$check_accession) {
            $_SESSION['error'] = 'Database error preparing accession check: ' . $conn->error;
            header('Location: catalog_page.php');
            exit();
        }

        foreach ($accession_codes as $code) {
            if (!preg_match('/^\d{4}$/', $code)) {
                $_SESSION['error'] = "Accession Code '$code' must be exactly 4 digits";
                header('Location: catalog_page.php');
                exit();
            }

            // Check if accession code already exists in book_copies
            $check_accession->bind_param('s', $code);
            $check_accession->execute();
            $check_accession->store_result();

            if ($check_accession->num_rows > 0) {
                $_SESSION['error'] = "Accession Code '$code' already exists";
                $check_accession->close();
                header('Location: catalog_page.php');
                exit();
            }
            $check_accession->free_result();
        }
        $check_accession->close();
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Resolve category and shelf_location text values from their IDs
        $category_name = '';
        $shelf_code = '';
        $shelf_location_id_db = null;

        if ($category_id > 0) {
            $cat_stmt = $conn->prepare('SELECT category_name FROM book_categories WHERE id = ?');
            if ($cat_stmt) {
                $cat_stmt->bind_param('i', $category_id);
                $cat_stmt->execute();
                $cat_res = $cat_stmt->get_result();
                if ($cat_res && $cat_res->num_rows > 0) {
                    $cat_row = $cat_res->fetch_assoc();
                    $category_name = $cat_row['category_name'];
                }
                $cat_stmt->close();
            }
        }

        if ($shelf_location_id && $shelf_location_id > 0) {
            $shelf_stmt = $conn->prepare('SELECT location_code FROM shelf_locations WHERE id = ?');
            if ($shelf_stmt) {
                $shelf_stmt->bind_param('i', $shelf_location_id);
                $shelf_stmt->execute();
                $shelf_res = $shelf_stmt->get_result();
                if ($shelf_res && $shelf_res->num_rows > 0) {
                    $shelf_row = $shelf_res->fetch_assoc();
                    $shelf_code = $shelf_row['location_code'];
                    $shelf_location_id_db = $shelf_location_id;
                }
                $shelf_stmt->close();
            }
        }

        // Check if book already exists by ISBN
        $check_book = $conn->prepare('SELECT id FROM books WHERE isbn = ?');
        $check_book->bind_param('s', $isbn);
        $check_book->execute();
        $result = $check_book->get_result();
        
        if ($result->num_rows > 0) {
            // Book already exists, use existing book ID
            $row = $result->fetch_assoc();
            $book_id = $row['id'];
            $check_book->close();
            
            // Get the current highest copy number for this book to continue numbering
            $get_max_copy = $conn->prepare('SELECT MAX(copy_number) as max_copy FROM book_copies WHERE book_id = ?');
            $get_max_copy->bind_param('i', $book_id);
            $get_max_copy->execute();
            $max_result = $get_max_copy->get_result();
            $max_row = $max_result->fetch_assoc();
            $next_copy_number = ($max_row['max_copy'] ?? 0) + 1;
            $get_max_copy->close();
            // Update book's category and shelf text/ids to reflect current selection
            $update_meta = $conn->prepare('UPDATE books SET category_id = ?, shelf_location_id = ?, category = ?, shelf_location = ? WHERE id = ?');
            if ($update_meta) {
                $update_meta->bind_param('iissi', $category_id, $shelf_location_id_db, $category_name, $shelf_code, $book_id);
                $update_meta->execute();
                $update_meta->close();
            }
        } else {
            // Book doesn't exist, create new one
            $check_book->close();
            
            $insert_book = $conn->prepare('INSERT INTO books (title, isbn, author, category_id, shelf_location_id, category, shelf_location) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $insert_book->bind_param('sssiiss', $title, $isbn, $author, $category_id, $shelf_location_id_db, $category_name, $shelf_code);

            if (!$insert_book->execute()) {
                throw new Exception('Error adding book: ' . $conn->error);
            }

            $book_id = $conn->insert_id;
            $insert_book->close();
            $next_copy_number = 1;
        }

        // Insert book copies
        $status = 'available';
        $inserted_count = 0;

        foreach ($accession_codes as $index => $code) {
            $copy_number = $next_copy_number + $index;
            
            $insert_copy = $conn->prepare('INSERT INTO book_copies (book_id, accession_code, copy_number, status) VALUES (?, ?, ?, ?)');
            if (!$insert_copy) {
                throw new Exception('Prepare failed: ' . $conn->error);
            }
            
            // Use separate variables for binding to avoid reference issues
            $code_bind = $code;
            $copy_num_bind = $copy_number;
            $status_bind = $status;
            $book_id_bind = $book_id;
            
            $insert_copy->bind_param('isis', $book_id_bind, $code_bind, $copy_num_bind, $status_bind);

            if (!$insert_copy->execute()) {
                throw new Exception('Error adding book copy for code ' . $code . ': ' . $insert_copy->error);
            }
            
            $inserted_count++;
            $insert_copy->close();
        }
        
        // Verify all copies were inserted
        if ($inserted_count !== $quantity) {
            throw new Exception('Expected to insert ' . $quantity . ' copies but only inserted ' . $inserted_count);
        }
        
        // Update books table with correct quantity and available count
        // For existing books, increment the counts; for new books, set them to the quantity
        $update_book = $conn->prepare('UPDATE books SET quantity = quantity + ?, available = available + ? WHERE id = ?');
        if (!$update_book) {
            throw new Exception('Prepare failed: ' . $conn->error);
        }
        
        $available_count = $quantity; // All new copies start as available
        $update_book->bind_param('iii', $quantity, $available_count, $book_id);
        
        if (!$update_book->execute()) {
            throw new Exception('Error updating book counts: ' . $conn->error);
        }
        
        $update_book->close();
        $conn->commit();

        $_SESSION['success'] = 'Added ' . $quantity . ' copy(' . ($quantity !== 1 ? 'ies' : '') . ') successfully!';
        header('Location: catalog_page.php');
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = $e->getMessage();
        header('Location: catalog_page.php');
        exit();
    }

} else {
    // If not POST request, redirect to catalog page
    header('Location: catalog_page.php');
    exit();
}

$conn->close();
?>

