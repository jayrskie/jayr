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
function sendBorrowApprovalEmail($userEmail, $userName, $bookTitle, $bookAuthor = null, $dueDate = null, $borrowType = null, $borrowDuration = null, $borrowSchedule = null, $requestType = null) {
    $subject = 'Borrow Request Approved';
    
    $transactionDetails = '<table style="width: 100%; border-collapse: collapse; margin: 1rem 0; font-size: 0.95rem;">';
    
    $transactionDetails .= '<tr><td style="padding: 0.5rem 1rem; background: #f5f5f5; border: 1px solid #ddd;"><strong>Book Title:</strong></td><td style="padding: 0.5rem 1rem; border: 1px solid #ddd;">' . htmlspecialchars($bookTitle, ENT_QUOTES, 'UTF-8') . '</td></tr>';
    
    if ($bookAuthor) {
        $transactionDetails .= '<tr><td style="padding: 0.5rem 1rem; background: #f5f5f5; border: 1px solid #ddd;"><strong>Author:</strong></td><td style="padding: 0.5rem 1rem; border: 1px solid #ddd;">' . htmlspecialchars($bookAuthor, ENT_QUOTES, 'UTF-8') . '</td></tr>';
    }
    
    if ($requestType) {
        $displayRequestType = ($requestType === 'CCT') ? 'CCT' : 'Online';
        $transactionDetails .= '<tr><td style="padding: 0.5rem 1rem; background: #f5f5f5; border: 1px solid #ddd;"><strong>Request Type:</strong></td><td style="padding: 0.5rem 1rem; border: 1px solid #ddd;">' . $displayRequestType . '</td></tr>';
    }
    
    if ($borrowType) {
        if ($borrowType === 'takehome') {
            $displayBorrowType = 'Take Home (' . ($borrowDuration ?? 7) . ' days)';
        } else {
            $displayBorrowType = 'Classroom (' . (($borrowSchedule === 'am') ? 'AM' : 'PM') . ')';
        }
        $transactionDetails .= '<tr><td style="padding: 0.5rem 1rem; background: #f5f5f5; border: 1px solid #ddd;"><strong>Borrow Type:</strong></td><td style="padding: 0.5rem 1rem; border: 1px solid #ddd;">' . $displayBorrowType . '</td></tr>';
    }
    
    if ($dueDate) {
        $transactionDetails .= '<tr><td style="padding: 0.5rem 1rem; background: #f5f5f5; border: 1px solid #ddd;"><strong>Due Date:</strong></td><td style="padding: 0.5rem 1rem; border: 1px solid #ddd;"><strong style="color: #d32f2f;">' . htmlspecialchars($dueDate, ENT_QUOTES, 'UTF-8') . '</strong></td></tr>';
    }
    
    $transactionDetails .= '</table>';
    
    $body = "
        <h2>Your Borrow Request is Approved!</h2>
        <p>Hello " . htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') . ",</p>
        <p>Your request to borrow a book has been approved. Here are the transaction details:</p>
        $transactionDetails
        <p>Best regards,<br>Library System</p>
    ";
    
    return sendEmail($userEmail, $userName, $subject, $body);
}

/**
 * Send borrow rejection email
 */
function sendBorrowRejectionEmail($userEmail, $userName, $bookTitle) {
    $subject = 'Borrow Request Rejected';
    $body = "
        <h2>Borrow Request Status Update</h2>
        <p>Hello $userName,</p>
        <p>Unfortunately, your request to borrow <strong>$bookTitle</strong> has been rejected.</p>
        <p>Best regards,<br>Library System</p>
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
        <p>Hello $userName,</p>
        <p>This is a reminder that <strong>$bookTitle</strong> is due on <strong>$dueDate</strong>.</p>
        <p>Please return it to the library before the due date to avoid late fees.</p>
        <p>Best regards,<br>Library System</p>
    ";
    
    return sendEmail($userEmail, $userName, $subject, $body);
}

/**
 * Send welcome email to new user
 */
function sendWelcomeEmail($userEmail, $userName) {
    $subject = 'Welcome to Library System';
    $body = "
        <h2>Welcome to Our Library System!</h2>
        <p>Hello $userName,</p>
        <p>Thank you for registering. Your account has been created successfully.</p>
        <p>You can now browse and borrow books from our library.</p>
        <p>Best regards,<br>Library System</p>
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
        <p>Hello $userName,</p>
        <p>We received a request to reset your password. Click the link below to reset it:</p>
        <p><a href='$resetLink'>Reset Password</a></p>
        <p>If you didn't request this, ignore this email.</p>
        <p>Best regards,<br>Library System</p>
    ";
    
    return sendEmail($userEmail, $userName, $subject, $body);
}
?>
