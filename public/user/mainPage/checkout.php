<?php
require_once __DIR__ . '/../../../services/session/sessionHandler.php';
requireLogin();
require_once __DIR__ . '/../../../database/databaseConnection.php';
require_once __DIR__ . '/../../../services/Frontend/checkoutService.php';
require_once __DIR__ . '/../../../config.php';

// mund ose mund te mos kete kod discounti, user duhet te kete me patjeter
$userEmail = $_SESSION['user']['email'] ?? null;
$discountCode = $_GET['discount'] ?? '';
$message = '';
$messageType = '';

// Handle payment submission
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_intent_id'])){
    $paymentIntentId = $_POST['payment_intent_id'] ?? null;
    if(!$paymentIntentId) {
        $message = 'Error: Payment ID is missing.';
        $messageType = 'error';
    } else {
        // te dhenat e checkout i marr nga forma embedded e stripe
        $shippingData = [
            'name' => $_POST['billing_name'] ?? '',
            'email' => $_POST['billing_email'] ?? '',
            'phone' => $_POST['billing_phone'] ?? '',
            'address' => $_POST['shipping_address'] ?? '',
            'city' => $_POST['shipping_city'] ?? '',
            'postal' => $_POST['shipping_postal'] ?? ''
        ];
        
        $result = checkout($userEmail, $conn, $discountCode, $paymentIntentId, $shippingData);
        if($result['success']){
            $_SESSION['order_details'] = $result['order_details'];
            header('Location: orderConfirmation.php');
            exit;
        } else {
            $message = $result['message'];
            $messageType = 'error';
        }
    }
}

// Get cart items
$cartItems = getCart($userEmail, $conn);
if($cartItems === false){
    $message = 'One or more items in your cart are no longer in stock. Please update your cart.';
    $messageType = 'error';}

// Calculate totals
$subtotal = calculateSubtotal($cartItems); // Initial subtotal without discount

$shipping = 0; // Free shipping ? idk\
$discountAmount = getDiscountAmount($discountCode, $subtotal, $conn);
$subtotal -= $discountAmount;

$total = $subtotal + $shipping;

