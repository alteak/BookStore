<?php

require_once dirname(__DIR__, 2) . '/database/databaseConnection.php';
require_once dirname(__DIR__, 2) . '/services/email/emailService.php';
require_once dirname(__DIR__, 2) . '/services/session/sessionHandler.php';

/** @var mysqli $conn */

$email = $_POST['email'] ?? '';

// VALIDIM
if (!validateForgotPassword($email)) {
    header('Location: ' . BASE_URL . '/forgot.php?error=invalid_email');
    exit;
}

// KONTROLL NËSE USER EKZISTON
$user = getUserByEmail($email, $conn);

// ❗ Mos zbulo nëse ekziston apo jo (security best practice)
if ($user) {
    $token = bin2hex(random_bytes(32));
    saveResetToken($user['id'], $token, $conn);
    sendForgotPasswordEmail($email, $token);
}

// gjithmonë i njëjti mesazh
header('Location: ' . BASE_URL . '/forgot.php?success=1');
exit;
