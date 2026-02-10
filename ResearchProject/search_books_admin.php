<?php
session_start();
require_once 'connect.php';

header('Content-Type: application/json');

try {
    $searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
    
    if (empty($searchQuery)) {
        // If no search query, return all books with copy counts
        $query = 'SELECT 
                    b.id,
                    b.title,
                    b.isbn,
                    b.author,
                    COALESCE(bc_cat.category_name, b.category, "Uncategorized") as category,
                    COALESCE(sl.location_code, b.shelf_location, "N/A") as shelf_location,
                    b.archived,
                    b.created_at,
                    COUNT(bc.id) as quantity,
                    SUM(CASE WHEN bc.status = "available" THEN 1 ELSE 0 END) as available
                  FROM books b
                  LEFT JOIN book_copies bc ON b.id = bc.book_id
                  LEFT JOIN book_categories bc_cat ON b.category_id = bc_cat.id
                  LEFT JOIN shelf_locations sl ON b.shelf_location_id = sl.id
                  GROUP BY b.id, b.category_id, b.shelf_location_id
                  ORDER BY b.created_at DESC';
        $result = $conn->query($query);
    } else {
        // Escape and search across all fields for admin
        $searchQueryEscaped = '%' . $conn->real_escape_string($searchQuery) . '%';
        
        $query = "SELECT 
                    b.id,
                    b.title,
                    b.isbn,
                    b.author,
                    COALESCE(bc_cat.category_name, b.category, 'Uncategorized') as category,
                    COALESCE(sl.location_code, b.shelf_location, 'N/A') as shelf_location,
                    b.archived,
                    b.created_at,
                    COUNT(bc.id) as quantity,
                    SUM(CASE WHEN bc.status = \"available\" THEN 1 ELSE 0 END) as available
                  FROM books b
                  LEFT JOIN book_copies bc ON b.id = bc.book_id
                  LEFT JOIN book_categories bc_cat ON b.category_id = bc_cat.id
                  LEFT JOIN shelf_locations sl ON b.shelf_location_id = sl.id
                  WHERE b.title LIKE ? OR b.author LIKE ? OR COALESCE(bc_cat.category_name, b.category) LIKE ? OR b.isbn LIKE ?
                  GROUP BY b.id, b.category_id, b.shelf_location_id
                  ORDER BY b.created_at DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ssss', $searchQueryEscaped, $searchQueryEscaped, $searchQueryEscaped, $searchQueryEscaped);
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
            'query' => $searchQuery
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
