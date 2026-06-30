<?php

require_once dirname(__DIR__, 2) . '/database/databaseConnection.php';
require_once __DIR__ . '/feedbackValidate.php';
require_once dirname(__DIR__, 2) . '/services/session/sessionHandler.php';

requireLogin();

/** @var mysqli $conn */

$userEmail = $_SESSION['user']['email'];
$rating    = $_POST['rating'] ?? '';
$message   = trim($_POST['message'] ?? '');

if (!validateFeedback($rating, $message)) {
    header('Location: ' . BASE_URL . '/user/mainPage/feedback.php?feedback_error=1');
    exit;
}

$sql = "INSERT INTO feedback (user_email, rating, message)
        VALUES (?, ?, ?)";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "sis", $userEmail, $rating, $message);
mysqli_stmt_execute($stmt);

header('Location: ' . BASE_URL . '/user/mainPage/feedback.php?feedback_success=1');
exit;
