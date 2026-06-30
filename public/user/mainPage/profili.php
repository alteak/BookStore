<?php
require_once __DIR__ . '/../../../services/session/sessionHandler.php';
requireLogin();
?>
<!doctype html>
<html lang="sq">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Account | Online Bookstore</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../assets/css/user/profili.css">
    <link rel="stylesheet" href="../../../assets/css/user/chatbox.css">
    <script src="../../../assets/js/user/chatbox.js" defer></script>
</head>

<body>

<div class="header">
    <div class="header-content">
        <a href="./mainPage.php" class="back-link">
            <i class="bi bi-arrow-left"></i> Back
        </a>
        <h1 class="page-title">My Account</h1>
    </div>
</div>

<div class="container">
    <div class="profile-card">

        <h2 class="profile-name"><?= htmlspecialchars($_SESSION['user']['email'] ?? 'User') ?></h2>

        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Wishlist</div>
                <div class="info-value" id="wishlistCount">0 Books</div>
            </div>
            <div class="info-item">
                <div class="info-label">Cart</div>
                <div class="info-value" id="cartCount">0 Items</div>
            </div>
        </div>

        <div class="btn-group">
            <a href="wishlist.php" class="btn btn-primary">
                <i class="bi bi-heart"></i> View Wishlist
            </a>

            <a href="cart.php" class="btn btn-secondary">
                <i class="bi bi-bag"></i> View Cart
            </a>

            <button type="button" class="btn btn-info" id="inboxBtn">
                <i class="bi bi-chat-dots"></i> Inbox <span class="badge bg-danger" id="unreadCount" style="display: none;">0</span>
            </button>

            <!-- LOGOUT -->
            <a href="../logout.php" class="btn btn-danger">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>

    </div>
</div>

<!-- Chatbox Modal -->
<div class="chatbox-overlay" id="chatboxOverlay">
    <div class="chatbox">
        <div class="chatbox-header">
            <h5><i class="bi bi-chat-dots"></i> Messages</h5>
            <button class="chatbox-close" id="chatboxClose">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="chatbox-messages" id="chatboxMessages">
            <div class="loading-messages">
                <i class="bi bi-hourglass-split"></i> Loading messages...
            </div>
        </div>
        <div class="chatbox-input">
            <div class="input-group">
                <input type="text" class="form-control" id="messageInput" placeholder="Type your message..." maxlength="500">
                <button class="btn btn-primary" type="button" id="sendBtn">
                    <i class="bi bi-send"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    async function loadStats(){
        try{
            // Load wishlist count from database
            const wishlistRes = await fetch('../../../services/frontend/wishlistService.php');
            const wishlistData = await wishlistRes.json();
            const wishlistCount = wishlistData.success && wishlistData.data ? wishlistData.data.length : 0;
            document.getElementById('wishlistCount').textContent = wishlistCount + ' Books';
            
            // Load cart count from database
            const cartRes = await fetch('../../../services/frontend/cartService.php');
            const cartData = await cartRes.json();
            const cartTotal = cartData.success && cartData.data ? 
                cartData.data.reduce((sum, item) => sum + (item.qty || 1), 0) : 0;
            document.getElementById('cartCount').textContent = cartTotal + ' Items';
        }catch(e){
            console.error(e);
            document.getElementById('wishlistCount').textContent = '0 Books';
            document.getElementById('cartCount').textContent = '0 Items';
        }
    }

    loadStats();
</script>

</body>
</html>
