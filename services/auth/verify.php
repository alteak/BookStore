<?php
require_once __DIR__ . '/../../database/databaseConnection.php';

if (!isset($_GET['token']) || empty($_GET['token'])) {
    header("Location: /BookStore/public/user/index.php?verify=invalid");
    exit;
}

$token = $_GET['token'];

// split token into selector:validator
$parts = explode(':', $token);
if (count($parts) !== 2) {
    header("Location: /BookStore/public/user/index.php?verify=invalid");
    exit;
}

[$selector, $validator] = $parts;

// kontrollo tokenin ne user_tokens
$sql = "SELECT hashed_validator, user_email, expiry FROM user_tokens 
    WHERE selector = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $selector);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$tokenData = mysqli_fetch_assoc($result);

if (!$tokenData) {
    header("Location: /BookStore/public/user/index.php?verify=invalid");
    exit;
}

// check expiry
if (strtotime($tokenData['expiry']) < time()) {
    // fshi tokenin e skaduar
    $deleteSql = "DELETE FROM user_tokens WHERE selector = ?";
    $deleteStmt = mysqli_prepare($conn, $deleteSql);
    mysqli_stmt_bind_param($deleteStmt, "s", $selector);
    mysqli_stmt_execute($deleteStmt);
    
    header("Location: /BookStore/public/user/index.php?verify=invalid");
    exit;
}

// verifiko validator
if (!password_verify($validator, $tokenData['hashed_validator'])) {
    header("Location: /BookStore/public/user/index.php?verify=invalid");
    exit;
}

// merr user-in nga email
$userEmail = $tokenData['user_email'];
$userSql = "SELECT email, status FROM users WHERE email = ?";
$userStmt = mysqli_prepare($conn, $userSql);
mysqli_stmt_bind_param($userStmt, "s", $userEmail);
$userStmt->execute();
$userResult = $userStmt->get_result();
$user = $userResult->fetch_assoc();

if (!$user) {
    header("Location: /BookStore/public/user/index.php?verify=invalid");
    exit;
}

// nëse është tashmë aktiv
if ((int)$user['status'] === 1) {
    header("Location: /BookStore/public/user/index.php?verify=already");
    exit;
}

// aktivizo user-in
$update = "UPDATE users 
           SET status = 1
           WHERE email = ?";
$stmt = mysqli_prepare($conn, $update);
mysqli_stmt_bind_param($stmt, "s", $user['email']);
mysqli_stmt_execute($stmt);

// fshi tokenin e perdorur
$deleteSql = "DELETE FROM user_tokens WHERE selector = ?";
$deleteStmt = mysqli_prepare($conn, $deleteSql);
mysqli_stmt_bind_param($deleteStmt, "s", $selector);
mysqli_stmt_execute($deleteStmt);

// redirect te login
header("Location: /BookStore/public/user/index.php?verify=success");
exit;