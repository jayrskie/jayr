<?php
session_start();
require_once 'connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $book_id = isset($_POST['book_id']) ? intval($_POST['book_id']) : 0;
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $isbn = isset($_POST['isbn']) ? trim($_POST['isbn']) : '';
    $author = isset($_POST['author']) ? trim($_POST['author']) : '';
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    $shelf_location_id = isset($_POST['shelf_location_id']) && $_POST['shelf_location_id'] !== '' ? intval($_POST['shelf_location_id']) : null;
    $new_accession_codes = isset($_POST['new_accession_codes']) ? trim($_POST['new_accession_codes']) : '';
    $new_auto_generate = isset($_POST['new_auto_generate']) && $_POST['new_auto_generate'] ? true : false;
    $new_auto_generate_count = isset($_POST['new_auto_generate_count']) ? intval($_POST['new_auto_generate_count']) : 0;

    // Validate inputs
    if ($book_id <= 0 || empty($title) || empty($author) || $category_id <= 0) {
        $_SESSION['error'] = 'Book ID, Title, Author and Category are mandatory';
        header('Location: edit_book.php?id=' . $book_id);
        exit();
    }

    // ISBN required and must be ISBN-13 (13 digits total). Dashes allowed but not consecutive.
    if (empty($isbn)) {
        $_SESSION['error'] = 'ISBN is required';
        header('Location: edit_book.php?id=' . $book_id);
        exit();
    }

    if (!preg_match('/^(?!.*--)(?:[0-9]-?){12}[0-9]$/', $isbn)) {
        $_SESSION['error'] = 'ISBN must contain exactly 13 digits (dashes allowed, but not consecutive). Example: 978-0-553-38016-3';
        header('Location: edit_book.php?id=' . $book_id);
        exit();
    }

    // Parse new accession codes or auto-generate if requested
    $accession_codes = [];
    if ($new_auto_generate) {
        $count = max(0, $new_auto_generate_count);
        if ($count <= 0) {
            $_SESSION['error'] = 'Invalid number of copies to generate';
            header('Location: edit_book.php?id=' . $book_id);
            exit();
        }

        $generated = [];
        $check_accession = $conn->prepare('SELECT id FROM book_copies WHERE accession_code = ?');
        if (!$check_accession) {
            $_SESSION['error'] = 'Database error preparing accession check: ' . $conn->error;
            header('Location: edit_book.php?id=' . $book_id);
            exit();
        }

        while (count($generated) < $count) {
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
        if (!empty($new_accession_codes)) {
            $accession_codes = array_filter(array_map('trim', explode(',', $new_accession_codes)));

            // Validate all accession codes
            $check_accession = $conn->prepare('SELECT id FROM book_copies WHERE accession_code = ?');
            if (!$check_accession) {
                $_SESSION['error'] = 'Database error preparing accession check: ' . $conn->error;
                header('Location: edit_book.php?id=' . $book_id);
                exit();
            }

            foreach ($accession_codes as $code) {
                if (!preg_match('/^\d{4}$/', $code)) {
                    $_SESSION['error'] = "Accession Code '$code' must be exactly 4 digits";
                    header('Location: edit_book.php?id=' . $book_id);
                    exit();
                }

                $check_accession->bind_param('s', $code);
                $check_accession->execute();
                $check_accession->store_result();

                if ($check_accession->num_rows > 0) {
                    $_SESSION['error'] = "Accession Code '$code' already exists";
                    $check_accession->close();
                    header('Location: edit_book.php?id=' . $book_id);
                    exit();
                }
                $check_accession->free_result();
            }
            $check_accession->close();
        }
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

        if ($shelf_location_id !== null && $shelf_location_id > 0) {
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
        } else {
            // explicit removal: clear stored text and id
            $shelf_location_id_db = null;
            $shelf_code = '';
        }

        // Update book including textual category and shelf_location columns
        $update_book = $conn->prepare('UPDATE books SET title = ?, isbn = ?, author = ?, category_id = ?, shelf_location_id = ?, category = ?, shelf_location = ? WHERE id = ?');
        if (!$update_book) {
            throw new Exception('Prepare failed: ' . $conn->error);
        }
        $update_book->bind_param('sssisssi', $title, $isbn, $author, $category_id, $shelf_location_id_db, $category_name, $shelf_code, $book_id);

        if (!$update_book->execute()) {
            throw new Exception('Error updating book: ' . $conn->error);
        }
        $update_book->close();

        // Add new copies if any
        if (!empty($accession_codes)) {
            // Get the current highest copy number for this book
            $get_max_copy = $conn->prepare('SELECT MAX(copy_number) as max_copy FROM book_copies WHERE book_id = ?');
            $get_max_copy->bind_param('i', $book_id);
            $get_max_copy->execute();
            $max_result = $get_max_copy->get_result();
            $max_row = $max_result->fetch_assoc();
            $next_copy_number = ($max_row['max_copy'] ?? 0) + 1;
            $get_max_copy->close();

            // Insert new book copies
            $status = 'available';
            $inserted_count = 0;

            foreach ($accession_codes as $index => $code) {
                $copy_number = $next_copy_number + $index;
                
                $insert_copy = $conn->prepare('INSERT INTO book_copies (book_id, accession_code, copy_number, status) VALUES (?, ?, ?, ?)');
                if (!$insert_copy) {
                    throw new Exception('Prepare failed: ' . $conn->error);
                }
                
                $code_bind = $code;
                $copy_num_bind = $copy_number;
                $status_bind = $status;
                
                $insert_copy->bind_param('isis', $book_id, $code_bind, $copy_num_bind, $status_bind);

                if (!$insert_copy->execute()) {
                    throw new Exception('Error adding book copy for code ' . $code . ': ' . $insert_copy->error);
                }
                
                $inserted_count++;
                $insert_copy->close();
            }

            // Update books table with correct quantity and available count
            $update_quantities = $conn->prepare('UPDATE books SET quantity = quantity + ?, available = available + ? WHERE id = ?');
            if (!$update_quantities) {
                throw new Exception('Prepare failed: ' . $conn->error);
            }
            
            $copies_added = count($accession_codes);
            $update_quantities->bind_param('iii', $copies_added, $copies_added, $book_id);
            
            if (!$update_quantities->execute()) {
                throw new Exception('Error updating book counts: ' . $conn->error);
            }
            $update_quantities->close();

            $conn->commit();
            $_SESSION['success'] = 'Book updated successfully! Added ' . count($accession_codes) . ' new copy(' . (count($accession_codes) !== 1 ? 'ies' : '') . ').';
        } else {
            $conn->commit();
            $_SESSION['success'] = 'Book updated successfully!';
        }

        header('Location: catalog_page.php');
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = $e->getMessage();
        header('Location: edit_book.php?id=' . $book_id);
        exit();
    }
} else {
    header('Location: catalog_page.php');
    exit();
}

$conn->close();
?>
