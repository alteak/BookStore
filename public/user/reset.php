<?php
$token = isset($_GET['token']) ? $_GET['token'] : '';
if ($token === '') {
    die('Invalid token');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password | Online Bookstore</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../../assets/css/user/auth.css">
</head>
<body>

<div class="forgot-container">
    <h2>Reset Password</h2>
    <p>Enter new password for your account.</p>

    <form action="../../services/auth/resetPasswordHandler.php" method="POST">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

        <input type="password" name="password" placeholder="New password" required>
        <input type="password" name="confirm" placeholder="Repeat password" required>

        <button type="submit">Change Password</button>
    </form>

    <a href="index.php">← Back to login</a>
</div>

</body>
</html>
