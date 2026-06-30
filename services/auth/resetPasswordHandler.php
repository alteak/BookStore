<?php
require_once __DIR__. '/../../database/databaseConnection.php';
require_once dirname(__DIR__, 2) . '/services/session/sessionHandler.php';
/** @var mysqli $conn */
$token   = isset($_POST['token']) ? $_POST['token'] : '';
$pass    = isset($_POST['password']) ? $_POST['password'] : '';
$confirm = isset($_POST['confirm']) ? $_POST['confirm'] : '';

if ($token === '' || $pass === '' || $confirm === '') {
    die('Te dhena te pavlefshme');
}

if ($pass !== $confirm) {
    die('Passwords do not match');
}

if (strlen($pass) < 6) {
    die('Password shume i shkurter');
}

// kontrollo token
$sql = "SELECT email FROM password_resets
        WHERE token = ? AND expires_at > NOW()
        LIMIT 1";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $token);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$row = mysqli_fetch_assoc($res);
if (!$row) {
    die('Token i pavlefshem ose i skaduar');
}

$email = $row['email'];
$hash  = password_hash($pass, PASSWORD_DEFAULT);

// update password
$upd = "UPDATE users SET pass_hash = ? WHERE email = ?";
$stmt = mysqli_prepare($conn, $upd);
mysqli_stmt_bind_param($stmt, "ss", $hash, $email);
mysqli_stmt_execute($stmt);

// fshi token
$del = "DELETE FROM password_resets WHERE token = ?";
$stmt = mysqli_prepare($conn, $del);
mysqli_stmt_bind_param($stmt, "s", $token);
mysqli_stmt_execute($stmt);

// redirect final
header('Location: ' . BASE_URL . '/user/index.php?reset=success');
exit;
