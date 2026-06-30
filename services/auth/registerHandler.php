<?php

require_once __DIR__. '/../../database/databaseConnection.php';
require_once __DIR__ . '/../../database/databaseUserAccess.php';
require_once dirname(__DIR__, 2) . '/services/session/sessionHandler.php';
/** @var mysqli $conn */

$redirectUrl = BASE_URL . '/user/index.php';

function respondAndExit($status, $title, $message, $redirectUrl, $delay = 5)
{
    $palette = [
        'success' => ['bg' => '#d1e7dd', 'text' => '#0f5132', 'label' => 'Success'],
        'error'   => ['bg' => '#f8d7da', 'text' => '#842029', 'label' => 'Error']
    ];

    $colors = $palette[$status] ?? $palette['error'];

    echo "<!DOCTYPE html>\n<html lang='en'>\n<head>\n<meta charset='UTF-8'>\n<meta name='viewport' content='width=device-width, initial-scale=1.0'>\n<title>Registration</title>\n<meta http-equiv='refresh' content='{$delay};url={$redirectUrl}'>\n<link rel='stylesheet' href='../../assets/css/user/registerResponse.css'>\n</head>\n<body>\n<div class='card'>\n<div class='badge badge-{$status}'>{$colors['label']}</div>\n<h1>{$title}</h1>\n<p>{$message}</p>\n<p class='note'>You will be redirected automatically in {$delay} seconds.</p>\n<div class='progress'><div class='progress-bar progress-bar-{$delay}s'></div></div>\n<a class='cta' href='{$redirectUrl}'>Go to Login</a>\n</div>\n</body>\n</html>";
    exit;
}

// GET DATA FROM FORM

$email   = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$confirm  = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

// BACK-END VALIDATION

if ($email === '' || $password === '' || $confirm === '') {
    respondAndExit('error', 'Empty Fields', 'Please fill in all fields.', $redirectUrl);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respondAndExit('error', 'Invalid Email', 'Please enter a valid email address.', $redirectUrl);
}

if (strlen($password) < 6) {
    respondAndExit('error', 'Password Too Short', 'Password must be at least 6 characters long.', $redirectUrl);
}

if ($password !== $confirm) {
    respondAndExit('error', 'Passwords do not match', 'Please check and try again.', $redirectUrl);
}

// CHECK IF USER EXISTS

$existingUser = getUserByEmail($email, $conn);
if ($existingUser) {
    respondAndExit('error', 'Email Already Registered', 'This email is already registered. Try a different one or log in.', $redirectUrl);
}

// HASH PASSWORD

$hash = password_hash($password, PASSWORD_DEFAULT);

// SAVE USER

$created = createUser($email, $hash, $conn);

if (!$created) {
    respondAndExit('error', 'Registration error', 'Registration was not completed. Please try again later.', $redirectUrl);
}

require_once __DIR__ . '/../email/emailService.php';

// gjenero token per verifikim
$selector = bin2hex(random_bytes(16));
$validator = bin2hex(random_bytes(32));
$token = $selector . ':' . $validator;

// ruaj token ne user_tokens
$hashedValidator = password_hash($validator, PASSWORD_DEFAULT);
$expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));

$sql = "INSERT INTO user_tokens (selector, hashed_validator, user_email, expiry) 
    VALUES (?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ssss", $selector, $hashedValidator, $email, $expiry);
mysqli_stmt_execute($stmt);

// dergo email
sendConfirmEmail($email, $token);

respondAndExit(
    'success',
    'Registration completed successfully',
    'Check your email to verify your account and then log in.',
    $redirectUrl,
    5
);
