<?php
// services/session/sessionHandler.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// KONFIGURIME
define('SESSION_TIMEOUT', 900); // 15 minuta
define('BASE_URL', '/BookStore/public');

// CREATE SESSION
function createSession(array $user)
{
    $_SESSION['user'] = [
        'email' => $user['email'],
        'role'  => $user['role']
    ];
    $_SESSION['last_activity'] = time();
}

// REQUIRE LOGIN (MAIN GUARD)
function requireLogin()
{
    if (!isset($_SESSION['user'])) {
        redirectToLogin();
    }

    // Auto logout on timeout
    if (isset($_SESSION['last_activity']) &&
        (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {

        session_unset();
        session_destroy();
        redirectToLogin(true);
    }

    $_SESSION['last_activity'] = time();
}

// LOGOUT
function logout()
{
    session_unset();
    session_destroy();
    redirectToLogin();
}

// REDIRECT HELPER
function redirectToLogin($timeout = false)
{
    $url = '/BookStore/public/user/index.php';
    if ($timeout) {
        $url .= '?timeout=1';
    }
    header("Location: $url");
    exit;
}
