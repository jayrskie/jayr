<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'config_email.php';

/**
 * Send a generic email
 * @param string $recipientEmail
 * @param string $recipientName
 * @param string $subject
 * @param string $body (HTML)
 * @return array ['success' => bool, 'message' => string]
 */
function sendEmail($recipientEmail, $recipientName, $subject, $body) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = MAIL_SECURE;
        $mail->Port       = MAIL_PORT;

        // Sender
        $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);

        // Receiver
        $mail->addAddress($recipientEmail, $recipientName);

        // Content
        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return ['success' => true, 'message' => 'Email sent successfully!'];

    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error: ' . $mail->ErrorInfo];
    }
}

/**
 * Send borrow approval email
 */
function sendBorrowApprovalEmail($userEmail, $userName, $bookTitle, $bookAuthor = null, $dueDate = null, $borrowType = null, $borrowDuration = null, $borrowSchedule = null, $requestType = null, $approvedDate = null, $accessionCode = null) {
    $subject = 'Borrow Request Approved';

    $transactionDetails = '<table style="width: 100%; border-collapse: collapse; margin: 1rem 0; font-size: 0.95rem;">';

    // Title by Author
    $bookDisplay = htmlspecialchars($bookTitle, ENT_QUOTES, 'UTF-8');
    if ($bookAuthor) {
        $bookDisplay .= ' by ' . htmlspecialchars($bookAuthor, ENT_QUOTES, 'UTF-8');
    }
    $transactionDetails .= '<tr><td style="padding: 0.5rem 1rem; background: #f5f5f5; border: 1px solid #ddd;"><strong>Book:</strong></td><td style="padding: 0.5rem 1rem; border: 1px solid #ddd;">' . $bookDisplay . '</td></tr>';

    if ($approvedDate) {
        $transactionDetails .= '<tr><td style="padding: 0.5rem 1rem; background: #f5f5f5; border: 1px solid #ddd;"><strong>Borrow Date:</strong></td><td style="padding: 0.5rem 1rem; border: 1px solid #ddd;">' . htmlspecialchars($approvedDate, ENT_QUOTES, 'UTF-8') . '</td></tr>';
    }

    if ($dueDate) {
        $transactionDetails .= '<tr><td style="padding: 0.5rem 1rem; background: #f5f5f5; border: 1px solid #ddd;"><strong>Due Date:</strong></td><td style="padding: 0.5rem 1rem; border: 1px solid #ddd;"><strong style="color: #d32f2f;">' . htmlspecialchars($dueDate, ENT_QUOTES, 'UTF-8') . '</strong></td></tr>';
    }

    $transactionDetails .= '</table>';

    $body = "
        <h2>Your Borrow Request is Approved!</h2>
        <p>Hello " . htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') . "!</p>
        <p>Your request to borrow a book has been approved. Here are the transaction details:</p>
        $transactionDetails
        <p>Best Regards,<br> AUJRC Library System</p>
    ";

    return sendEmail($userEmail, $userName, $subject, $body);
}

/**
 * Send borrow rejection email
 */
function sendBorrowRejectionEmail($userEmail, $userName, $bookTitle, $bookAuthor = null, $rejectionReason = null) {
    $subject = 'Borrow Request - Not Approved';

    $transactionDetails = '<table style="width: 100%; border-collapse: collapse; margin: 1rem 0; font-size: 0.95rem;">';

    // Title by Author
    $bookDisplay = htmlspecialchars($bookTitle, ENT_QUOTES, 'UTF-8');
    if ($bookAuthor) {
        $bookDisplay .= ' by ' . htmlspecialchars($bookAuthor, ENT_QUOTES, 'UTF-8');
    }
    $transactionDetails .= '<tr><td style="padding: 0.5rem 1rem; background: #f5f5f5; border: 1px solid #ddd;"><strong>Book:</strong></td><td style="padding: 0.5rem 1rem; border: 1px solid #ddd;">' . $bookDisplay . '</td></tr>';

    if ($rejectionReason) {
        $transactionDetails .= '<tr><td style="padding: 0.5rem 1rem; background: #f5f5f5; border: 1px solid #ddd;"><strong>Reason:</strong></td><td style="padding: 0.5rem 1rem; border: 1px solid #ddd;">' . htmlspecialchars($rejectionReason, ENT_QUOTES, 'UTF-8') . '</td></tr>';
    }

    $transactionDetails .= '</table>';

    $body = "
        <h2>Borrow Request Status Update</h2>
        <p>Hello " . htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') . "!</p>
        <p>Unfortunately, your request to borrow the following book has been rejected:</p>
        $transactionDetails
        <p>If you have any questions or require assistance, please contact the library staff.</p>
        <p>Best Regards,<br> AUJRC Library System</p>
    ";

    return sendEmail($userEmail, $userName, $subject, $body);
}

