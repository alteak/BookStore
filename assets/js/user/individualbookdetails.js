// Global variables
// These will be set by the PHP file before this script loads
// currentBookId - ID of the current book being displayed
// currentGenres - Array of genres for the current book for recommendations

// Toast notification
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `custom-toast ${type}`;
    toast.innerHTML = `
        <i class="bi ${type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill'}"></i>
        <span>${message}</span>
    `;
    document.body.appendChild(toast);
    
    setTimeout(() => toast.classList.add('show'), 10);
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Utility functions
/**
 * Format price in Euro format with thousand separators
 * Example: 15000 -> "15.000"
 */
function formatEuro(price) {
    return Number(price).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
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

// Recommendations
/**
 * Load and display book recommendations based on current book's genres
 * Filters out current book and shows books that share at least one genre
 */
async function loadRecommendations() {
    try {
        const books = await getBooks();

        // If current book has no genres, just show random books instead
        if (!currentGenres || currentGenres.length === 0) {
            const filtered = books.filter(b => b.id !== currentBookId).slice(0, 10);
            
            const section = document.querySelector(".recommendations-section");
            const row = document.getElementById("recommendationsRow");
            const nextBtn = document.getElementById("sliderNext");

            if (!filtered.length) {
                section.style.display = "none";
                return;
            }

            row.innerHTML = filtered.map(b => `
                <a class="book-card" href="individualbookdetails.php?id=${b.id}">
                    <div class="book-cover-rec">
                        <img src="${b.image}" alt="${b.title}" loading="lazy">
                    </div>
                    <div class="book-name-rec">${b.title}</div>
                    <div class="book-price-rec">${formatEuro(b.price)} €</div>
                </a>
            `).join("");

            requestAnimationFrame(() => {
                const canScroll = row.scrollWidth > row.clientWidth + 5;
                nextBtn.style.display = canScroll ? "flex" : "none";
            });

            nextBtn.onclick = () => {
                row.scrollBy({ left: 240, behavior: "smooth" });
            };
            return;
        }

        // Filter books: exclude current book, include books that share at least one genre
        const filtered = books.filter(b => {
            if (b.id === currentBookId) return false;

            let bookGenres = [];
            if (Array.isArray(b.genres)) {
                bookGenres = b.genres;
            } else if (typeof b.genres === "string") {
                bookGenres = b.genres.split(",").map(g => g.trim());
            }
            
            // Check if any of the book's genres match any of the current book's genres
            return bookGenres.some(genre => currentGenres.includes(genre));
        });

        const section = document.querySelector(".recommendations-section");
        const row = document.getElementById("recommendationsRow");
        const nextBtn = document.getElementById("sliderNext");

        // Hide section if no recommendations found
        if (!filtered.length) {
            section.style.display = "none";
            return;
        }

        // Render up to 10 recommendations
        row.innerHTML = filtered.slice(0, 10).map(b => `
            <a class="book-card" href="individualbookdetails.php?id=${b.id}">
                <div class="book-cover-rec">
                    <img src="${b.image}" alt="${b.title}" loading="lazy">
                </div>
                <div class="book-name-rec">${b.title}</div>
                <div class="book-price-rec">${formatEuro(b.price)} €</div>
            </a>
        `).join("");

        // Show/hide scroll button based on whether content is scrollable
        requestAnimationFrame(() => {
            const canScroll = row.scrollWidth > row.clientWidth + 5;
            nextBtn.style.display = canScroll ? "flex" : "none";
        });

        // Add click handler to scroll button
        nextBtn.onclick = () => {
            row.scrollBy({ left: 240, behavior: "smooth" });
        };

    } catch (err) {
        console.error("Recommendations error:", err);
        document.querySelector(".recommendations-section").style.display = "none";
    }
}

// Add to wishlist
/**
 * Handle adding current book to user's wishlist
 */
function setupWishlistButton() {
    document.getElementById("addToWishlistBtn").addEventListener("click", async () => {
        try {
            const res = await fetch("../../../services/Frontend/wishlistService.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    book_id: currentBookData.id
                })
            });

            const data = await res.json();
            if (!data.success) throw new Error(data.message || "Error");

            showToast('Book added to wishlist ❤️', 'success');
        } catch (err) {
            console.error(err);
            showToast('Error adding to wishlist', 'error');
        }
    });
}

// Add to cart
/**
 * Handle adding current book to user's shopping cart
 */
function setupCartButton() {
    const btn = document.getElementById("addToCartBtn");
    
    // Check stock before allowing add to cart
    if (currentBookData.stock === 0) {
        return; // Button already disabled in HTML
    }
    
    btn.addEventListener("click", async () => {
        // Double-check stock
        if (currentBookData.stock === 0) {
            return;
        }
        
        try {
            const res = await fetch("../../../services/Frontend/cartService.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    book_id: currentBookData.id,
                    quantity: 1
                })
            });

            const data = await res.json();
            if (!data.success) throw new Error(data.message || "Error");

            showToast('Book added to cart 🛒', 'success');
        } catch (err) {
            console.error(err);
            showToast('Error adding to cart', 'error');
        }
    });
}

// Initialization
/**
 * Initialize all functionality when page loads
 */
function init() {
    loadRecommendations();
    setupWishlistButton();
    setupCartButton();
}

// Run initialization when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
