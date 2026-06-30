<?php

function getUserByEmail($email, $conn)
{
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

function createUser($email, $hash, $conn)
{
    $sql = "INSERT INTO users (email, pass_hash, role, status)
            VALUES (?, ?, 'USER', 0)";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $email, $hash);

    return mysqli_stmt_execute($stmt);
}
function logLoginAttempt($email, $status, $conn)
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

    $sql = "INSERT INTO login_logs (email, status, ip_address)
            VALUES (?, ?, ?)";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sss", $email, $status, $ip);
    mysqli_stmt_execute($stmt);
}
