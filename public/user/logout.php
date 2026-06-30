<?php

require_once dirname(__DIR__, 2) . '/database/databaseConnection.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Delete remember_me token from database (if user is logged in)
if (isset($_SESSION['user']['email'])) {
    $stmt = $conn->prepare("DELETE FROM user_tokens WHERE user_email = ?");
    $stmt->bind_param("s", $_SESSION['user']['email']);
    $stmt->execute();
}

// Clear remember_me cookie
if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', time() - 3600, '/');
}

// Clear session data
$_SESSION = [];

// Destroy session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 3600,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Redirect to login page
header("Location: index.php");
exit;