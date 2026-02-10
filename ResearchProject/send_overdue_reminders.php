<?php
/**
 * Cron job to send overdue reminders to users
 * This script should be run daily via a cron job
 * 
 * Cron setup example (runs daily at 9 AM):
 * 0 9 * * * /usr/bin/php /path/to/send_overdue_reminders.php
 */

// Only run this script from command line (CLI), not from web requests
if (php_sapi_name() !== 'cli') {
    exit('This script must be run from command line only.');
}

require_once 'connect.php';
require_once 'email_helper.php';

try {
    // Query for overdue borrowed books that have not been returned
    // Only send reminders for registered users (not guests)
    $query = 'SELECT 
                bb.id as borrowed_id,
                bb.due_date,
                b.title as book_title,
                b.author,
                u.id as user_id,
                u.name as user_name,
                u.email as user_email,
                DATEDIFF(NOW(), bb.due_date) as days_overdue
              FROM borrowed_books bb
              JOIN books b ON bb.book_id = b.id
              JOIN users u ON bb.user_id = u.id
              WHERE bb.return_date IS NULL 
              AND bb.due_date < NOW()
              ORDER BY bb.due_date ASC';

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Database prepare error: ' . $conn->error);
    }

    if (!$stmt->execute()) {
        throw new Exception('Database execute error: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    if (!$result) {
        throw new Exception('Database get_result error: ' . $conn->error);
    }

    $remindersSent = 0;
    $remindersFailed = 0;

    while ($row = $result->fetch_assoc()) {
        // Format the due date nicely
        $dueDate = new DateTime($row['due_date']);
        $formattedDueDate = $dueDate->format('F j, Y');
        $daysOverdue = intval($row['days_overdue']);

        // Create the email subject with days overdue
        $subject = $daysOverdue === 1 
            ? 'Urgent: Your Book is Overdue by 1 Day' 
            : 'Urgent: Your Book is Overdue by ' . $daysOverdue . ' Days';

        // Create the reminder email with HTML table
        $overdueDetails = '<table style="width: 100%; border-collapse: collapse; margin: 1rem 0; font-size: 0.95rem;">';
        $overdueDetails .= '<tr><td style="padding: 0.5rem 1rem; background: #f5f5f5; border: 1px solid #ddd;"><strong>Book Title:</strong></td><td style="padding: 0.5rem 1rem; border: 1px solid #ddd;">' . htmlspecialchars($row['book_title'], ENT_QUOTES, 'UTF-8') . '</td></tr>';
        $overdueDetails .= '<tr><td style="padding: 0.5rem 1rem; background: #f5f5f5; border: 1px solid #ddd;"><strong>Author:</strong></td><td style="padding: 0.5rem 1rem; border: 1px solid #ddd;">' . htmlspecialchars($row['author'], ENT_QUOTES, 'UTF-8') . '</td></tr>';
        $overdueDetails .= '<tr><td style="padding: 0.5rem 1rem; background: #f5f5f5; border: 1px solid #ddd;"><strong>Due Date:</strong></td><td style="padding: 0.5rem 1rem; border: 1px solid #ddd;"><strong style="color: #d32f2f;">' . $formattedDueDate . '</strong></td></tr>';
        $overdueDetails .= '<tr><td style="padding: 0.5rem 1rem; background: #f5f5f5; border: 1px solid #ddd;"><strong>Days Overdue:</strong></td><td style="padding: 0.5rem 1rem; border: 1px solid #ddd;"><strong style="color: #d32f2f;">' . $daysOverdue . ' day' . ($daysOverdue !== 1 ? 's' : '') . '</strong></td></tr>';
        $overdueDetails .= '</table>';

        $body = "
            <h2>Overdue Book Reminder</h2>
            <p>Hello " . htmlspecialchars($row['user_name'], ENT_QUOTES, 'UTF-8') . ",</p>
            <p>This is a reminder that your borrowed book is now overdue. Please return it to the library as soon as possible.</p>
            $overdueDetails
            <p>Please return the book immediately to avoid additional late fees.</p>
            <p>Best regards,<br>Library System</p>
        ";

        // Send the email using the sendEmail function
        $result = sendEmail($row['user_email'], $row['user_name'], $subject, $body);

        if ($result['success']) {
            $remindersSent++;
            echo "[" . date('Y-m-d H:i:s') . "] Reminder sent to " . $row['user_email'] . " for book: " . $row['book_title'] . " (Overdue: " . $daysOverdue . " days)\n";
        } else {
            $remindersFailed++;
            echo "[" . date('Y-m-d H:i:s') . "] FAILED to send reminder to " . $row['user_email'] . " - " . $result['message'] . "\n";
        }
    }

    echo "\n[" . date('Y-m-d H:i:s') . "] Cron job completed. Reminders sent: $remindersSent, Failed: $remindersFailed\n";

} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

$conn->close();
?>
