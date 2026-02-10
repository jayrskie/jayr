<?php
session_start();
require_once 'connect.php';

// Check if user is admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Verify Books & Copies — ICT 3A Library</title>
  <link rel="stylesheet" href="styles.css" />
  <style>
    .verify-container {
      max-width: 1200px;
      margin: 2rem auto;
      padding: 2rem;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 2rem;
    }
    th, td {
      padding: 0.75rem;
      text-align: left;
      border: 1px solid #ddd;
    }
    th {
      background: var(--accent);
      color: white;
    }
    tr:nth-child(even) {
      background: #f9fafb;
    }
    .status-available {
      color: #2e7d32;
      font-weight: bold;
    }
    .status-damaged {
      color: #f57c00;
      font-weight: bold;
    }
    .status-lost {
      color: #c33;
      font-weight: bold;
    }
  </style>
</head>
<body>
  <?php include 'header_template.php'; ?>

  <main>
    <div class="verify-container">
      <h1>Database Verification</h1>
      
      <h2>Books with Copy Counts</h2>
      <table>
        <thead>
          <tr>
            <th>Book ID</th>
            <th>Title</th>
            <th>ISBN</th>
            <th>Total Copies (DB)</th>
            <th>Available Copies (DB)</th>
            <th>Created At</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $query = 'SELECT 
                      b.id,
                      b.title,
                      b.isbn,
                      COUNT(bc.id) as total_copies,
                      SUM(CASE WHEN bc.status = "available" THEN 1 ELSE 0 END) as available_copies,
                      b.created_at
                    FROM books b
                    LEFT JOIN book_copies bc ON b.id = bc.book_id
                    GROUP BY b.id
                    ORDER BY b.created_at DESC
                    LIMIT 10';
          
          $result = $conn->query($query);
          while ($row = $result->fetch_assoc()) {
            echo '<tr>';
            echo '<td>' . $row['id'] . '</td>';
            echo '<td>' . htmlspecialchars($row['title']) . '</td>';
            echo '<td>' . htmlspecialchars($row['isbn']) . '</td>';
            echo '<td>' . $row['total_copies'] . '</td>';
            echo '<td>' . $row['available_copies'] . '</td>';
            echo '<td>' . $row['created_at'] . '</td>';
            echo '</tr>';
          }
          ?>
        </tbody>
      </table>

      <h2>All Book Copies with Status</h2>
      <table>
        <thead>
          <tr>
            <th>Copy ID</th>
            <th>Book ID</th>
            <th>Book Title</th>
            <th>Accession Code</th>
            <th>Copy #</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $query = 'SELECT 
                      bc.id,
                      bc.book_id,
                      b.title,
                      bc.accession_code,
                      bc.copy_number,
                      bc.status
                    FROM book_copies bc
                    JOIN books b ON bc.book_id = b.id
                    ORDER BY bc.book_id DESC, bc.copy_number ASC
                    LIMIT 50';
          
          $result = $conn->query($query);
          while ($row = $result->fetch_assoc()) {
            $status_class = 'status-' . $row['status'];
            echo '<tr>';
            echo '<td>' . $row['id'] . '</td>';
            echo '<td>' . $row['book_id'] . '</td>';
            echo '<td>' . htmlspecialchars($row['title']) . '</td>';
            echo '<td style="font-family: monospace;">' . htmlspecialchars($row['accession_code']) . '</td>';
            echo '<td>' . $row['copy_number'] . '</td>';
            echo '<td class="' . $status_class . '">' . ucfirst($row['status']) . '</td>';
            echo '</tr>';
          }
          ?>
        </tbody>
      </table>
    </div>
  </main>
</body>
</html>
