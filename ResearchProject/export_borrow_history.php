<?php
session_start();
require_once 'connect.php';

try {
    // Check if user is admin
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        throw new Exception('Unauthorized access');
    }

    // Fetch complete borrow history
    $query = 'SELECT 
                br.id as request_id,
                br.status as request_status,
                br.request_date,
                br.approved_date,
                br.request_type,
                bb.borrow_date, 
                bb.due_date,
                bb.return_date,
                bb.borrow_type,
                bb.borrow_duration,
                bb.borrow_schedule,
                bb.return_status,
                b.title as book_title, 
                b.author,
                b.accession_code,
                b.category,
                COALESCE(u.name, br.guest_name) as user_name,
                COALESCE(u.email, br.guest_name) as user_email
              FROM borrow_requests br
              JOIN books b ON br.book_id = b.id
              LEFT JOIN users u ON br.user_id = u.id
              LEFT JOIN borrowed_books bb ON br.id = bb.borrow_request_id
              WHERE br.status IN ("approved", "rejected")
              ORDER BY br.request_date DESC';

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Database prepare error: ' . $conn->error);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="borrow_history_' . date('Y-m-d_H-i-s') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Open output stream
    $output = fopen('php://output', 'w');

    // Write CSV header row
    fputcsv($output, [
        'Request ID',
        'Book Title',
        'Author',
        'Category',
        'Accession Code',
        'User Name',
        'User Email',
        'Request Type',
        'Request Date',
        'Request Status',
        'Approved Date',
        'Borrow Date',
        'Due Date',
        'Return Date',
        'Borrow Type',
        'Duration/Schedule',
        'Return Status'
    ]);

    // Write data rows
    while ($row = $result->fetch_assoc()) {
        // Format borrow type display
        $borrowTypeDisplay = '';
        if ($row['borrow_type'] === 'takehome') {
            $borrowTypeDisplay = 'Take Home (' . ($row['borrow_duration'] ?? '—') . ' days)';
        } else if ($row['borrow_type'] === 'classroom') {
            $schedule = $row['borrow_schedule'] === 'am' ? 'AM' : ($row['borrow_schedule'] === 'pm' ? 'PM' : '—');
            $borrowTypeDisplay = 'Classroom (' . $schedule . ')';
        } else {
            $borrowTypeDisplay = 'Not configured';
        }

        // Format return status
        $returnStatus = $row['return_status'] ?? 'Pending';
        if ($row['request_status'] === 'rejected') {
            $returnStatus = 'Rejected';
        } else if ($row['return_date'] === null) {
            $returnStatus = 'Borrowed';
        }

        fputcsv($output, [
            $row['request_id'],
            $row['book_title'],
            $row['author'],
            $row['category'],
            $row['accession_code'],
            $row['user_name'],
            $row['user_email'],
            $row['request_type'] === 'CCT' ? 'CCT' : 'Online',
            $row['request_date'] ? date('m/d/Y H:i', strtotime($row['request_date'])) : '',
            ucfirst($row['request_status']),
            $row['approved_date'] ? date('m/d/Y H:i', strtotime($row['approved_date'])) : '',
            $row['borrow_date'] ? date('m/d/Y H:i', strtotime($row['borrow_date'])) : '',
            $row['due_date'] ? date('m/d/Y H:i', strtotime($row['due_date'])) : '',
            $row['return_date'] ? date('m/d/Y H:i', strtotime($row['return_date'])) : '',
            $row['borrow_type'] ?? '',
            $borrowTypeDisplay,
            $returnStatus
        ]);
    }

    fclose($output);
    $stmt->close();
    $conn->close();
    exit();

} catch (Exception $e) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit();
}
?>
