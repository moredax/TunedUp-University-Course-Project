<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/sign-in.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Database connection
require_once __DIR__ . '/../config/database.php';
$mysqli = getDBConnection();

// Fetch all favorites for the user with item details
$favorites_query = "
    SELECT 
        f.id as favorite_id,
        f.item_type,
        f.item_id,
        f.created_at,
        CASE 
            WHEN f.item_type = 'tool' THEN t.name
            WHEN f.item_type = 'sticker' THEN s.name
            WHEN f.item_type = 'color' THEN cc.name
            WHEN f.item_type = 'light' THEN cl.name
        END as item_name,
        CASE 
            WHEN f.item_type = 'tool' THEN t.price
            WHEN f.item_type = 'sticker' THEN s.price
            WHEN f.item_type = 'color' THEN cc.price
            WHEN f.item_type = 'light' THEN cl.price
        END as item_price,
        CASE 
            WHEN f.item_type = 'tool' THEN CONCAT('Tools/', t.image)
            WHEN f.item_type = 'sticker' THEN CONCAT('Stickers/', s.image)
            WHEN f.item_type = 'color' THEN CONCAT('Cars/', cc.image)
            WHEN f.item_type = 'light' THEN CONCAT('Lights/', cl.image)
        END as item_image
    FROM favorites f
    LEFT JOIN tools t ON f.item_type = 'tool' AND f.item_id = t.id
    LEFT JOIN stickers s ON f.item_type = 'sticker' AND f.item_id = s.id
    LEFT JOIN car_colors cc ON f.item_type = 'color' AND f.item_id = cc.id
    LEFT JOIN car_lights cl ON f.item_type = 'light' AND f.item_id = cl.id
    WHERE f.user_id = ?
    ORDER BY f.created_at DESC
";

$stmt = $mysqli->prepare($favorites_query);
$favorites = [];

if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $favorites[] = $row;
    }
    $stmt->close();
}

