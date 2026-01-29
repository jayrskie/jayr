<?php
session_start();
require_once 'connect.php';

header('Content-Type: application/json');

try {
    $searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
    $searchType = isset($_GET['type']) ? trim($_GET['type']) : 'all'; // all, title, author, category
    
    if (empty($searchQuery)) {
        // If no search query, return all active books with copy counts
        $query = 'SELECT 
                    b.id,
                    b.title,
                    b.isbn,
                    b.author,
                    b.category,
                    b.shelf_location,
                    COUNT(bc.id) as quantity,
                    SUM(CASE WHEN bc.status = "available" THEN 1 ELSE 0 END) as available
                  FROM books b
                  LEFT JOIN book_copies bc ON b.id = bc.book_id
                  GROUP BY b.id
                  ORDER BY b.category ASC, b.title ASC';
        $result = $conn->query($query);
    } else {
        // Escape the search query for safety
        $searchQueryEscaped = '%' . $conn->real_escape_string($searchQuery) . '%';
        
        if ($searchType === 'title') {
            $query = "SELECT 
                        b.id,
                        b.title,
                        b.isbn,
                        b.author,
                        b.category,
                        b.shelf_location,
                        COUNT(bc.id) as quantity,
                        SUM(CASE WHEN bc.status = \"available\" THEN 1 ELSE 0 END) as available
                      FROM books b
                      LEFT JOIN book_copies bc ON b.id = bc.book_id
                      WHERE b.title LIKE ?
                      GROUP BY b.id
                      ORDER BY b.title ASC";
        } elseif ($searchType === 'author') {
            $query = "SELECT 
                        b.id,
                        b.title,
                        b.isbn,
                        b.author,
                        b.category,
                        b.shelf_location,
                        COUNT(bc.id) as quantity,
                        SUM(CASE WHEN bc.status = \"available\" THEN 1 ELSE 0 END) as available
                      FROM books b
                      LEFT JOIN book_copies bc ON b.id = bc.book_id
                      WHERE b.author LIKE ?
                      GROUP BY b.id
                      ORDER BY b.author ASC";
        } elseif ($searchType === 'category') {
            $query = "SELECT 
                        b.id,
                        b.title,
                        b.isbn,
                        b.author,
                        b.category,
                        b.shelf_location,
                        COUNT(bc.id) as quantity,
                        SUM(CASE WHEN bc.status = \"available\" THEN 1 ELSE 0 END) as available
                      FROM books b
                      LEFT JOIN book_copies bc ON b.id = bc.book_id
                      WHERE b.category LIKE ?
                      GROUP BY b.id
                      ORDER BY b.category ASC";
        } else {
            // Search across all fields
            $query = "SELECT 
                        b.id,
                        b.title,
                        b.isbn,
                        b.author,
                        b.category,
                        b.shelf_location,
                        COUNT(bc.id) as quantity,
                        SUM(CASE WHEN bc.status = \"available\" THEN 1 ELSE 0 END) as available
                      FROM books b
                      LEFT JOIN book_copies bc ON b.id = bc.book_id
                      WHERE (b.title LIKE ? OR b.author LIKE ? OR b.category LIKE ?)
                      GROUP BY b.id
                      ORDER BY b.category ASC, b.title ASC";
        }
        
        $stmt = $conn->prepare($query);
        
        if ($searchType === 'all') {
            $stmt->bind_param('sss', $searchQueryEscaped, $searchQueryEscaped, $searchQueryEscaped);
        } else {
            $stmt->bind_param('s', $searchQueryEscaped);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
    }
    
    if ($result) {
        $books = [];
        while ($row = $result->fetch_assoc()) {
            $books[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'books' => $books,
            'query' => $searchQuery,
            'type' => $searchType
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error searching books: ' . $conn->error
        ]);
    }
    
    if (isset($stmt)) {
        $stmt->close();
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
