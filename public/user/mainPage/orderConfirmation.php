<?php
// e gjeneruar me Copilot thjesht si redirect page pas pagesës së suksesshme
// ska shume rendesi si kod ne aspekt te funksionalitetit

require_once __DIR__ . '/../../../services/session/sessionHandler.php';
requireLogin();

$order = $_SESSION['order_details'] ?? null;

if (!$order) {
    header('Location: mainPage.php');
    exit;
}

// Clear the session order details after displaying
unset($_SESSION['order_details']);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Order Confirmation | Online Bookstore</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../assets/css/user/orderConfirmation.css">
</head>
<body>

<div class="confirmation-container">
    <div class="success-icon">
        <i class="bi bi-check-circle-fill"></i>
    </div>

    <h1 class="confirmation-title">Payment Successful!</h1>
    <p class="confirmation-text">Your order has been processed and will be delivered soon.</p>

    <div class="order-number">
        <div class="order-number-label">Order Number</div>
        <div class="order-number-value"><?php echo htmlspecialchars($order['order_number']); ?></div>
    </div>

    <p class="confirmation-text">You will receive an email with your order details.</p>

    <div class="confirmation-buttons">
        <a href="mainPage.php" class="btn-secondary-custom">
            <i class="bi bi-house"></i> Keep Shopping
        </a>
    </div>
</div>

</body>
</html>
