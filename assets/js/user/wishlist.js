function formatEuro(price){
    return Number(price).toString().replace(/\B(?=(\d{3})+(?!\d))/g,".");
}

function escapeHtml(text){
    const div=document.createElement('div');
    div.textContent=text;
    return div.innerHTML;
}

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

async function loadWishlist(){
    try{
        const res = await fetch('../../../services/Frontend/wishlistService.php');
        const data = await res.json();

        document.getElementById('loadingState').classList.add('d-none');

        if(!data.success || !data.data || !data.data.length){
            document.getElementById('emptyState').classList.remove('d-none');
            return;
        }

        const books = data.data;
        const grid=document.getElementById('booksGrid');
        grid.classList.remove('d-none');

        grid.innerHTML = books.map(book => `
      <div class="book-card" data-book-id="${book.id}">
        <div class="book-image">
          <a href="individualbookdetails.php?id=${book.id}">
            <img src="${escapeHtml(book.image)}">
          </a>
          <button class="remove-btn" onclick="removeFromWishlist(${book.id})">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>
        <div class="book-info">
          <span class="book-category">${escapeHtml(book.category || 'Book')}</span>
          <div class="book-title">${escapeHtml(book.title)}</div>
          <div class="book-price">${formatEuro(book.price)} €</div>
          <div class="stock-status ${book.stock === 0 ? 'out-of-stock' : book.stock < 10 ? 'low-stock' : 'in-stock'}">
            ${book.stock === 0 ? '❌ Out of stock' : book.stock < 10 ? `⚠️ Only ${book.stock} in stock` : '✓ In stock'}
          </div>
          <div class="book-actions">
            <a href="individualbookdetails.php?id=${book.id}" class="btn-view">
              <i class="bi bi-eye"></i> View Details
            </a>
            <button class="btn-cart" onclick="addToCartFromWishlist(${book.id})" ${book.stock === 0 ? 'disabled style="opacity: 0.6; cursor: not-allowed;"' : ''}>
              <i class="bi bi-bag-plus"></i> ${book.stock === 0 ? 'Out of stock' : 'Add to Cart'}
            </button>
          </div>
        </div>
      </div>
    `).join('');

    } catch(e){
        console.error(e);
        document.getElementById('loadingState').classList.add('d-none');
        document.getElementById('emptyState').classList.remove('d-none');
    }
}

async function removeFromWishlist(bookId){
    try{
        const res = await fetch('../../../services/Frontend/wishlistService.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ book_id: bookId })
        });
        
        const data = await res.json();
        
        if(data.success){
            location.reload();
        } else {
            console.error(data.message || 'Error during removal');
        }
    } catch(e){
        console.error(e);
    }
}

function addToCartFromWishlist(bookId){
    // Use the cart API instead of localStorage
    fetch('../../../services/Frontend/cartService.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            book_id: bookId,
            quantity: 1
        })
    })
    .then(res => res.json())
    .then(data => {
        if(data.success){
            showToast('Book added to cart 🛒', 'success');
        } else {
            showToast(data.message || 'Error adding to cart', 'error');
        }
    })
    .catch(e => {
        console.error(e);
        showToast('Server connection error', 'error');
    });
}

// Initialize on page load
loadWishlist();
