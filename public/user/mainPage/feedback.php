<?php
require_once dirname(__DIR__, 3) . '/database/databaseConnection.php';
require_once dirname(__DIR__, 3) . '/services/session/sessionHandler.php';

requireLogin();

$userEmail = $_SESSION['user']['email'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback - Online Bookstore</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Merriweather+Sans:400,700" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css?family=Merriweather:400,300,700" rel="stylesheet" />
    
    <!-- Core CSS -->
    <link rel="stylesheet" href="../../../assets/css/user/mainPageTemplate.css">
    <link rel="stylesheet" href="../../../assets/css/user/feedback.css">
</head>
<body>

<!-- ===== NAVBAR ===== -->
<nav class="navbar fixed-top navbar-dark py-3" id="mainNav">
    <div class="container px-4 px-lg-5 d-flex align-items-center">
        <a class="navbar-brand fw-bold me-3" href="mainPage.php">Online Bookstore</a>
        
        <div class="d-flex align-items-center gap-3 ms-auto">
            <ul class="nav nav-icons m-0">
                <li class="nav-item">
                    <a class="nav-link icon-link" href="profili.php">
                        <span class="icon-circle"><i class="bi bi-person"></i></span>
                        <span class="icon-text">MY ACCOUNT</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link icon-link" href="wishlist.php">
                        <span class="icon-circle"><i class="bi bi-heart"></i></span>
                        <span class="icon-text">WISHLIST</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link icon-link" href="cart.php">
                        <span class="icon-circle"><i class="bi bi-bag"></i></span>
                        <span class="icon-text">SHPORTA</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="feedback-container">
    <div class="feedback-header">
        <h1><i class="bi bi-chat-heart"></i> Share Your Feedback</h1>
        <p>We'd love to hear about your experience with our bookstore!</p>
    </div>

    <?php if (isset($_GET['feedback_success'])): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill"></i>
            <span>Thank you! Your feedback has been submitted successfully.</span>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['feedback_error'])): ?>
        <div class="alert alert-error">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span>Please provide a valid rating and message (minimum 5 characters).</span>
        </div>
    <?php endif; ?>

    <form action="../../../services/feedback/feedbackHandler.php" method="POST" id="feedbackForm">
        <div class="form-group">
            <label><i class="bi bi-star-fill"></i> Rating *</label>
            <div class="rating-container">
                <div class="rating-input">
                    <input type="radio" name="rating" id="star5" value="5" required>
                    <label for="star5">★</label>
                    <input type="radio" name="rating" id="star4" value="4">
                    <label for="star4">★</label>
                    <input type="radio" name="rating" id="star3" value="3">
                    <label for="star3">★</label>
                    <input type="radio" name="rating" id="star2" value="2">
                    <label for="star2">★</label>
                    <input type="radio" name="rating" id="star1" value="1">
                    <label for="star1">★</label>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label for="message"><i class="bi bi-pencil-square"></i> Your Feedback *</label>
            <textarea 
                name="message" 
                id="message" 
                placeholder="Tell us about your experience with our bookstore..." 
                required
                minlength="5"
                maxlength="1000"></textarea>
            <div class="char-count">
                <span id="charCount">0</span> / 1000 characters
            </div>
        </div>

        <button type="submit" class="submit-btn">
            <i class="bi bi-send-fill"></i> Submit Feedback
        </button>
    </form>

    <a href="mainPage.php" class="back-link">
        <i class="bi bi-arrow-left-circle"></i> Back to Main Page
    </a>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    const textarea = document.getElementById('message');
    const charCount = document.getElementById('charCount');

    textarea.addEventListener('input', function() {
        charCount.textContent = this.value.length;
    });

    // Star rating hover effect
    const stars = document.querySelectorAll('.rating-input label');
    stars.forEach(star => {
        star.addEventListener('mouseenter', function() {
            const rating = this.previousElementSibling.value;
            stars.forEach((s, index) => {
                if (5 - index <= rating) {
                    s.style.color = '#ffc107';
                } else {
                    s.style.color = '#ddd';
                }
            });
        });
    });

    document.querySelector('.rating-input').addEventListener('mouseleave', function() {
        const checked = document.querySelector('.rating-input input:checked');
        stars.forEach((s, index) => {
            if (checked && 5 - index <= checked.value) {
                s.style.color = '#ffc107';
            } else {
                s.style.color = '#ddd';
            }
        });
    });
</script>

</body>
</html>
