<?php
require_once __DIR__ . '/../../database/databaseUserAccess.php';

/*
KONVENTË:
login() kthen:
- false -> email / password gabim
- 'blocked' -> user i bllokuar 30 min
- array -> login OK
*/


function isActiveUser($user){
    return isset($user['status']) && (int)$user['status'] === 1;
}

function authenticatePass($user, $password){
    return password_verify($password, $user['pass_hash']);
}

// LOGIN BLOCK LOGIC
function isLoginBlocked($user){
    if ((int)$user['failed_attempts'] < 7) {
        return false;
    }
    if (empty($user['last_failed_login'])) {
        return false;
    }

    $lastFail = strtotime($user['last_failed_login']);
    $now = time();

    // 30 minuta = 1800 sekonda
    if (($now - $lastFail) < 1800) {
        return true;
    }

    return false;
}

// FAILED ATTEMPTS (EMAIL-BASED)
function increaseLoginFailures($email, $conn){
    $sql = "UPDATE users 
            SET failed_attempts = failed_attempts + 1, last_failed_login = NOW() 
            WHERE email = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
}

function resetLoginFailures($email, $conn){
    $sql = "UPDATE users 
            SET failed_attempts = 0, last_failed_login = NULL 
            WHERE email = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
}

// LOGIN MAIN FUNCTION
function login($email, $password, $conn){
    if (!validateLoginCredentials($email, $password)) {
        return false;


}

    $user = getUserByEmail($email, $conn);

    if (!$user) {
        logLoginAttempt($email, 'FAIL', $conn);
        return 'invalid';
    }

    if (!isActiveUser($user)) {
        return false;
    }

    // kontrollo block
    if (isLoginBlocked($user)) {
        logLoginAttempt($email, 'BLOCKED', $conn);
        return 'blocked';
    }

    if (!authenticatePass($user, $password)) {
        increaseLoginFailures($email, $conn);
        logLoginAttempt($email, 'FAIL', $conn);
        return 'invalid';
    }

    // login OK
    resetLoginFailures($email, $conn);
    logLoginAttempt($email, 'SUCCESS', $conn);
    return $user;
}

// REGISTER FAILED LOGIN (EMAIL-BASED)
function registerFailedLogin($email, $conn){
    $sql = "UPDATE users 
            SET failed_attempts = failed_attempts + 1, last_failed_login = NOW() 
            WHERE email = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
}

function validateLoginCredentials($email, $password)
{
    // EMAIL
    if (empty($email)) {
        return false;
    }

    // filter bazë
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    // format user@domain.com (siguri shtesë)
    if (!preg_match('/^[\w\.-]+@[\w-]+\.[a-zA-Z]{2,}$/', $email)) {
        return false;
    }

    // PASSWORD
    if (empty($password)) {
        return false;
    }

    if (strlen($password) < 6) {
        return false;
    }

    // 1 shkronjë e madhe
    if (!preg_match('/[A-Z]/', $password)) {
        return false;
    }

    // 1 shkronjë e vogël
    if (!preg_match('/[a-z]/', $password)) {
        return false;
    }

    // 1 numër
    if (!preg_match('/[0-9]/', $password)) {
        return false;
    }

    // 1 simbol
    if (!preg_match('/[\W_]/', $password)) {
        return false;
    }

    return true;
}
