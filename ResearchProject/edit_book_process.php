<?php
session_start();
require_once 'connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $book_id = isset($_POST['book_id']) ? intval($_POST['book_id']) : 0;
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $isbn = isset($_POST['isbn']) ? trim($_POST['isbn']) : '';
    $author = isset($_POST['author']) ? trim($_POST['author']) : '';
    $category = isset($_POST['category']) ? trim($_POST['category']) : '';
    $shelf_location = isset($_POST['shelf_location']) ? trim($_POST['shelf_location']) : '';
    $new_accession_codes = isset($_POST['new_accession_codes']) ? trim($_POST['new_accession_codes']) : '';

    // Validate inputs
    if ($book_id <= 0 || empty($title) || empty($author) || empty($category)) {
        $_SESSION['error'] = 'Book ID and all required fields are mandatory';
        header('Location: edit_book.php?id=' . $book_id);
        exit();
    }

    // Parse new accession codes if any
    $accession_codes = [];
    if (!empty($new_accession_codes)) {
        $accession_codes = array_filter(array_map('trim', explode(',', $new_accession_codes)));
        
        // Validate all accession codes
        foreach ($accession_codes as $code) {
            if (!preg_match('/^\d{7}$/', $code)) {
                $_SESSION['error'] = "Accession Code '$code' must be exactly 7 digits";
                header('Location: edit_book.php?id=' . $book_id);
                exit();
            }

            // Check if accession code already exists
            $check_accession = $conn->prepare('SELECT id FROM book_copies WHERE accession_code = ?');
            $check_accession->bind_param('s', $code);
            $check_accession->execute();
            $check_accession->store_result();

            if ($check_accession->num_rows > 0) {
                $_SESSION['error'] = "Accession Code '$code' already exists";
                $check_accession->close();
                header('Location: edit_book.php?id=' . $book_id);
                exit();
            }
            $check_accession->close();
        }
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Update book
        $update_book = $conn->prepare('UPDATE books SET title = ?, isbn = ?, author = ?, category = ?, shelf_location = ? WHERE id = ?');
        $update_book->bind_param('sssssi', $title, $isbn, $author, $category, $shelf_location, $book_id);

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