$mysqli->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="../css/styles.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet" />
</head>
<body>

    <header>
        <div class="header-left">
            <a href="../main.php" class="logo">TunedUp</a>
        </div>
        <div class="nav">
            <a href="../cart.php">Cart</a>
            <a href="profile.php">Profile</a>
        </div>
    </header>

    <div class="sidebar">
        <a href="../colors.php" class="icon" title="Colors">
            <div class="icon-bg"></div>
            <div class="icon-inner"></div>
            <img src="../Icons/palette.svg" class="icon-svg" alt="">
        </a>
        <a href="../lights.php" class="icon" title="Lights">
            <div class="icon-bg"></div>
            <div class="icon-inner"></div>
            <img src="../Icons/lightbulb.svg" class="icon-svg" alt="">
        </a>
        <a href="../tools.php" class="icon" title="Tools">
            <div class="icon-bg"></div>
            <div class="icon-inner"></div>
            <img src="../Icons/nut.svg" class="icon-svg" alt="">
        </a>
        <a href="../stickers.php" class="icon" title="Stickers">
            <div class="icon-bg"></div>
            <div class="icon-inner"></div>
            <img src="../Icons/car.png" class="icon-svg" alt="">
        </a>
    </div>


    <main>

        <section class="recommendations">
            <img src="../Headings/Saved.png" class="heading-saved" alt="SAVED" />
            <div class="recommendation-list-saved">
                <?php if (!empty($favorites)): ?>
                    <?php foreach ($favorites as $favorite): ?>
                        <div class="item product-item" 
                             data-item-type="<?= htmlspecialchars($favorite['item_type']) ?>" 
                             data-item-id="<?= htmlspecialchars($favorite['item_id']) ?>"
                             style="cursor: pointer;">
                            <img src="../<?= htmlspecialchars($favorite['item_image']) ?>" class="image" alt="<?= htmlspecialchars($favorite['item_name']) ?>" />
                            <h3 class="item-name"><?= htmlspecialchars($favorite['item_name']) ?></h3>
                            <div class="item-price-row">
                                <p>$<?= number_format($favorite['item_price'], 2) ?></p>
                                <button class="add-to-cart-btn" 
                                        data-item-type="<?= htmlspecialchars($favorite['item_type']) ?>"
                                        data-item-id="<?= htmlspecialchars($favorite['item_id']) ?>">Add to Cart</button>
                            </div>
                            <div class="corner-circle favorite-btn" 
                                 data-item-type="<?= htmlspecialchars($favorite['item_type']) ?>" 
                                 data-item-id="<?= htmlspecialchars($favorite['item_id']) ?>"
                                 style="cursor: pointer;"
                                 onclick="event.stopPropagation();">
                                <img src="../Icons/heart-fill.svg" class="corner-icon favorite-icon" alt="Favorite" />
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="grid-column: 1 / -1; text-align: center; padding: 2rem; font-size: 1.2rem;">
                        No favorites yet. Start adding items to your favorites!
                    </p>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <!-- Product Modal -->
    <div id="productModal" class="product-modal">
        <div class="product-modal-overlay"></div>
        <div class="product-modal-content">
            <img id="modal-favorite-icon" 
                 src="../Icons/heart.svg" 
                 alt="Favorite" 
                 class="product-modal-favorite" 
                 style="cursor: pointer;" />
            <div class="product-modal-body">
                <div class="image-wrapper">
                    <div class="image-rectangle"></div>
                    <img id="modal-product-image" src="" alt="Product" class="product-image" />
                </div>
                <div class="product-info">
                    <h1 class="title" id="modal-product-title">Product Name</h1>
                    <p class="description" id="modal-product-description">
                        Product description will appear here.
                    </p>
                    <div class="product-row">
                        <p class="price" id="modal-product-price">$0.00</p>
                        <div class="right-group">
                            <div class="cart-quantity">
                                <button class="qty-btn" id="qty-decrease">−</button>
                                <span class="qty-number" id="qty-value">1</span>
                                <button class="qty-btn" id="qty-increase">+</button>
                            </div>
                            <button class="add-to-cart" id="modal-add-to-cart">Add to Cart</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        // Favorites functionality - remove from favorites
        const favoriteButtons = document.querySelectorAll(".favorite-btn");
        
        favoriteButtons.forEach(btn => {
            btn.addEventListener("click", async (e) => {
                e.stopPropagation();
                const itemType = btn.getAttribute("data-item-type");
                const itemId = btn.getAttribute("data-item-id");
                const itemElement = btn.closest(".item");
                
                const formData = new FormData();
                formData.append("action", "remove");
                formData.append("item_type", itemType);
                formData.append("item_id", itemId);
                
                try {
                    const response = await fetch("../api/favorites.php", {
                        method: "POST",
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        // Remove the item from the page
                        if (itemElement) {
                            itemElement.style.transition = "opacity 0.3s";
                            itemElement.style.opacity = "0";
                            setTimeout(() => {
                                itemElement.remove();
                                
                                // Check if no more items, show message
                                const list = document.querySelector(".recommendation-list-saved");
                                if (list && list.children.length === 0) {
                                    list.innerHTML = '<p style="grid-column: 1 / -1; text-align: center; padding: 2rem; font-size: 1.2rem;">No favorites yet. Start adding items to your favorites!</p>';
                                }
                            }, 300);
                        }
                    } else {
                        if (result.requires_login) {
                            showLoginWarning("Please log in to manage favorites", "../auth/sign-in.php");
                        } else {
                            alert(result.message || "An error occurred");
                        }
                    }
                } catch (error) {
                    console.error("Error:", error);
                    alert("An error occurred. Please try again.");
                }
            });
        });

        // Add to cart functionality
        const addToCartButtons = document.querySelectorAll(".add-to-cart-btn");
        addToCartButtons.forEach(btn => {
            let timeoutId = null;
            let isProcessing = false;
            const originalText = btn.textContent;
            
            btn.addEventListener("click", async (e) => {
                e.stopPropagation(); // Prevent modal from opening
                // Prevent multiple clicks
                if (isProcessing || btn.disabled) {
                    return;
                }
                
                const itemType = btn.getAttribute("data-item-type");
                const itemId = btn.getAttribute("data-item-id");
                if (!itemType || !itemId) return;
                
                // Set processing flag and disable button
                isProcessing = true;
                btn.disabled = true;
                
                // Clear any existing timeout
                if (timeoutId) {
                    clearTimeout(timeoutId);
                    timeoutId = null;
                }
                
                try {
                    const formData = new FormData();
                    formData.append('action', 'add');
                    formData.append('item_type', itemType);
                    formData.append('item_id', itemId);
                    formData.append('quantity', 1);
                    
                    const response = await fetch('../api/cart.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        btn.classList.add("in-cart");
                        btn.textContent = "In cart";
                        
                        // Revert button after 1.5 seconds
                        timeoutId = setTimeout(() => {
                            btn.classList.remove("in-cart");
                            btn.textContent = originalText || "Add to Cart";
                            btn.disabled = false;
                            isProcessing = false;
                            timeoutId = null;
                        }, 1500);
                    } else {
                        btn.disabled = false;
                        isProcessing = false;
                        if (data.requires_login) {
                            showLoginWarning("Please log in to add items to cart", "../auth/sign-in.php");
                        } else {
                            alert(data.message || 'Failed to add to cart');
                        }
                    }
                } catch (error) {
                    console.error('Error:', error);
                    if (error instanceof SyntaxError) {
                        alert('Invalid response from server. Please try again.');
                    } else {
                        alert('An error occurred while adding to cart');
                    }
                    btn.disabled = false;
                    isProcessing = false;
                }
            });
        });

        // Product Modal functionality
        let currentProductData = null;
        let modalQuantity = 1;

        function openProductModal(itemType, itemId) {
            const modal = document.getElementById('productModal');
            if (!modal) return;
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
            
            modalQuantity = 1;
            document.getElementById('qty-value').textContent = '1';
            
            document.getElementById('modal-product-title').textContent = 'Loading...';
            document.getElementById('modal-product-description').textContent = 'Please wait...';
            document.getElementById('modal-product-price').textContent = '$0.00';
            document.getElementById('modal-product-image').src = '';
            
            fetch(`../api/product_details.php?item_type=${encodeURIComponent(itemType)}&item_id=${itemId}`)
                .then(async response => {
                    if (!response.ok) {
                        // Try to get error message from response
                        let errorMessage = `HTTP error! status: ${response.status}`;
                        try {
                            const errorData = await response.text();
                            if (errorData) {
                                errorMessage += ` - ${errorData.substring(0, 200)}`;
                            }
                        } catch (e) {
                            // Ignore if we can't read the error
                        }
                        throw new Error(errorMessage);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success && data.product) {
                        currentProductData = data.product;
                        updateModalContent(data.product);
                    } else {
                        alert('Failed to load product details: ' + (data.message || 'Unknown error'));
                        closeProductModal();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    if (error instanceof SyntaxError) {
                        alert('Invalid response from server. Please try again.');
                    } else {
                        alert('An error occurred while loading product details: ' + error.message);
                    }
                    closeProductModal();
                });
        }

        function closeProductModal() {
            const modal = document.getElementById('productModal');
            if (modal) {
                modal.classList.remove('active');
                modal.style.display = '';
            }
            document.body.style.overflow = '';
            currentProductData = null;
            modalQuantity = 1;
        }

        function updateModalContent(product) {
            document.getElementById('modal-product-title').textContent = product.name || 'Product Name';
            document.getElementById('modal-product-description').textContent = product.description || 'No description available.';
            document.getElementById('modal-product-price').textContent = '$' + parseFloat(product.price || 0).toFixed(2);
            
            const imageElement = document.getElementById('modal-product-image');
            if (imageElement && product.image_path) {
                imageElement.src = '../' + product.image_path;
                imageElement.onerror = function() {
                    const parts = product.image_path.split('/');
                    const encodedPath = parts.slice(0, -1).join('/') + '/' + encodeURIComponent(parts[parts.length - 1]);
                    this.src = '../' + encodedPath;
                    this.onerror = function() {
                        this.src = '../placeholder.png';
                    };
                };
            }
            
            const favoriteIcon = document.getElementById('modal-favorite-icon');
            if (favoriteIcon) {
                favoriteIcon.src = product.is_favorite ? '../Icons/heart-fill.svg' : '../Icons/heart.svg';
                favoriteIcon.setAttribute('data-item-type', product.item_type);
                favoriteIcon.setAttribute('data-item-id', product.id);
            }
        }

        // Make product items clickable
        const productItems = document.querySelectorAll(".product-item");
        productItems.forEach(item => {
            item.addEventListener('click', (e) => {
                if (e.target.closest('.add-to-cart-btn') || e.target.closest('.favorite-btn')) {
                    return;
                }
                const itemType = item.getAttribute('data-item-type');
                const itemId = item.getAttribute('data-item-id');
                if (itemType && itemId) {
                    openProductModal(itemType, itemId);
                }
            });
        });

        // Quantity controls
        const qtyIncrease = document.getElementById('qty-increase');
        const qtyDecrease = document.getElementById('qty-decrease');
        if (qtyIncrease) {
            qtyIncrease.addEventListener('click', () => {
                modalQuantity++;
                document.getElementById('qty-value').textContent = modalQuantity;
            });
        }
        if (qtyDecrease) {
            qtyDecrease.addEventListener('click', () => {
                if (modalQuantity > 1) {
                    modalQuantity--;
                    document.getElementById('qty-value').textContent = modalQuantity;
                }
            });
        }

        // Modal add to cart
        const modalAddToCart = document.getElementById('modal-add-to-cart');
        if (modalAddToCart) {
            modalAddToCart.addEventListener('click', async () => {
                if (!currentProductData) return;
                const btn = modalAddToCart;
                const originalText = btn.textContent;
                btn.disabled = true;
                btn.textContent = 'Adding...';
                
                const formData = new FormData();
                formData.append('action', 'add');
                formData.append('item_type', currentProductData.item_type);
                formData.append('item_id', currentProductData.id);
                formData.append('quantity', modalQuantity);
                
                try {
                    const response = await fetch('../api/cart.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();
                    
                    if (data.success) {
                        btn.textContent = 'Added to Cart!';
                        setTimeout(() => {
                            btn.textContent = originalText;
                            btn.disabled = false;
                        }, 1500);
                    } else {
                        btn.disabled = false;
                        btn.textContent = originalText;
                        if (data.requires_login) {
                            showLoginWarning("Please log in to add items to cart", "../auth/sign-in.php");
                        } else {
                            alert(data.message || 'Failed to add to cart');
                        }
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('An error occurred while adding to cart');
                    btn.disabled = false;
                    btn.textContent = originalText;
                }
            });
        }

        // Modal favorite toggle
        const modalFavoriteIcon = document.getElementById('modal-favorite-icon');
        if (modalFavoriteIcon) {
            modalFavoriteIcon.addEventListener('click', async (e) => {
                e.stopPropagation();
                const icon = e.target;
                const itemType = icon.getAttribute('data-item-type');
                const itemId = icon.getAttribute('data-item-id');
                if (!itemType || !itemId) return;
                
                const formData = new FormData();
                formData.append("action", "toggle");
                formData.append("item_type", itemType);
                formData.append("item_id", itemId);
                
                try {
                    const response = await fetch("../api/favorites.php", {
                        method: "POST",
                        body: formData
                    });
                    const result = await response.json();
                    
                    if (result.success) {
                        icon.src = result.is_favorite ? "../Icons/heart-fill.svg" : "../Icons/heart.svg";
                        if (currentProductData) currentProductData.is_favorite = result.is_favorite;
                    } else {
                        if (result.requires_login) {
                            showLoginWarning("Please log in to add favorites", "../auth/sign-in.php");
                        } else {
                            alert(result.message || "An error occurred");
                        }
                    }
                } catch (error) {
                    console.error("Error:", error);
                    alert("An error occurred. Please try again.");
                }
            });
        }

        // Close modal on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeProductModal();
            }
        });

        // Close modal when clicking outside the content
        const productModal = document.getElementById('productModal');
        const productModalContent = document.querySelector('.product-modal-content');
        if (productModal) {
            productModal.addEventListener('click', (e) => {
                // Close if clicking on the modal container itself (not the content)
                if (e.target === productModal || e.target.classList.contains('product-modal-overlay')) {
                    closeProductModal();
                }
            });
            // Prevent clicks inside the content from closing the modal
            if (productModalContent) {
                productModalContent.addEventListener('click', (e) => {
                    e.stopPropagation();
                });
            }
        }
    });

        // Show login warning modal
        function showLoginWarning(message, redirectUrl) {
            const modal = document.getElementById('login-warning-modal');
            const messageEl = document.getElementById('login-warning-message');
            const confirmBtn = document.getElementById('login-warning-confirm-btn');
            const cancelBtn = document.getElementById('login-warning-cancel-btn');
            const closeBtn = document.querySelector('.login-warning-modal-close');
            
            // Set message
            messageEl.textContent = message || 'Please log in to continue';
            
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
                window.location.href = redirectUrl || '../auth/sign-in.php';
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
    </script>

    <!-- Login Warning Modal -->
    <div id="login-warning-modal" class="login-warning-modal">
        <div class="login-warning-modal-content">
            <div class="login-warning-modal-header">
                <h2>Login Required</h2>
                <span class="login-warning-modal-close">&times;</span>
            </div>
            <div class="login-warning-modal-body">
                <div class="login-warning-icon">🔒</div>
                <p class="login-warning-message" id="login-warning-message">Please log in to continue</p>
                <p class="login-warning-note">You need to be logged in to save items to your favorites.</p>
            </div>
            <div class="login-warning-modal-footer">
                <button class="login-warning-modal-btn login-warning-cancel-btn" id="login-warning-cancel-btn">Cancel</button>
                <button class="login-warning-modal-btn login-warning-confirm-btn" id="login-warning-confirm-btn">Go to Login</button>
            </div>
        </div>
    </div>
</body>
</html>

