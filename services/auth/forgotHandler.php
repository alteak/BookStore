<?php

require_once __DIR__ . '/../email/emailService.php';
require_once __DIR__ . '/../../database/databaseConnection.php';
require_once __DIR__ . '/../../database/databaseUserAccess.php';
require_once dirname(__DIR__, 2) . '/services/session/sessionHandler.php';

/** @var mysqli $conn */

$email = isset($_POST['email']) ? trim($_POST['email']) : '';

if ($email === '') {
    header('Location: ' . BASE_URL . '/user/forgot.php?error=1');
    exit;
}

$user = getUserByEmail($email, $conn);

// Mos zbulo nese user ekziston apo jo
if ($user) {

    $token   = bin2hex(openssl_random_pseudo_bytes(32));
    $expires = date('Y-m-d H:i:s', time() + 3600);

    $sql = "INSERT INTO password_resets (email, token, expires_at)
            VALUES (?, ?, ?)";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sss", $email, $token, $expires);
    mysqli_stmt_execute($stmt);

    sendForgotPasswordEmail($email, $token);
}

// gjithmonë redirect
header('Location: ' . BASE_URL . '/user/forgot.php?sent=1');
exit;
