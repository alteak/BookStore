<?php
require_once __DIR__ . '/../../../services/session/sessionHandler.php';
requireLogin();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Wishlist | Online Bookstore</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../assets/css/user/wishlist.css">
</head>

<body>

<!-- Header -->
<div class="header">
    <div class="header-content">
        <a href="mainPage.php" class="back-link">
            <i class="bi bi-arrow-left"></i> Back
        </a>
        <h1 class="page-title">Wishlist</h1>
        <a href="cart.php" class="cart-button">
            <i class="bi bi-bag"></i> Cart
        </a>
    </div>
</div>

<!-- Main Container -->
<div class="container">

    <div id="loadingState" class="loading">
        <i class="bi bi-hourglass-split"></i>
        <p>Loading...</p>
    </div>

    <div id="emptyState" class="empty-state d-none">
        <i class="bi bi-heart"></i>
        <h3>Your wishlist is empty</h3>
        <p>Add books you like to read later</p>
        <a href="mainPage.php" class="btn-explore">
            <i class="bi bi-compass"></i> Explore Books
        </a>
    </div>

    <div id="booksGrid" class="books-grid d-none"></div>
</div>

<script src="../../../assets/js/user/wishlist.js"></script>

</body>
</html>
