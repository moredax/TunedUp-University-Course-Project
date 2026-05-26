// Cart functionality
document.addEventListener('DOMContentLoaded', function() {
    // Quantity increase/decrease buttons
    document.querySelectorAll('.qty-increase').forEach(btn => {
        btn.addEventListener('click', function() {
            const cartItem = this.closest('.cart-item');
            const itemType = cartItem.dataset.itemType;
            const itemId = cartItem.dataset.itemId;
            const qtySpan = cartItem.querySelector('.qty-number');
            const currentQty = parseInt(qtySpan.textContent);
            updateCartQuantity(itemType, itemId, currentQty + 1);
        });
    });

    document.querySelectorAll('.qty-decrease').forEach(btn => {
        btn.addEventListener('click', function() {
            const cartItem = this.closest('.cart-item');
            const itemType = cartItem.dataset.itemType;
            const itemId = cartItem.dataset.itemId;
            const qtySpan = cartItem.querySelector('.qty-number');
            const currentQty = parseInt(qtySpan.textContent);
            if (currentQty > 1) {
                updateCartQuantity(itemType, itemId, currentQty - 1);
            }
        });
    });

    // Remove item buttons
    document.querySelectorAll('.cart-remove').forEach(btn => {
        btn.addEventListener('click', function() {
            const cartItem = this.closest('.cart-item');
            const itemType = cartItem.dataset.itemType;
            const itemId = cartItem.dataset.itemId;
            removeFromCart(itemType, itemId);
        });
    });

    // Checkout button
    const checkoutBtn = document.getElementById('checkout-btn');
    if (checkoutBtn) {
        checkoutBtn.addEventListener('click', function() {
            handleCheckout();
        });
    }
});

function updateCartQuantity(itemType, itemId, quantity) {
    const formData = new FormData();
    formData.append('action', 'update');
    formData.append('item_type', itemType);
    formData.append('item_id', itemId);
    formData.append('quantity', quantity);

    fetch('api/cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update quantity display
            const cartItem = document.querySelector(`[data-item-type="${itemType}"][data-item-id="${itemId}"]`);
            if (cartItem) {
                const qtySpan = cartItem.querySelector('.qty-number');
                qtySpan.textContent = quantity;
            }
            // Refresh cart totals
            refreshCartTotals();
        } else {
            alert('Failed to update cart: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating the cart');
    });
}

function removeFromCart(itemType, itemId) {
    // Get item details from the cart item element
    const cartItem = document.querySelector(`[data-item-type="${itemType}"][data-item-id="${itemId}"]`);
    if (!cartItem) {
        return;
    }

    // Extract item details
    const itemName = cartItem.querySelector('.cart-title')?.textContent || 'Item';
    const itemPrice = cartItem.querySelector('.cart-price')?.textContent || '$0.00';
    const itemImage = cartItem.querySelector('.cart-image')?.src || '';

    // Show delete confirmation modal
    showDeleteConfirmation(itemType, itemId, itemName, itemPrice, itemImage);
}

function showDeleteConfirmation(itemType, itemId, itemName, itemPrice, itemImage) {
    const modal = document.getElementById('delete-confirmation-modal');
    const itemImageEl = document.getElementById('delete-item-image');
    const itemNameEl = document.getElementById('delete-item-name');
    const itemPriceEl = document.getElementById('delete-item-price');
    const confirmBtn = document.getElementById('delete-confirm-btn');
    const cancelBtn = document.getElementById('delete-cancel-btn');
    const closeBtn = document.querySelector('.delete-modal-close');

    // Set item details
    itemImageEl.src = itemImage;
    itemImageEl.alt = itemName;
    itemNameEl.textContent = itemName;
    itemPriceEl.textContent = itemPrice;

    // Show modal
    modal.classList.add('show');

    // Close modal function
    function closeModal() {
        modal.classList.remove('show');
    }

    // Remove existing event listeners by cloning and replacing
    const newConfirmBtn = confirmBtn.cloneNode(true);
    const newCancelBtn = cancelBtn.cloneNode(true);
    const newCloseBtn = closeBtn.cloneNode(true);
    
    confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
    cancelBtn.parentNode.replaceChild(newCancelBtn, cancelBtn);
    closeBtn.parentNode.replaceChild(newCloseBtn, closeBtn);

    // Set up event listeners
    newConfirmBtn.addEventListener('click', function() {
        closeModal();
        performDelete(itemType, itemId);
    });

    newCancelBtn.addEventListener('click', closeModal);
    newCloseBtn.addEventListener('click', closeModal);

    // Close on background click
    modal.onclick = function(e) {
        if (e.target === modal) {
            closeModal();
        }
    };

    // Close on Escape key
    const handleEscape = function(e) {
        if (e.key === 'Escape' && modal.classList.contains('show')) {
            closeModal();
            document.removeEventListener('keydown', handleEscape);
        }
    };
    document.addEventListener('keydown', handleEscape);
}

