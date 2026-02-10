<?php
session_start();
require_once 'connect.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $library_id = isset($_POST['library_id']) ? trim($_POST['library_id']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    // Validate inputs
    if (empty($library_id)) {
        $_SESSION['error'] = 'Library ID is required';
        header('Location: login_page.php');
        exit();
    }

    // If no password provided, use library_id as default
    if (empty($password)) {
        $password = $library_id;
    }

    // Prepare statement to fetch user
    $stmt = $conn->prepare('SELECT id, library_id, name, email, password, role FROM users WHERE library_id = ?');
    $stmt->bind_param('s', $library_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Password is correct, set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            
            // Track login time
            $updateLoginStmt = $conn->prepare('UPDATE users SET last_login = NOW(), last_logout = NULL WHERE id = ?');
            $updateLoginStmt->bind_param('i', $user['id']);
            $updateLoginStmt->execute();
            $updateLoginStmt->close();
            
            // Redirect based on role
            if ($user['role'] === 'user') {
                header('Location: index.php');
            } else {
                // On admin login, check for overdue items and send reminders if appropriate
                require_once 'email_helper.php';

                // Ensure reminder log table exists
                $createTableSql = "CREATE TABLE IF NOT EXISTS overdue_reminder_log (
                    borrowed_id INT PRIMARY KEY,
                    last_sent_date DATE NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
                $conn->query($createTableSql);

                // Find overdue borrowed books (not returned) whose due_date <= NOW()
                $remQuery = "SELECT bb.id as borrowed_id, bb.due_date, b.title as book_title, u.id as user_id, u.name as user_name, u.email as user_email
                    FROM borrowed_books bb
                    JOIN books b ON bb.book_id = b.id
                    JOIN users u ON bb.user_id = u.id
                    WHERE bb.return_date IS NULL AND bb.due_date <= NOW()";

                if ($remStmt = $conn->prepare($remQuery)) {
                    $remStmt->execute();
                    $remRes = $remStmt->get_result();
                    while ($r = $remRes->fetch_assoc()) {
                        $borrowedId = (int)$r['borrowed_id'];
                        $dueDateTime = new DateTime($r['due_date']);

                        // Build today's scheduled reminder datetime using the original due time
                        $dueTime = $dueDateTime->format('H:i:s');
                        $todayDue = new DateTime(date('Y-m-d') . ' ' . $dueTime);
                        $now = new DateTime();

                        // Skip if admin logged in before today's scheduled reminder time
                        if ($now < $todayDue) {
                            continue;
                        }

                        // Check last sent date
                        $logStmt = $conn->prepare('SELECT last_sent_date FROM overdue_reminder_log WHERE borrowed_id = ?');
                        $logStmt->bind_param('i', $borrowedId);
                        $logStmt->execute();
                        $logRes = $logStmt->get_result();
                        $logRow = $logRes->fetch_assoc();
                        $lastSent = $logRow ? $logRow['last_sent_date'] : null;

                        // If already sent today, skip
                        if ($lastSent !== null && $lastSent === date('Y-m-d')) {
                            continue;
                        }

                        // Send reminder now
                        $formattedDue = $dueDateTime->format('F j, Y \\a\\t g:i A');
                        sendReturnReminderEmail($r['user_email'], $r['user_name'], $r['book_title'], $formattedDue);

                        // Upsert last_sent_date to today
                        $today = date('Y-m-d');
                        if ($logRow) {
                            $up = $conn->prepare('UPDATE overdue_reminder_log SET last_sent_date = ? WHERE borrowed_id = ?');
                            $up->bind_param('si', $today, $borrowedId);
                            $up->execute();
                        } else {
                            $ins = $conn->prepare('INSERT INTO overdue_reminder_log (borrowed_id, last_sent_date) VALUES (?, ?)');
                            $ins->bind_param('is', $borrowedId, $today);
                            $ins->execute();
                        }
                    }
                }

                header('Location: admin.php');
            }
            exit();
        } else {
            $_SESSION['error'] = 'Invalid library ID or password';
            header('Location: login_page.php');
            exit();
        }
    } else {
        $_SESSION['error'] = 'Invalid library ID or password';
        header('Location: login_page.php');
        exit();
    }
    $stmt->close();
} else {
    // If not POST request, redirect to login page
    header('Location: login_page.php');
    exit();
}

$conn->close();
?>