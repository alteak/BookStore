// Navbar scroll behavior
/**
 * Handle navbar appearance and behavior on scroll
 * - Changes navbar background from transparent to white
 * - Hides navbar when scrolling down, shows when scrolling up
 */
function setupNavbarScroll() {
    const nav = document.getElementById("mainNav");
    let lastScrollY = window.scrollY;

    window.addEventListener("scroll", () => {
        const currentScroll = window.scrollY;

        // Change navbar style after scrolling 50px
        if (currentScroll > 50) {
            nav.classList.add("scrolled");
            nav.classList.remove("navbar-dark");
            nav.classList.add("navbar-light");
        } else {
            nav.classList.remove("scrolled");
            nav.classList.add("navbar-dark");
            nav.classList.remove("navbar-light");
        }

        // Hide navbar when scrolling down, show when scrolling up
        if (currentScroll > lastScrollY && currentScroll > 120) {
            nav.classList.add("nav-hidden");
        } else {
            nav.classList.remove("nav-hidden");
        }

        lastScrollY = currentScroll;
    });
}

// Utility functions
/**
 * Format price in Euro format with thousand separators
 * Example: 15000 -> "15.000"
 */
function formatEuro(price) {
    return Number(price)
        .toString()
        .replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

/**
 * Fetch all books from the API
 * @returns {Promise<Array>} Array of book objects
 */
async function getBooks() {
    const res = await fetch("../../../services/Frontend/displayBooksFront.php", {
        cache: "no-store"
    });
    if (!res.ok) throw new Error("Error in displayBooksFront.php");
    return await res.json();
}

// Render books
/**
 * Group books by genre and render them in horizontal scrollable rows
 * @param {Array} books - Array of book objects from API
 */
function renderBooks(books) {
    const root = document.getElementById("booksByGenre");
    if (!root) return;

    // Group books by genre
    const grouped = {};

    books.forEach(b => {
        // Support: genres as array OR comma string OR single category
        let genres = [];

        if (Array.isArray(b.genres)) {
            genres = b.genres;
        } else if (typeof b.genres === "string") {
            genres = b.genres.split(",").map(g => g.trim());
        } else if (b.category) {
            genres = [b.category];
        } else {
            genres = ["Others"];
        }

        // Add book to each of its genres
        genres.forEach(g => {
            if (!grouped[g]) grouped[g] = [];
            grouped[g].push(b);
        });
    });

    // Render each genre section with its books
    root.innerHTML = Object.keys(grouped).map(genre => {
        return `
      <div class="genre-section">
        <h3 class="genre-title">${genre}</h3>
        <div class="books-row">
          ${grouped[genre].map(b => `
            <a class="book-card" href="individualbookdetails.php?id=${b.id}">
              <div class="book-cover">
                <img src="${b.image}" alt="${b.title}">
              </div>
              <div class="book-name">${b.title}</div>
              <div class="book-price">${formatEuro(b.price)} €</div>
            </a>
          `).join("")}
        </div>
      </div>
    `;
    }).join("");
}

// Load books
/**
 * Fetch books from API and render them on the page
 * Shows error message if loading fails
 */
async function loadBooks() {
    try {
        const books = await getBooks();
        renderBooks(books);
    } catch (e) {
        console.error(e);
        document.getElementById("booksByGenre").innerHTML =
            "<p class='text-danger'>Error loading books</p>";
    }
}

// Initialization
/**
 * Initialize all functionality when page loads
 */
function init() {
    setupNavbarScroll();
    loadBooks();
}

// Run initialization when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