function performDelete(itemType, itemId) {
    const formData = new FormData();
    formData.append('action', 'remove');
    formData.append('item_type', itemType);
    formData.append('item_id', itemId);

    fetch('api/cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove item from DOM
            const cartItem = document.querySelector(`[data-item-type="${itemType}"][data-item-id="${itemId}"]`);
            if (cartItem) {
                cartItem.remove();
            }
            // Refresh cart totals
            refreshCartTotals();
            
            // Check if cart is empty
            const cartLeft = document.querySelector('.cart-left');
            if (cartLeft && cartLeft.querySelectorAll('.cart-item').length === 0) {
                location.reload(); // Reload to show empty cart message
            }
        } else {
            alert('Failed to remove item: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while removing the item');
    });
}

function refreshCartTotals() {
    fetch('api/cart.php?action=get')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const subtotalEl = document.getElementById('cart-subtotal');
                const totalEl = document.getElementById('cart-total');
                if (subtotalEl) {
                    subtotalEl.textContent = '$' + data.total.toFixed(2);
                }
                if (totalEl) {
                    totalEl.textContent = '$' + data.total.toFixed(2);
                }
                
                // Update checkout button state
                const checkoutBtn = document.getElementById('checkout-btn');
                if (checkoutBtn) {
                    checkoutBtn.disabled = data.count === 0;
                }
            }
        })
        .catch(error => {
            console.error('Error refreshing cart totals:', error);
        });
}

function handleCheckout() {
    const name = document.getElementById('checkout-name').value.trim();
    const email = document.getElementById('checkout-email').value.trim();
    const address = document.getElementById('checkout-address').value.trim();
    const paymentMethod = document.querySelector('input[name="payment"]:checked').value;

    // Validate form
    if (!name || !email || !address) {
        alert('Please fill in all required fields');
        return;
    }

    if (!validateEmail(email)) {
        alert('Please enter a valid email address');
        return;
    }

    // Get cart items
    fetch('api/cart.php?action=get')
        .then(response => response.json())
        .then(data => {
            if (!data.success || data.count === 0) {
                alert('Your cart is empty');
                return;
            }

            // Create order
            const formData = new FormData();
            formData.append('action', 'checkout');
            formData.append('name', name);
            formData.append('email', email);
            formData.append('address', address);
            formData.append('payment_method', paymentMethod);
            formData.append('total', data.total);

            fetch('api/checkout.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                // Check if response is ok and has content
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                // Check if response has content
                const contentType = response.headers.get("content-type");
                if (!contentType || !contentType.includes("application/json")) {
                    return response.text().then(text => {
                        throw new Error('Server returned non-JSON response: ' + text.substring(0, 100));
                    });
                }
                return response.json();
            })
            .then(result => {
                if (result.success) {
                    // Show confirmation modal
                    showOrderConfirmation(result.order_id, data.total);
                    // Clear cart
                    fetch('api/cart.php?action=clear', { method: 'POST' });
                } else {
                    alert('Failed to place order: ' + result.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while placing the order: ' + error.message);
            });
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while processing checkout');
        });
}

function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function showOrderConfirmation(orderId, total) {
    const modal = document.getElementById('order-confirmation-modal');
    const orderIdDisplay = document.getElementById('order-id-display');
    const orderTotalDisplay = document.getElementById('order-total-display');
    const confirmBtn = document.getElementById('order-confirm-btn');
    const closeBtn = document.querySelector('.order-modal-close');

    // Set order details
    orderIdDisplay.textContent = '#' + orderId;
    orderTotalDisplay.textContent = '$' + total.toFixed(2);

    // Show modal
    modal.classList.add('show');

    // Close modal function
    function closeModal() {
        modal.classList.remove('show');
        window.location.href = 'main.php';
    }

    // Set up event listeners (using once option to prevent duplicates)
    confirmBtn.onclick = closeModal;
    closeBtn.onclick = closeModal;

    // Close on background click
    modal.onclick = function(e) {
        if (e.target === modal) {
            closeModal();
        }
    };

    // Close on Escape key
    const handleEscape = function(e) {
        if (e.key === 'Escape' && modal.classList.contains('show')) {
            closeModal();
            document.removeEventListener('keydown', handleEscape);
        }
    };
    document.addEventListener('keydown', handleEscape);
}

// Add to cart function (can be called from other pages)
function addToCart(itemType, itemId, quantity = 1) {
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('item_type', itemType);
    formData.append('item_id', itemId);
    formData.append('quantity', quantity);

    return fetch('api/cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update button state if on product page
            const addToCartBtn = document.querySelector(`[data-${itemType}-id="${itemId}"]`);
            if (addToCartBtn && addToCartBtn.classList.contains('add-to-cart-btn')) {
                addToCartBtn.classList.add('in-cart');
                addToCartBtn.textContent = 'In cart';
            }
            return data;
        } else {
            throw new Error(data.message || 'Failed to add to cart');
        }
    });
}

