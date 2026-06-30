<?php

use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';


 // KRIJON DHE KTHEN OBJEKTIN MAILER

function getMailer()
{
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;

    // EMAIL & APP PASSWORD (GMAIL)
   $mail->Username = 'your_email_here@gmail.com';
    $mail->Password = 'your_gmail_app_password_here';

    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('automaticmmail@gmail.com', 'Bookstore');
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';

    return $mail;
}


 // EMAIL KONFIRMIMI PAS REGJISTRIMIT

function sendConfirmEmail($email, $token)
{
    $mail = getMailer();

    $link = "http://localhost/BookStore/services/auth/verify.php?token=" . $token;

    $mail->addAddress($email);
    $mail->Subject = 'Confirm your account';
    $mail->Body    = "
        <p>Kliko linkun për të aktivizuar account-in:</p>
        <a href='$link'>$link</a>
    ";

    return $mail->send();
}


 // EMAIL PËR FORGOT PASSWORD

function sendForgotPasswordEmail($email, $token)
{
    $mail = getMailer();

    $resetLink = "http://localhost/BookStore/public/user/reset.php?token=" . $token;

    $mail->addAddress($email);
    $mail->Subject = 'Reset Password';
    $mail->Body = "
        <p>Kliko linkun për të ndryshuar password-in:</p>
        <a href='$resetLink'>$resetLink</a>
    ";

    return $mail->send();
}


 // EMAIL FATURË (CHECKOUT)
 
function sendInvoiceEmail($email, $orderNumber)
{
    $mail = getMailer();

    $mail->addAddress($email);
    $mail->Subject = "Invoice Order #$orderNumber";
    $mail->Body    = "
        <p>Faleminderit për blerjen!</p>
        <p>Order Number: <b>$orderNumber</b></p>
    ";

    return $mail->send();
}
