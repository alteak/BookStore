function formatEuro(price){
    return Number(price).toString().replace(/\B(?=(\d{3})+(?!\d))/g,".");
}

function escapeHtml(text){
    const div=document.createElement('div');
    div.textContent=text;
    return div.innerHTML;
}

async function loadCart(){
    try{
        const res = await fetch('../../../services/Frontend/cartService.php');
        const data = await res.json();
        
        document.getElementById('loadingState').classList.add('d-none');
        
        if(!data.success || !data.data || !data.data.length){
            document.getElementById('emptyState').classList.remove('d-none');
            document.getElementById('cartContent').classList.add('d-none');
            return;
        }
        
        const cart = data.data;
        
        // Store cart items globally for stock validation
        window.cartItems = cart;
        
        document.getElementById('emptyState').classList.add('d-none');
        document.getElementById('cartContent').classList.remove('d-none');
        
        // Render cart items
        const itemsContainer = document.getElementById('cartItems');
        itemsContainer.innerHTML = cart.map(item => {
            const canIncrease = item.qty < item.stock;
            return `
            <div class="cart-item">
                <a href="individualbookdetails.php?id=${item.id}" class="item-image-link">
                    <div class="item-image">
                        <img src="${escapeHtml(item.image || '')}" alt="${escapeHtml(item.title)}">
                    </div>
                </a>
                <div class="item-details">
                    <a href="individualbookdetails.php?id=${item.id}" class="item-title-link">
                        <div class="item-title">${escapeHtml(item.title)}</div>
                    </a>
                    <span class="item-category">${escapeHtml(item.category || 'Book')}</span>
                    <div class="item-price">${formatEuro(item.price)} €</div>
                </div>
                <div class="item-actions">
                    <button class="remove-btn" onclick="removeFromCart(${item.id})">
                        <i class="bi bi-x-lg"></i>
                    </button>
                    <div class="quantity-control">
                        <button class="qty-btn" onclick="updateQuantity(${item.id}, ${item.qty - 1}, ${item.stock})">
                            <i class="bi bi-dash"></i>
                        </button>
                        <span class="qty-value">${item.qty || 1}</span>
                        <button class="qty-btn" onclick="updateQuantity(${item.id}, ${item.qty + 1}, ${item.stock})" 
                                ${!canIncrease ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : ''}>
                            <i class="bi bi-plus"></i>
                        </button>
                    </div>
                </div>
            </div>
            `;
        }).join('');
        
        // Calculate totals
        const subtotal = cart.reduce((sum, item) => sum + (item.price * (item.qty || 1)), 0);
        const shipping = 0; // Free shipping
        
        // Store subtotal globally for discount calculation
        window.cartSubtotal = subtotal;
        window.appliedDiscount = null;
        
        const total = subtotal + shipping;
        
        document.getElementById('subtotal').textContent = formatEuro(subtotal) + ' €';
        document.getElementById('shipping').textContent = shipping === 0 ? 'Free' : formatEuro(shipping) + ' €';
        document.getElementById('total').textContent = formatEuro(total) + ' €';
        document.getElementById('discountAmount').textContent = '0 €';
        
    }catch(e){
        console.error(e);
        document.getElementById('loadingState').classList.add('d-none');
        document.getElementById('emptyState').classList.remove('d-none');
    }
}

async function updateQuantity(bookId, newQty, stock){
    if(newQty < 1) return;
    
    // Check if trying to exceed available stock
    if(newQty > stock){
        return;
    }
    
    try{
        const res = await fetch('../../../services/Frontend/cartService.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                book_id: bookId,
                quantity: newQty
            })
        });
        
        const data = await res.json();
        
        if(data.success){
            loadCart();
        } else {
            console.error(data.message || 'Gabim gjatë përditësimit');
        }
    }catch(e){
        console.error(e);
    }
}

async function removeFromCart(bookId){
    try{
        const res = await fetch('../../../services/Frontend/cartService.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ book_id: bookId })
        });
        
        const data = await res.json();
        
        if(data.success){
            loadCart();
        } else {
            console.error(data.message || 'Error during removal');
        }
    }catch(e){
        console.error(e);
    }
}

// Initialize on page load
loadCart();

// Discount Code Functionality
const discountCodeInput = document.getElementById('discountCode');
const applyDiscountBtn = document.getElementById('applyDiscountBtn');
const discountMessage = document.getElementById('discountMessage');

applyDiscountBtn.addEventListener('click', applyDiscount);
discountCodeInput.addEventListener('keypress', (e) => {
    if(e.key === 'Enter') applyDiscount();
});

async function applyDiscount(){
    const code = discountCodeInput.value.trim();
    
    if(!code){
        showDiscountMessage('Enter a discount code', 'error');
        return;
    }
    
    // Disable button during processing
    applyDiscountBtn.disabled = true;
    applyDiscountBtn.textContent = 'Applying...';
    
    try{
        const res = await fetch('../../../services/Frontend/discountService.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                action: 'validate_discount',
                code: code,
                subtotal: window.cartSubtotal || 0
            })
        });
        
        const data = await res.json();
        
        if(data.success && data.discount_amount){
            const discountAmount = parseFloat(data.discount_amount);
            const subtotal = window.cartSubtotal || 0;
            const shipping = 0;
            const finalTotal = Math.max(0, subtotal + shipping - discountAmount);
            
            // Store discount info globally for checkout
            window.appliedDiscount = {
                code: data.discount_code,
                amount: discountAmount
            };
            
            // Update UI
            document.getElementById('discountAmount').textContent = '-' + formatEuro(discountAmount) + ' €';
            document.getElementById('total').textContent = formatEuro(finalTotal) + ' €';
            
            showDiscountMessage('✓ Code applied successfully!', 'success');
            discountCodeInput.disabled = true;
            applyDiscountBtn.disabled = true;
            applyDiscountBtn.textContent = 'Applied';
        } else {
            showDiscountMessage(data.message || 'Code is not valid', 'error');
            applyDiscountBtn.disabled = false;
            applyDiscountBtn.textContent = 'Apply';
        }
    }catch(e){
        console.error(e);
        showDiscountMessage('Error while applying code', 'error');
        applyDiscountBtn.disabled = false;
        applyDiscountBtn.textContent = 'Apply';
    }
}

function showDiscountMessage(message, type){
    discountMessage.textContent = message;
    discountMessage.className = `discount-message ${type}`;
}
