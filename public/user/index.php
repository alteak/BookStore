<?php

require_once dirname(__DIR__, 2) . '/database/databaseConnection.php';
require_once dirname(__DIR__, 2) . '/services/session/sessionHandler.php';
require_once dirname(__DIR__, 2) . '/services/auth/remember.php';
/** @var mysqli $conn */
// nëse ekziston cookie remember_me → auto login
loginFromRememberToken($conn);

// nëse u krijua session → mos shfaq login page
if (isset($_SESSION['user'])) {
    if ($_SESSION['user']['role'] === 'ADMIN') {
        header('Location: admin.php');
    } else {
        header('Location: mainPage/mainPage.php');
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login / Signup</title>
    <link rel="stylesheet" href="../../assets/css/user/index.css">
    <script src="../../assets/js/user/index.js" defer></script>
</head>

<body>
<div class="page-container">
    <div class="wrapper">

    <div class="title-text">
        <div class="title login">Online Bookstore</div>
        <div class="title signup"></div>
    </div>

    <div class="form-container">

        <div class="slide-controls">
            <input type="radio" name="slide" id="login" checked>
            <input type="radio" name="slide" id="signup">
            <label for="login" class="slide login">Log in</label>
            <label for="signup" class="slide signup">Sign up</label>
            <div class="slider-tab"></div>
        </div>

        <div class="form-inner">

            <!-- LOGIN -->
            <form action="../../services/auth/loginHandler.php"
                  method="POST"
                  autocomplete="off"
                  class="login">

                <div class="field">
                    <input type="email" name="email" required>
                </div>

                <div class="field password-field">
                    <input type="password"
                           name="password"
                           id="loginPassword"
                           autocomplete="new-password"
                           required>
                    <span class="eye" data-target="loginPassword">👁️</span>
                </div>

                <div class="field btn">
                    <div class="btn-layer"></div>
                    <input type="submit" value="Log in">
                </div>
                <div class="field-row">
                    <div class="forgot-link">
                        <a href="forgot.php">Forgot password?</a>
                    </div>
                    <div class="remember-me">
                        <label>
                            <input type="checkbox" name="remember">
                            Remember me
                        </label>
                    </div>
                </div>
            </form>

            <!-- SIGNUP -->
            <form action="../../services/auth/registerHandler.php" method="POST" class="signup">
                <div class="field">
                    <input type="text" name="email" placeholder="Email Address" required>
                </div>

                <div class="field password-field">
                    <input type="password" name="password" id="signupPassword" placeholder="Password" required>
                    <span class="eye" data-target="signupPassword">👁️</span>
                </div>

                <div class="field password-field">
                    <input type="password" name="confirm_password" id="signupConfirm" placeholder="Confirm Password" required>
                    <span class="eye" data-target="signupConfirm">👁️</span>
                </div>

                <div class="field btn">
                    <div class="btn-layer"></div>
                    <input type="submit" value="Sign up">
                </div>
            </form>

        </div>
    </div>

    <div class="notices">
        <?php if (isset($_GET['verify'])): ?>
            <?php if ($_GET['verify'] === 'success'): ?>
                <p style="color:green;">Email verified successfully. You can now log in.</p>
            <?php elseif ($_GET['verify'] === 'invalid'): ?>
                <p style="color:red;">Invalid or expired verification link.</p>
            <?php elseif ($_GET['verify'] === 'already'): ?>
                <p style="color:blue;">Account is already verified.</p>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (isset($_GET['timeout'])): ?>
            <p style="color:red;">Session expired. Please log in again.</p>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <p style="color:red;">Incorrect email or password</p>
        <?php endif; ?>

        <?php if (isset($_GET['blocked'])): ?>
            <p style="color:red;">Account is blocked for 30 minutes due to unsuccessful attempts.</p>
        <?php endif; ?>
    </div>
</div>
</div>

<div id="popup" class="popup hidden">
    <span id="popup-message"></span>
</div>

</body>
</html>
