<?php

function generateToken($length = 32)
{
    return bin2hex(random_bytes($length));
}

function createRememberToken($userEmail, mysqli $conn)
{
    $selector  = generateToken(12);
    $validator = generateToken(32);

    $hashedValidator = password_hash($validator, PASSWORD_DEFAULT);
    $expiry = date('Y-m-d H:i:s', strtotime('+30 days'));

    // fshij token-at e vjetër
    $stmt = $conn->prepare("DELETE FROM user_tokens WHERE user_email = ?");
    $stmt->bind_param("s", $userEmail);
    $stmt->execute();

    // ruaj token-in e ri
    $stmt = $conn->prepare("
        INSERT INTO user_tokens (selector, hashed_validator, user_email, expiry)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("ssss", $selector, $hashedValidator, $userEmail, $expiry);
    $stmt->execute();

    // cookie
    setcookie(
        'remember_me',
        $selector . ':' . $validator,
        time() + (30 * 24 * 60 * 60),
        '/',
        '',
        false,
        true
    );
}

function loginFromRememberToken(mysqli $conn)
{
    // Check if cookie exists
    if (!isset($_COOKIE['remember_me'])) {
        return false;
    }

    // Parse cookie value (selector:validator)
    $cookie = $_COOKIE['remember_me'];
    $parts = explode(':', $cookie);
    
    if (count($parts) !== 2) {
        // Invalid format - clear cookie
        clearRememberCookie();
        return false;
    }

    $selector = $parts[0];
    $validator = $parts[1];

    // Find token in database
    $stmt = $conn->prepare("
        SELECT ut.hashed_validator, ut.user_email, ut.expiry, u.role
        FROM user_tokens ut
        JOIN users u ON u.email = ut.user_email
        WHERE ut.selector = ?
        LIMIT 1
    ");
    $stmt->bind_param("s", $selector);
    $stmt->execute();
    $result = $stmt->get_result();
    $token = $result->fetch_assoc();

    if (!$token) {
        // Token not found - clear cookie
        clearRememberCookie();
        return false;
    }

    // Check if token is expired
    if (strtotime($token['expiry']) < time()) {
        // Expired - delete from DB and clear cookie
        deleteToken($conn, $selector);
        clearRememberCookie();
        return false;
    }

    // Verify validator against hashed value
    if (!password_verify($validator, $token['hashed_validator'])) {
        // Invalid validator - possible theft, delete all tokens for user
        $stmt = $conn->prepare("DELETE FROM user_tokens WHERE user_email = ?");
        $stmt->bind_param("s", $token['user_email']);
        $stmt->execute();
        clearRememberCookie();
        return false;
    }

    // Token is valid - create session
    $user = [
        'email' => $token['user_email'],
        'role'  => $token['role']
    ];
    
    createSession($user);

    // Rotate token for security (issue new token, delete old)
    deleteToken($conn, $selector);
    createRememberToken($token['user_email'], $conn);

    return true;
}

function clearRememberCookie()
{
    setcookie('remember_me', '', time() - 3600, '/');
}

function deleteToken(mysqli $conn, string $selector)
{
    $stmt = $conn->prepare("DELETE FROM user_tokens WHERE selector = ?");
    $stmt->bind_param("s", $selector);
    $stmt->execute();
}