/**
 * Send book return reminder email
 */
function sendReturnReminderEmail($userEmail, $userName, $bookTitle, $dueDate) {
    $subject = 'Book Return Reminder';
    $body = "
        <h2>Book Return Reminder</h2>
        <p>Hello " . htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') . "!</p>
        <p>This is a reminder that <strong>$bookTitle</strong> book that you borrow is due on <strong>$dueDate</strong>.</p>
        <p>Please return it to the library to avoid late fees.</p>
        <p>Best regards,<br>AUJRC Library System</p>
    ";
    
    return sendEmail($userEmail, $userName, $subject, $body);
}

/**
 * Send password reset email
 */
function sendPasswordResetEmail($userEmail, $userName, $resetLink) {
    $subject = 'Password Reset Request';
    $body = "
        <h2>Password Reset Request</h2>
        <p>Hello " . htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') . "!</p>
        <p>We received a request to reset your password. Click the link below to reset it:</p>
        <p><a href='$resetLink'>Reset Password</a></p>
        <p>If you didn't request this, ignore this email.</p>
        <p>Best regards,<br>Library System</p>
    ";
    
    return sendEmail($userEmail, $userName, $subject, $body);
}

/**
 * Send welcome email to confirm email validity and successful registration
 */
function sendWelcomeEmail($userEmail, $userName, $libraryId) {
    $subject = 'Welcome to AUJRC Library System!';
    
    $body = "
        <h2>Welcome to AUJRC Library System!</h2>
        <p>Hello " . htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') . "!</p>
        <p>Thank you for registering to our library system. This email confirms that your account has been successfully created and that <strong>" . htmlspecialchars($userEmail, ENT_QUOTES, 'UTF-8') . "</strong> is a valid email address in our system.</p>
        
        <table style=\"width: 100%; border-collapse: collapse; margin: 1.5rem 0; font-size: 0.95rem;\">
            <tr><td style=\"padding: 0.5rem 1rem; background: #f5f5f5; border: 1px solid #ddd;\"><strong>Account Details:</strong></td></tr>
            <tr><td style=\"padding: 0.5rem 1rem; border: 1px solid #ddd;\"><strong>Name:</strong> " . htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') . "</td></tr>
            <tr><td style=\"padding: 0.5rem 1rem; border: 1px solid #ddd;\"><strong>Library ID:</strong> " . htmlspecialchars($libraryId, ENT_QUOTES, 'UTF-8') . "</td></tr>
            <tr><td style=\"padding: 0.5rem 1rem; border: 1px solid #ddd;\"><strong>Email:</strong> " . htmlspecialchars($userEmail, ENT_QUOTES, 'UTF-8') . "</td></tr>
        </table>
        
        <p>Our system will notify you once your borrowing request has been approved containing the detail of the transaction.</p>
        <p>You will also receive reminders when the due date of your borrowed books is approaching and if any items become overdue.</p>
        <p>You may now log in to the library system using your Library ID and password.</p>
        <p>If you have any questions or require assistance, please contact the library staff.</p>
        <p>Best Regards,<br><strong>AUJRC Library System</strong></p>
    ";
    
    return sendEmail($userEmail, $userName, $subject, $body);
}
?>