function formatEuro($price){
    return number_format($price, 0, ',', '.');
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Checkout | Online Bookstore</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../assets/css/user/cart.css">
    <link rel="stylesheet" href="../../../assets/css/user/checkout.css">
</head>

<body>

<!-- Header -->
<div class="checkout-header">
    <div class="header-content">
        <a href="cart.php" class="back-link">
            <i class="bi bi-arrow-left"></i> Back to Cart
        </a>
    </div>
</div>

<!-- Checkout Content -->
<div class="checkout-content">
    <div class="order-summary">
        <div class="summary-section">
            <h3>Order Summary</h3>
            <?php foreach($cartItems as $item): ?>
                <div class="order-item">
                    <div class="order-item-details">
                        <div class="order-item-title"><?= htmlspecialchars($item['title']) ?></div>
                        <div class="order-item-qty">Quantity: <?= $item['quantity'] ?></div>
                    </div>
                    <div class="order-item-price"><?= formatEuro($item['total_price']) ?> €</div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="summary-row">
            <span>Subtotal:</span>
            <span><?= formatEuro($subtotal) ?> €</span>
        </div>
        <div class="summary-row">
            <span>Shipping:</span>
            <span>Free</span>
        </div>
        <div class="summary-row">
            <span>Discount:</span>
            <span class="discount-amount"><?= $discountAmount > 0 ? '-' . formatEuro($discountAmount) : '0' ?> €</span>
        </div>

        <div class="summary-row total">
            <span>Total:</span>
            <span><?= formatEuro($total) ?> €</span>
        </div>
    </div>

    <div class="payment-section">
        <h2>Payment Details</h2>
        
        <?php if($discountCode): ?>
            <p class="discount-badge">
                <i class="bi bi-tag"></i> Discount code: <?= htmlspecialchars($discountCode) ?>
            </p>
        <?php endif; ?>
        
        <?php if($message): ?>
            <div class="message-box <?= $messageType ?>">
                <i class="bi bi-<?= $messageType === 'success' ? 'check-circle' : 'x-circle' ?>"></i> <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <form id="stripe-form" method="POST">
            <input type="hidden" id="payment_intent_id" name="payment_intent_id" value="">
            
            <!-- Forma e perdoruesit (jo dhe aq te rendesishme), i perdorim per te dhenat e porosise -->
            <div class="form-section">
                <h3><i class="bi bi-person"></i> Billing Information</h3>
                
                <div class="form-field">
                    <label for="cardholder-name">Full Name</label>
                    <input type="text" id="cardholder-name" name="billing_name" placeholder="First and Last Name" required>
                </div>
                
                <div class="form-grid-2">
                    <div class="form-field">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="billing_email" placeholder="example@email.com" required>
                    </div>
                    <div class="form-field">
                        <label for="phone">Phone</label>
                        <input type="tel" id="phone" name="billing_phone" placeholder="+355 XX XXX XXXX" required>
                    </div>
                </div>
                
                <div class="form-field">
                    <label for="address">Shipping Address</label>
                    <input type="text" id="address" name="shipping_address" placeholder="Street address" required>
                </div>
                
                <div class="form-grid-2">
                    <div class="form-field">
                        <label for="city">City</label>
                        <input type="text" id="city" name="shipping_city" placeholder="City" required>
                    </div>
                    <div class="form-field">
                        <label for="postal">Postal Code</label>
                        <input type="text" id="postal" name="shipping_postal" placeholder="Postal Code">
                    </div>
                </div>
            </div>
            
            <!-- Te dhenat e kartes, ruaji me kujdes (per test ne sandbox perdoret nr 4242 4242 4242 4242, me kusht data e skandences te jete ne te ardhmen, cvc random) -->
            <div class="form-section">
                <h3><i class="bi bi-credit-card"></i> Card Details</h3>
                
                <div class="form-field">
                    <label for="card-element">Card Number, Expiration Date and CVC</label>
                    <div id="card-element"></div>
                    <div id="card-errors"></div>
                </div>
                
                <div class="security-badge">
                    <p><i class="bi bi-shield-check"></i> Your payment is secure and encrypted with Stripe</p>
                </div>
            </div>
            
            <button type="submit" class="btn-pay full-width" id="submit-button">
                <i class="bi bi-lock"></i> Pay <?= formatEuro($total) ?> €
            </button>
        </form>
    </div>
    
        <!-- JS i gatshem per stripe -->
    <script src="https://js.stripe.com/v3/"></script>
    <script>
        const stripe = Stripe('<?= htmlspecialchars(STRIPE_PUBLIC_KEY) ?>');
        const elements = stripe.elements();
        const cardElement = elements.create('card');
        cardElement.mount('#card-element');
        
        const cardErrors = document.getElementById('card-errors');
        
        cardElement.on('change', function(event) {
            if (event.error) {
                cardErrors.textContent = event.error.message;
            } else {
                cardErrors.textContent = '';
            }
        });
        
        const form = document.getElementById('stripe-form');
        form.addEventListener('submit', async function(event) {
            event.preventDefault();
            
            const submitButton = document.getElementById('submit-button');
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="bi bi-hourglass-split"></i> Processing...';
            
            // error handling perseri, nevojitet kujdes gjate komunikimit me api te jashtme
            try {

                // Step 1: Create Payment Intent
                const response = await fetch('stripeAPI.php?action=create_intent', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'amount=<?= $total ?>&currency=usd&description=BookStore Order'
                });
                
                const data = await response.json();
                
                if (!data.success) {
                    cardErrors.textContent = 'Error creating payment: ' + data.error;
                    submitButton.disabled = false;
                    submitButton.innerHTML = '<i class="bi bi-lock"></i> Pay <?= formatEuro($total) ?> €';
                    return;
                }
                
                // Step 2: Confirm payment with Stripe
                const confirmResult = await stripe.confirmCardPayment(data.client_secret, {
                    payment_method: {
                        card: cardElement,
                        billing_details: {
                            name: document.getElementById('cardholder-name').value
                        }
                    }
                });
                
                if (confirmResult.error) {
                    cardErrors.textContent = confirmResult.error.message;
                    submitButton.disabled = false;
                    submitButton.innerHTML = '<i class="bi bi-lock"></i> Pay <?= formatEuro($total) ?> €';
                } else if (confirmResult.paymentIntent.status === 'succeeded') {

                    // Payment successful
                    document.getElementById('payment_intent_id').value = confirmResult.paymentIntent.id;
                    form.submit();
                } else {
                    cardErrors.textContent = 'Payment failed. Status: ' + confirmResult.paymentIntent.status;
                    submitButton.disabled = false;
                    submitButton.innerHTML = '<i class="bi bi-lock"></i> Pay <?= formatEuro($total) ?> €';
                }
            } catch (error) {
                cardErrors.textContent = 'Error: ' + error.message;
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="bi bi-lock"></i> Pay <?= formatEuro($total) ?> €';
            }
        });
    </script>
</div>

</body>
</html>
