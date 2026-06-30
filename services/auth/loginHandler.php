<?php

require_once __DIR__ . '/../../database/databaseConnection.php';
require_once __DIR__ . '/loginValidate.php';
require_once __DIR__. '/../session/sessionHandler.php';

/** @var mysqli $conn */


// MERR TE DHENAT NGA FORMA


$email    = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';


// VALIDIM I THJESHTË


if ($email === '' || $password === '') {
    header('Location: ' . BASE_URL . '/user/index.php?error=1');
    exit;
}

// LOGIN

$result = login($email, $password, $conn);

/**
 * login() kthen:
 * - false        → email/password gabim
 * - 'blocked'    → user i bllokuar 30 min
 * - array user   → login OK
 */

if ($result === 'blocked') {
    header('Location: ' . BASE_URL . '/user/index.php?blocked=1');
    exit;
}

if ($result === 'INVALID') {
    header('Location: ' . BASE_URL . '/user/index.php?error=1');
    exit;
}
if (!is_array($result)) {
    header('Location: ' . BASE_URL . '/user/index.php?error=1');
    exit;
}
if (isset($_POST['remember_me'])) {
    // ruaj email për 30 ditë
    setcookie(
        'remember_me',
        $email,
        time() + (30 * 24 * 60 * 60),
        '/'
    );
} else {
    // nëse s'është checked → fshij cookie
    if (isset($_COOKIE['remember_me'])) {
        setcookie('remember_me', '', time() - 3600, '/');
    }
}
// CREATE SESSION

$user = $result;
createSession($user);

// REMEMBER ME

if (isset($_POST['remember_me'])) {
    createRememberToken($user['email'], $conn);
}

// RESET FAILED ATTEMPTS

resetLoginFailures($user['email'], $conn);

// REDIRECT SIPAS ROLIT

if ($user['role'] === 'ADMIN') {
    header('Location: ' . BASE_URL . '/admin/adminDashboard.php');
} else {
    header('Location: ' . BASE_URL . '/user/mainPage/mainPage.php');
}
exit;
