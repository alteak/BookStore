<?php
require_once __DIR__ . '/../../../services/session/sessionHandler.php';
requireLogin();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cart | Online Bookstore</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../assets/css/user/cart.css">
</head>

<body>

<!-- Header -->
<div class="header">
    <div class="header-content">
        <a href="mainPage.php" class="back-link">
            <i class="bi bi-arrow-left"></i> Back
        </a>
        <h1 class="page-title">My Cart</h1>
    </div>
</div>

<!-- Main Container -->
<div class="container">

    <?php if(isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
            <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($_SESSION['error_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <div id="loadingState" class="loading">
        <i class="bi bi-hourglass-split"></i>
        <p>Loading...</p>
    </div>

    <div id="emptyState" class="empty-state d-none">
        <i class="bi bi-bag-x"></i>
        <h3>Your cart is empty</h3>
        <p>Add books to cart to continue with purchase</p>
        <a href="mainPage.php" class="btn-explore">
            <i class="bi bi-compass"></i> Explore Books
        </a>
    </div>

    <div id="cartContent" class="d-none">
        <div class="cart-items" id="cartItems"></div>
        
        <div class="cart-summary">
            <h3>Summary</h3>
            <div class="summary-row">
                <span>Subtotal:</span>
                <span id="subtotal">0 €</span>
            </div>
            <div class="summary-row">
                <span>Shipping:</span>
                <span id="shipping">Free</span>
            </div>

            <div class="summary-row">
                <span>Discount:</span>
                <span id="discountAmount" class="discount-amount">0 €</span>
            </div>

            <div class="discount-section">
                <label for="discountCode" class="discount-label">Discount Code</label>
                <div class="discount-input-group">
                    <input type="text" id="discountCode" placeholder="Enter code" maxlength="50">
                    <button type="button" id="applyDiscountBtn" class="apply-discount-btn">Apply</button>
                </div>
                <div id="discountMessage" class="discount-message"></div>
            </div>

            <div class="summary-row total">
                <span>Total:</span>
                <span id="total">0 €</span>
            </div>
            <a href="checkout.php" id="checkoutBtn" class="btn-checkout">
                <i class="bi bi-credit-card"></i> Continue to Payment
            </a>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../../assets/js/user/cart.js"></script>
<script>
const checkoutBtn = document.getElementById('checkoutBtn');
checkoutBtn.addEventListener('click', function() {
    if (window.appliedDiscount && window.appliedDiscount.code) {
        checkoutBtn.href = 'checkout.php?discount=' + encodeURIComponent(window.appliedDiscount.code);
    } else {
        checkoutBtn.href = 'checkout.php';
    }
});
</script>

</body>
</html>
