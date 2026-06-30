<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="../../assets/css/user/auth.css">
</head>
<body>

<div class="forgot-container">
    <h2>Forgot Password?</h2>
    <p>Enter your email address below and we'll send you a link to reset your password.</p>

    <form action="../../services/auth/forgotHandler.php" method="POST">
        <input type="email" name="email" placeholder="Email address" required>
        <button type="submit">Send Reset Link</button>
    </form>

    <a href="index.php">Back to Login</a>
</div>

</body>
</html>
