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
                    COALESCE(bc_cat.category_name, b.category, "Uncategorized") as category,
                    COALESCE(sl.location_code, b.shelf_location, "N/A") as shelf_location,
                    COUNT(bc.id) as quantity,
                    SUM(CASE WHEN bc.status = "available" THEN 1 ELSE 0 END) as available
                  FROM books b
                  LEFT JOIN book_copies bc ON b.id = bc.book_id
                  LEFT JOIN book_categories bc_cat ON b.category_id = bc_cat.id
                  LEFT JOIN shelf_locations sl ON b.shelf_location_id = sl.id
                  GROUP BY b.id, b.category_id, b.shelf_location_id
                  ORDER BY category ASC, b.title ASC';
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
                        COALESCE(bc_cat.category_name, b.category, 'Uncategorized') as category,
                        COALESCE(sl.location_code, b.shelf_location, 'N/A') as shelf_location,
                        COUNT(bc.id) as quantity,
                        SUM(CASE WHEN bc.status = \"available\" THEN 1 ELSE 0 END) as available
                      FROM books b
                      LEFT JOIN book_copies bc ON b.id = bc.book_id
                      LEFT JOIN book_categories bc_cat ON b.category_id = bc_cat.id
                      LEFT JOIN shelf_locations sl ON b.shelf_location_id = sl.id
                      WHERE b.title LIKE ?
                      GROUP BY b.id, b.category_id, b.shelf_location_id
                      ORDER BY b.title ASC";
        } elseif ($searchType === 'author') {
            $query = "SELECT 
                        b.id,
                        b.title,
                        b.isbn,
                        b.author,
                        COALESCE(bc_cat.category_name, b.category, 'Uncategorized') as category,
                        COALESCE(sl.location_code, b.shelf_location, 'N/A') as shelf_location,
                        COUNT(bc.id) as quantity,
                        SUM(CASE WHEN bc.status = \"available\" THEN 1 ELSE 0 END) as available
                      FROM books b
                      LEFT JOIN book_copies bc ON b.id = bc.book_id
                      LEFT JOIN book_categories bc_cat ON b.category_id = bc_cat.id
                      LEFT JOIN shelf_locations sl ON b.shelf_location_id = sl.id
                      WHERE b.author LIKE ?
                      GROUP BY b.id, b.category_id, b.shelf_location_id
                      ORDER BY b.author ASC";
        } elseif ($searchType === 'category') {
            $query = "SELECT 
                        b.id,
                        b.title,
                        b.isbn,
                        b.author,
                        COALESCE(bc_cat.category_name, b.category, 'Uncategorized') as category,
                        COALESCE(sl.location_code, b.shelf_location, 'N/A') as shelf_location,
                        COUNT(bc.id) as quantity,
                        SUM(CASE WHEN bc.status = \"available\" THEN 1 ELSE 0 END) as available
                      FROM books b
                      LEFT JOIN book_copies bc ON b.id = bc.book_id
                      LEFT JOIN book_categories bc_cat ON b.category_id = bc_cat.id
                      LEFT JOIN shelf_locations sl ON b.shelf_location_id = sl.id
                      WHERE COALESCE(bc_cat.category_name, b.category) LIKE ?
                      GROUP BY b.id, b.category_id, b.shelf_location_id
                      ORDER BY category ASC";
        } else {
            // Search across all fields
            $query = "SELECT 
                        b.id,
                        b.title,
                        b.isbn,
                        b.author,
                        COALESCE(bc_cat.category_name, b.category, 'Uncategorized') as category,
                        COALESCE(sl.location_code, b.shelf_location, 'N/A') as shelf_location,
                        COUNT(bc.id) as quantity,
                        SUM(CASE WHEN bc.status = \"available\" THEN 1 ELSE 0 END) as available
                      FROM books b
                      LEFT JOIN book_copies bc ON b.id = bc.book_id
                      LEFT JOIN book_categories bc_cat ON b.category_id = bc_cat.id
                      LEFT JOIN shelf_locations sl ON b.shelf_location_id = sl.id
                      WHERE (b.title LIKE ? OR b.author LIKE ? OR COALESCE(bc_cat.category_name, b.category) LIKE ?)
                      GROUP BY b.id, b.category_id, b.shelf_location_id
                      ORDER BY category ASC, b.title ASC";
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
