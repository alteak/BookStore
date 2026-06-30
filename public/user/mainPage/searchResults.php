<?php 
require_once __DIR__ . '/../../../services/session/sessionHandler.php';
requireLogin();

require_once __DIR__ . '/../../../services/Frontend/searchQuery.php';

    $searchTerm = $_POST["search"] ?? '';
    $selectedGenre = $_POST["genre"] ?? '';
    $results = []; 
    // E inicializoj boshe, ne php vleredhenia ne indexe inekzistente i shton ato indexe ne fund te array


    // sipas rastit: 2 kushte -> 4 raste (pyet profin si mund te optimzohen queryt)
    if (!empty($searchTerm) && !empty($selectedGenre)) {
        $results = searchBooksByGenre($searchTerm, $selectedGenre, $conn);} 

    else if (!empty($selectedGenre)) {
        $results = getBooksByGenre($selectedGenre, $conn);} 

    else if (!empty($searchTerm)) {
        $results = searchBooks($searchTerm, $conn);}

    else {
        $results = getAllBooks($conn);}

    $genres = getAllGenres($conn);
?>
<!DOCTYPE html>

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Book Search</title>


    <!-- bootstrapi per navbarin si ne main page-->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" type="text/css" href="../../../assets/css/user/mainPageTemplate.css">
    <link rel="stylesheet" type="text/css" href="../../../assets/css/user/mainPageCustom.css">

    <!-- CSS nga Copiloti i Visual Studio Code, Claude Haiku 4.5   -->
    <link rel="stylesheet" type="text/css" href="../../../assets/css/user/searchResults.css">

</head>
<body id="page-top">

<div class="masthead-section">
    <div class="search-container">
        <h2>Search Books</h2>
        <form method="POST" action="searchResults.php">
            <div class="search-bar">
                <input type="text" name="search" value="<?php echo htmlspecialchars($searchTerm); ?>" placeholder="Search by title or author">
                <button type="submit">Search</button>
            </div>
        </form>
    </div>
</div>
<!-- Struktura e navbarit njesoj pak a shume kudo -->
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
                        <span class="icon-text">CART</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link icon-link" href="../logout.php">
                        <span class="icon-circle"><i class="bi bi-box-arrow-right"></i></span>
                        <span class="icon-text">LOG <OUTput></OUTput></span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

    <div class="main-content">

        <!-- lista e zhanreve, thjesht nje ciker foreach, echo cdo te dhene, css siguron paraqitjen e duhur-->
        <div class="genre-filter">
            <h3>Filter by Genre:</h3>
            <form method="POST" action="searchResults.php">
                <input type="hidden" name="search" value="<?php echo htmlspecialchars($searchTerm); ?>">
                <?php foreach ($genres as $genre): ?>
                    <label>
                        <input type="checkbox" name="genre" value="<?php echo htmlspecialchars($genre['name']); ?>" class="genre-checkbox" <?php echo ($selectedGenre === $genre['name'] ? 'checked' : ''); ?>>
                        <?php echo htmlspecialchars($genre['name']); ?>
                    </label>
                <?php endforeach; ?>
            </form>
        </div>

        <div class="results-content">
            <h3>Results:</h3>
            <div class="book-grid">
                <?php if (!empty($results)): ?>
                    <?php foreach ($results as $book): ?>
                        <div class="book-card">
                            <a href="individualBookDetails.php?id=<?php echo htmlspecialchars($book['id']) ?>"><img src="<?php echo htmlspecialchars($book['cover_image']); ?>" class="book-cover" alt="Cover"></a>
                            <h4><?php echo htmlspecialchars($book['title']); ?></h4>
                            <p><?php echo htmlspecialchars($book['author']); ?></p>
                            <div class="book-price"><?php echo htmlspecialchars($book['price']); ?> €</div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-results">No books found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // e kam bere checkbox se katrore duket me appealing
        // JS per kushtin me e shumta 1 zhaner i perzgjedhur 
        document.querySelectorAll('.genre-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                if (this.checked) {
                    document.querySelectorAll('.genre-checkbox').forEach(cb => {
                        if (cb !== this) cb.checked = false;
                    });
                    // Auto-submit per cdo event, ne kte rast per checkboxe klikimi eshte eventi -> sjell reload
                    this.closest('form').submit();
                }
                else {
                    this.closest('form').submit();
                }
            });
        });
    </script>

    <footer class="bg-light py-4">
        <div class="container text-center text-muted">
            © 2025 Online Bookstore – University Project
        </div>
    </footer>

</body>
</html>