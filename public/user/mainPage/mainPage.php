<?php
require_once __DIR__ . '/../../../services/session/sessionHandler.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description" content="Online bookstore for reading and buying books." />
    <title>Online Bookstore | Read &amp; Buy Books</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Merriweather+Sans:400,700" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css?family=Merriweather:400,300,700" rel="stylesheet" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="../../../assets/css/user/mainPageTemplate.css">
    <link rel="stylesheet" href="../../../assets/css/user/mainPage.css">

    <!-- Main page JavaScript -->
    <script src="../../../assets/js/user/mainPage.js" defer></script>
</head>

<body id="page-top">

<!-- ===== NAVBAR ===== -->
<nav class="navbar fixed-top navbar-dark py-3" id="mainNav">
    <div class="container px-4 px-lg-5 d-flex align-items-center">

        <a class="navbar-brand fw-bold me-3" href="#page-top">Online Bookstore</a>

        <form class="flex-grow-1 mx-3" role="search" action="searchResults.php" method="POST">
            <input class="form-control text-center" type="search" name="search" placeholder="Search books" aria-label="Search books" id="searchInput">
        </form>

        <div class="d-flex align-items-center gap-3">
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
                        <span class="icon-text">CART</span>
                    </a>
                </li>
            </ul>

            <div class="dropdown">
                <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                    Menu
                </button>

                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="#services">Services</a></li>
                    <li><a class="dropdown-item" href="#portfolio">Books</a></li>
                    <li><a class="dropdown-item" href="#contact">Contact</a></li>
                    <li><a class="dropdown-item" href="../../../services/feedback/feedbackHandler.php">Feedback</a></li>
                    <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
                </ul>
            </div>
        </div>

    </div>
</nav>

<header class="masthead">
    <div class="container px-4 px-lg-5 h-100">
        <div class="row h-100 align-items-center justify-content-center text-center">
            <div class="col-lg-8">
                <h1 class="text-white font-weight-bold">Discover, Read and Buy Books Online</h1>
                <hr class="divider" />
                <p class="text-white-75 mb-5">A modern platform for reading and buying books from different genres.</p>
                <a class="btn btn-primary btn-xl" href="#services">Explore the Library</a>
            </div>
        </div>
    </div>
</header>

<section class="page-section" id="services">
    <div class="container px-4 px-lg-5">
        <h2 class="text-center mt-0">What We Offer</h2>
        <hr class="divider" />

        <div class="row gx-4 gx-lg-5">
            <div class="col-lg-4 col-md-6 text-center">
                <a href="#portfolio" class="service-link">
                    <i class="bi bi-book fs-1 text-primary"></i>
                    <h3 class="h4 mt-2">Book Collection</h3>
                </a>
                <p class="text-muted mb-0">Different genres</p>
            </div>

            <div class="col-lg-4 col-md-6 text-center">
                <a href="profili.php" class="service-link">
                    <i class="bi bi-person-check fs-1 text-primary"></i>
                    <h3 class="h4 mt-2">User Account</h3>
                </a>
                <p class="text-muted mb-0">Manage books</p>
            </div>

            <div class="col-lg-4 col-md-6 text-center">
                <a href="cart.php" class="service-link">
                    <i class="bi bi-cart fs-1 text-primary"></i>
                    <h3 class="h4 mt-2">Online Shopping</h3>
                </a>
                <p class="text-muted mb-0">Fast &amp; secure</p>
            </div>
        </div>
    </div>
</section>

<div id="portfolio" class="container-fluid p-0">
    <div class="row g-0">
        <div class="col-lg-4 col-sm-6"><img class="img-fluid" src="../../../assets/visual/portfolio/thumbnails/1.jpg" alt=""></div>
        <div class="col-lg-4 col-sm-6"><img class="img-fluid" src="../../../assets/visual/portfolio/thumbnails/2.jpg" alt=""></div>
        <div class="col-lg-4 col-sm-6"><img class="img-fluid" src="../../../assets/visual/portfolio/thumbnails/3.jpg" alt=""></div>
    </div>
</div>

<!-- ===== LIBRA (sipas zhanreve) ===== -->
<section class="page-section" id="books">
    <div class="container px-4 px-lg-5">
        <div class="text-center mb-4">
            <h2 class="m-0 books-title">Books</h2>
        </div>

        <div id="booksByGenre"></div>
    </div>
</section>

<section class="page-section" id="contact">
    <div class="container px-4 px-lg-5 text-center">
        <h2>Contact Us</h2>
        <hr class="divider" />
        <p class="text-muted">For questions or suggestions</p>
    </div>
</section>

<footer class="bg-light py-4">
    <div class="container text-center text-muted">
        © 2025 Online Bookstore – University Project
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
