<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../../services/session/sessionHandler.php';  
requireLogin();

require_once __DIR__ . "/../../../database/databaseConnection.php";
/** @var mysqli $conn */

// Validate ID
if (!isset($_GET['id'])) {
    die("Missing book id.");
}

$bookId = (int)$_GET['id'];
if ($bookId <= 0) {
    die("Invalid id.");
}

// Init
$id = 0;
$titulli = '';
$autori = '';
$cmimi = 0;
$image = '';
$pershkrimi = '';
$zhanri = '';
$zhanriId = 0;
$stock = 0;

// Query - Get book details
$sql = "
    SELECT 
        b.id,
        b.title AS titulli,
        b.author AS autori,
        b.description AS pershkrimi,
        b.cover_image AS image,
        COALESCE(i.price, 0) AS cmimi,
        COALESCE(i.stock, 0) AS stock
    FROM books b
    LEFT JOIN inventory i ON i.book_id = b.id
    WHERE b.id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare error: " . $conn->error);
}

$stmt->bind_param("i", $bookId);
$stmt->execute();
$stmt->bind_result($id, $titulli, $autori, $pershkrimi, $image, $cmimi, $stock);

if (!$stmt->fetch()) {
    die("Book not found.");
}
$stmt->close();

// Get all genres for this book
$genresSql = "SELECT g.name, g.id FROM book_genres bg JOIN genres g ON g.id = bg.genre_id WHERE bg.book_id = ?";
$genresStmt = $conn->prepare($genresSql);
if (!$genresStmt) {
    die("Prepare error: " . $conn->error);
}

$genresStmt->bind_param("i", $bookId);
$genresStmt->execute();
$genresResult = $genresStmt->get_result();

$genres = [];
$genreIds = [];
while ($genreRow = $genresResult->fetch_assoc()) {
    $genres[] = $genreRow['name'];
    $genreIds[] = $genreRow['id'];
}
$genresStmt->close();

// For backward compatibility, set $zhanri to the first genre or empty
$zhanri = !empty($genres) ? $genres[0] : '';
$zhanriId = !empty($genreIds) ? $genreIds[0] : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($titulli); ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Merriweather+Sans:400,700" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css?family=Merriweather:400,300,700" rel="stylesheet" />

    <!-- Base styles -->
    <link rel="stylesheet" href="../../../assets/css/user/mainPageTemplate.css">
    <link rel="stylesheet" href="../../../assets/css/user/mainPage.css">

    <!-- Book details styles -->
    <link rel="stylesheet" href="../../../assets/css/user/individualbookdetails.css">
</head>

<body id="page-top">

<!-- ===== NAVBAR ===== -->
 <section id="navbarSection">
    <div class="container px-4 px-lg-5 d-flex align-items-center">

        <a class="navbar-brand fw-bold me-3" href="mainPage.php">Online Bookstore</a>

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
                        <span class="icon-text">SHPORTA</span>
                    </a>
                </li>
            </ul>

            <div class="dropdown">
                <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                    Menu
                </button>

                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="#services">Shërbimet</a></li>
                    <li><a class="dropdown-item" href="#portfolio">Librat</a></li>
                    <li><a class="dropdown-item" href="#contact">Kontakt</a></li>
                    <li><a class="dropdown-item" href="../../../services/feedback/feedbackHandler.php">Feedback</a></li>
                    <li><a class="dropdown-item" href="../logout.php">Dil</a></li>
                </ul>
            </div>
        </div>

    </div>
</section>
<!-- MAIN BOOK SECTION -->
<section class="book-display-section">
    <div class="container-custom">

        <!-- BOOK CARD -->
        <div class="book-detail-card">
            <div class="book-detail-flex">

                <!-- IMAGE -->
                <div class="book-image-container">
                    <?php if (!empty($image)): ?>
                        <img
                                class="book-detail-image"
                                src="<?php echo htmlspecialchars($image); ?>"
                                alt="<?php echo htmlspecialchars($titulli); ?>"
                        >
                    <?php else: ?>
                        <div class="book-image-placeholder"></div>
                    <?php endif; ?>
                </div>

                <!-- INFO -->
                <div class="book-detail-info">
                    <h1><?php echo htmlspecialchars($titulli); ?></h1>

                    <p class="book-detail-meta">
                        <strong>Author:</strong> <?php echo htmlspecialchars($autori ?: '—'); ?>
                    </p>

                    <p class="book-detail-meta">
                        <strong>Genre:</strong> <?php echo !empty($genres) ? htmlspecialchars(implode(', ', $genres)) : '—'; ?>
                    </p>

                    <p class="book-detail-price">
                        <?php echo number_format((float)$cmimi, 0, '', '.'); ?> €
                    </p>

                    <p class="book-detail-meta">
                        <strong>Availability:</strong> 
                        <?php if($stock < 10 && $stock > 0): ?>
                            <span style="color: #ff9800; font-weight: 600;">Only <?php echo $stock; ?> in stock</span>
                        <?php elseif($stock === 0): ?>
                            <span style="color: #dc3545; font-weight: 600;">Out of stock</span>
                        <?php else: ?>
                            <span style="color: #198754; font-weight: 600;">In stock</span>
                        <?php endif; ?>
                    </p>

                    <h3>Description</h3>
                    <p class="book-detail-description">
                        <?php echo nl2br(htmlspecialchars($pershkrimi)); ?>
                    </p>

                    <div class="book-actions-detail">
                        <button type="button" class="btn-add-cart" id="addToCartBtn" <?php echo ($stock === 0) ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : ''; ?>>
                            <i class="bi bi-cart-plus"></i> <?php echo ($stock === 0) ? 'Out of stock' : 'Add to Cart'; ?>
                        </button>

                        <button type="button" class="btn-wishlist" id="addToWishlistBtn">
                            <i class="bi bi-heart"></i> Add to Wishlist
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </div>
</section>

<!-- RECOMMENDATIONS SECTION -->
<section class="recommendations-wrapper">
    <div class="container-custom">
        <div class="recommendations-section">
            <h3 class="recommendations-title"><i class="bi bi-book"></i> Similar Books</h3>

            <div class="slider-wrap">
                <div class="books-row" id="recommendationsRow"></div>
                <div class="slider-next" id="sliderNext">›</div>
            </div>
        </div>
    </div>
</section>

<script>
    // Pass PHP data to JavaScript
    const currentBookId = <?php echo $id; ?>;
    const currentGenres = <?php echo json_encode($genres); ?>;
    const currentBookData = {
        id: <?php echo $id; ?>,
        title: "<?php echo htmlspecialchars($titulli); ?>",
        price: <?php echo $cmimi; ?>,
        image: "<?php echo htmlspecialchars($image); ?>",
        category: "<?php echo !empty($genres) ? htmlspecialchars(implode(', ', $genres)) : 'Book'; ?>",
        stock: <?php echo $stock; ?>
    };
</script>
<script src="../../../assets/js/user/individualbookdetails.js"></script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
