<?php
require __DIR__ . '/config_email.php';
require __DIR__ . '/PHPMAILER/src/Exception.php';
require __DIR__ . '/PHPMAILER/src/PHPMailer.php';
require __DIR__ . '/PHPMAILER/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$to = getenv('TEST_EMAIL_RECIPIENT') ?: (isset($_GET['to']) ? $_GET['to'] : MAIL_FROM_ADDRESS);

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = MAIL_HOST;
    $mail->SMTPAuth = !empty(MAIL_USERNAME);
    if (!empty(MAIL_USERNAME)) {
        $mail->Username = MAIL_USERNAME;
        $mail->Password = MAIL_PASSWORD;
    }
    $mail->SMTPSecure = MAIL_SECURE;
    $mail->Port = MAIL_PORT;

    $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
    $mail->addAddress($to);
    $mail->Subject = 'AUJRC: Test email';
    $mail->Body = 'Test email sent at ' . date('c');

    if ($mail->send()) {
        echo "OK: Email sent to $to\n";
    } else {
        echo "FAIL: " . $mail->ErrorInfo . "\n";
    }
} catch (Exception $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
}

?>
