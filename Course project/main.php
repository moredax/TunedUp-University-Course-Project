<?php
session_start();

// Database connection
require_once __DIR__ . '/config/database.php';
$mysqli = getDBConnection();

// Check if user is logged in and get favorite status
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$favorites = [];
if ($user_id) {
    $favorites_query = "SELECT item_type, item_id FROM favorites WHERE user_id = ?";
    $favorites_stmt = $mysqli->prepare($favorites_query);
    if ($favorites_stmt) {
        $favorites_stmt->bind_param("i", $user_id);
        $favorites_stmt->execute();
        $favorites_result = $favorites_stmt->get_result();
        while ($row = $favorites_result->fetch_assoc()) {
            $favorites[$row['item_type'] . '_' . $row['item_id']] = true;
        }
        $favorites_stmt->close();
    }
}

// Fetch recent products from tools and stickers only
// Get 6 most recent items total, mixing tools and stickers
$all_products = [];

// Fetch tools
$tools_query = "SELECT 'tool' as item_type, id, name, image, price, NULL as created_at FROM tools ORDER BY id DESC LIMIT 10";
$tools_result = $mysqli->query($tools_query);
if ($tools_result) {
    while ($row = $tools_result->fetch_assoc()) {
        $all_products[] = $row;
    }
} else {
    // Log error but continue
    error_log("Tools query error: " . $mysqli->error);
}

// Fetch stickers
$stickers_query = "SELECT 'sticker' as item_type, id, name, image, price, NULL as created_at FROM stickers ORDER BY id DESC LIMIT 10";
$stickers_result = $mysqli->query($stickers_query);
if ($stickers_result) {
    while ($row = $stickers_result->fetch_assoc()) {
        $all_products[] = $row;
    }
} else {
    error_log("Stickers query error: " . $mysqli->error);
}

// Shuffle and take first 6
shuffle($all_products);
$new_products = array_slice($all_products, 0, 6);

// Fetch recent reviews with photos for USER REVIEWS section
$reviews_query = "
    SELECT 
        r.id,
        r.item_type,
        r.item_id,
        r.rating,
        r.comment,
        r.photo,
        r.created_at,
        u.name as user_name,
        CASE 
            WHEN r.item_type = 'tool' THEN t.name
            WHEN r.item_type = 'sticker' THEN s.name
            WHEN r.item_type = 'color' THEN cc.name
            WHEN r.item_type = 'light' THEN cl.name
        END as item_name
    FROM reviews r
    INNER JOIN users u ON r.user_id = u.id
    LEFT JOIN tools t ON r.item_type = 'tool' AND r.item_id = t.id
    LEFT JOIN stickers s ON r.item_type = 'sticker' AND r.item_id = s.id
    LEFT JOIN car_colors cc ON r.item_type = 'color' AND r.item_id = cc.id
    LEFT JOIN car_lights cl ON r.item_type = 'light' AND r.item_id = cl.id
    WHERE r.photo IS NOT NULL AND r.photo != ''
    ORDER BY r.created_at DESC
    LIMIT 3
";

$reviews_result = $mysqli->query($reviews_query);
$reviews = [];
if ($reviews_result) {
    while ($row = $reviews_result->fetch_assoc()) {
        $reviews[] = $row;
    }
} else {
    // If query fails, set empty array to prevent errors
    $reviews = [];
    error_log("Reviews query error: " . $mysqli->error);
}

// Fetch all cars for dropdown
$cars_query = "SELECT * FROM cars ORDER BY brand, model, series";
$cars_result = $mysqli->query($cars_query);
$cars = [];
$brands = [];
if ($cars_result) {
    while ($row = $cars_result->fetch_assoc()) {
        $cars[] = $row;
        if (!in_array($row['brand'], $brands)) {
            $brands[] = $row['brand'];
        }
    }
} else {
    // If query fails, set empty arrays to prevent errors
    $cars = [];
    $brands = [];
}

$mysqli->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="css/main.css" />
</head>
<body>
    <header>
        <div class="header-left">
            <a href="main.php" class="logo">TunedUp</a>
        </div>
        <div class="nav">
            <a href="cart.php">Cart</a>
            <a href="user/saved.php">Saved</a>
            <a href="user/profile.php">Profile</a>
        </div>
    </header>

    <main>
        <section class="hero">
            <div class="hero-content">
                <h1 class="hero-title">TunedUp</h1>
                <p class="hero-subtitle">YOUR CAR - YOUR STYLE</p>
                <p class="hero-text">
                    Customize your car with adjustable colors, stickers and lightings, then add your favorites
                    to the cart or wishlist for easy ordering.
                </p>
                <a href="colors.php" class="btn primary-btn">TUNE NOW</a>
            </div>
            <div class="hero-car-wrapper">
                <img src="Main images/car_top_view.png" alt="Car" class="hero-car-image" />
            </div>
            <div class="hero-sidebar">
                <a href="colors.php" class="hero-tile">
                    <img src="Main images/palette_ICON.png" alt="Colors" class="hero-tile-icon-img" />
                    <div class="hero-tile-label">Colors</div>
                </a>
                <a href="stickers.php" class="hero-tile">
                    <img src="Main images/smile_ICON.png" alt="Stickers" class="hero-tile-icon-img" />
                    <div class="hero-tile-label">Stikers</div>
                </a>
                <a href="lights.php" class="hero-tile">
                    <img src="Main images/lightbulb_ICON.png" alt="Lights" class="hero-tile-icon-img" />
                    <div class="hero-tile-label">Lights</div>
                </a>
                <a href="tools.php" class="hero-tile">
                    <img src="Main images/nut_ICON.png" alt="Tools" class="hero-tile-icon-img" />
                    <div class="hero-tile-label">Tools</div>
                </a>
            </div>
        </section>

        <section class="select-car">
            <h2 class="section-title small">SELECT YOUR CAR</h2>
            <form class="select-car-form">
                <div class="select-group">
                    <label for="make-select">Brand</label>
                    <select id="make-select" name="make">
                        <option value="" selected>Any</option>
                        <?php foreach ($brands as $brand): ?>
                            <option value="<?= htmlspecialchars(strtolower($brand)) ?>"><?= htmlspecialchars(strtoupper($brand)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="select-group">
                    <label for="model-select">Model</label>
                    <select id="model-select" name="model" disabled>
                        <option value="">Any</option>
                    </select>
                </div>
                <div class="select-group">
                    <label for="series-select">Series</label>
                    <select id="series-select" name="series" disabled>
                        <option value="">Any</option>
                    </select>
                </div>
                <button type="button" class="btn secondary-btn" id="find-btn">Find</button>
            </form>
        </section>

        <section class="new-products">
            <img src="Main images/NEW.png" alt="NEW" class="section-label" />
            <div class="recommendation-list">
                <?php if (!empty($new_products)): ?>
                    <?php foreach ($new_products as $product): ?>
                        <?php 
                        $item_key = $product['item_type'] . '_' . $product['id'];
                        $is_favorite = isset($favorites[$item_key]);
                        
                        // Determine image path based on item type
                        $image_path = '';
                        if ($product['item_type'] === 'tool') {
                            $image_path = 'Tools/' . htmlspecialchars($product['image']);
                        } elseif ($product['item_type'] === 'sticker') {
                            $image_path = 'Stickers/' . htmlspecialchars($product['image']);
                        }
                        ?>
                        <div class="item product-item" 
                             data-item-type="<?= htmlspecialchars($product['item_type']) ?>" 
                             data-item-id="<?= htmlspecialchars($product['id']) ?>"
                             style="cursor: pointer;">
                            <img src="<?= $image_path ?>" class="image" alt="<?= htmlspecialchars($product['name']) ?>" onerror="this.src='placeholder.png'" />
                            <h3 class="item-name"><?= htmlspecialchars($product['name']) ?></h3>
                            <div class="item-price-row">
                                <p>$<?= number_format($product['price'], 2) ?></p>
                                <button class="add-to-cart-btn" 
                                        data-item-type="<?= htmlspecialchars($product['item_type']) ?>" 
                                        data-item-id="<?= htmlspecialchars($product['id']) ?>"
                                        onclick="event.stopPropagation();">
                                    Add to Cart
                                </button>
                            </div>
                            <div class="product-badge">NEW</div>
                            <div class="corner-circle favorite-btn" 
                                 data-item-type="<?= htmlspecialchars($product['item_type']) ?>" 
                                 data-item-id="<?= htmlspecialchars($product['id']) ?>"
                                 style="cursor: pointer;"
                                 onclick="event.stopPropagation();">
                                <img src="Icons/<?= $is_favorite ? 'heart-fill.svg' : 'heart.svg' ?>" 
                                     class="corner-icon favorite-icon" 
                                     alt="Favorite" />
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="grid-column: 1 / -1; text-align: center; padding: 2rem;">No products available. Please add products in the admin panel.</p>
                <?php endif; ?>
            </div>

            <div class="center-btn-wrapper">
                <a href="tools.php" class="btn secondary-btn wide">ALL NEW PRODUCTS</a>
            </div>
        </section>

        <section class="gallery">
            <h2 class="section-title">USER REVIEWS</h2>
            <p class="section-subtitle">All Recent Content From Our Clients</p>
            <div class="cards-row gallery-row">
                <?php if (!empty($reviews)): ?>
                    <?php foreach ($reviews as $review): ?>
                        <article class="gallery-card">
                            <div class="gallery-image" style="background-image: url('<?= htmlspecialchars($review['photo']) ?>'); background-size: cover; background-position: center; background-repeat: no-repeat;"></div>
                            <div class="gallery-body">
                                <div class="gallery-title"><?= htmlspecialchars($review['item_name']) ?></div>
                                <div class="gallery-text">
                                    <div style="margin-bottom: 8px;">
                                        <?php 
                                        $rating = (int)$review['rating'];
                                        for ($i = 1; $i <= 5; $i++): 
                                        ?>
                                            <span style="color: <?= $i <= $rating ? '#ffc107' : '#ddd' ?>;">★</span>
                                        <?php endfor; ?>
                                        <span style="margin-left: 8px; color: #666;"><?= $rating ?>/5</span>
                                    </div>
                                    <?php if (!empty($review['comment'])): ?>
                                        <div style="margin-top: 8px;"><?= htmlspecialchars($review['comment']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="gallery-date">
                                    <?= htmlspecialchars($review['user_name']) ?> • <?= date('M j, Y', strtotime($review['created_at'])) ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="grid-column: 1 / -1; text-align: center; padding: 2rem; color: #666;">No reviews with photos yet. Be the first to share your experience!</p>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="footer-inner">
            <div class="footer-col">
                <div class="footer-logo">TunedUp</div>
                <div class="footer-contact">
                    <div>+37067996245</div>
                    <div>info@tunedup.lt</div>
                </div>
                <p class="footer-note">
                    English calls only. For other languages, please message us on WhatsApp.<br>
                    Mon–Fri 09:30–17:00 (GMT+2/3)
                </p>
                <form class="footer-newsletter">
                    <label for="newsletter" class="newsletter-label">Your email address</label>
                    <div class="newsletter-input-wrapper">
                        <input id="newsletter" type="email" placeholder="Your email address" />
                        <button type="button" class="newsletter-btn">→</button>
                    </div>
                </form>
                <p class="footer-terms">
                    I agree to the terms and conditions and the privacy policy.
                </p>
            </div>

            <div class="footer-col">
                <div class="footer-heading">CATEGORIES</div>
                <ul class="footer-links">
                    <li><a href="lights.php">LIGHTS</a></li>
                    <li><a href="colors.php">COLORS</a></li>
                    <li><a href="tools.php">TOOLS</a></li>
                    <li><a href="stickers.php">STICKERS</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <p class="footer-description">
                    The items shown in this online shop are not original parts. All reference and
                    original numbers provided are for comparison purposes only. Spare parts of
                    equivalent quality for the owners of the vehicle (according to EU Regulation 46/2010).
                </p>
            </div>
        </div>
    </footer>

    <!-- Product Modal -->
    <div id="productModal" class="product-modal">
        <div class="product-modal-overlay" onclick="closeProductModal()"></div>
        <div class="product-modal-content">
            <img id="modal-favorite-icon" 
                 src="Icons/heart.svg" 
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

    <script src="main.js"></script>
    <script>
        // Show login warning modal - Global function
        function showLoginWarning(message, redirectUrl) {
            const modal = document.getElementById('login-warning-modal');
            if (!modal) {
                console.error('Login warning modal not found');
                return;
            }
            
            const messageEl = document.getElementById('login-warning-message');
            const confirmBtn = document.getElementById('login-warning-confirm-btn');
            const cancelBtn = document.getElementById('login-warning-cancel-btn');
            const closeBtn = document.querySelector('.login-warning-modal-close');
            
            if (!messageEl || !confirmBtn || !cancelBtn || !closeBtn) {
                console.error('Login warning modal elements not found');
                return;
            }
            
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
                window.location.href = redirectUrl || 'auth/sign-in.php';
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
    <script>
        // Product Modal functionality
        let currentProductData = null;
        let modalQuantity = 1;

        function openProductModal(itemType, itemId) {
            console.log('Opening modal for:', itemType, itemId);
            const modal = document.getElementById('productModal');
            if (!modal) {
                console.error('Modal element not found!');
                return;
            }
            // Force display
            // Add active class (CSS will handle display)
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
            console.log('Modal opened');
            
            // Reset quantity
            modalQuantity = 1;
            document.getElementById('qty-value').textContent = '1';
            
            // Show loading state
            document.getElementById('modal-product-title').textContent = 'Loading...';
            document.getElementById('modal-product-description').textContent = 'Please wait...';
            document.getElementById('modal-product-price').textContent = '$0.00';
            document.getElementById('modal-product-image').src = '';
            
            // Fetch product details
            console.log('Fetching product details for:', itemType, itemId);
            fetch(`api/product_details.php?item_type=${encodeURIComponent(itemType)}&item_id=${itemId}`)
                .then(response => {
                    console.log('Response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Received data:', data);
                    if (data.success && data.product) {
                        currentProductData = data.product;
                        updateModalContent(data.product);
                    } else {
                        console.error('API returned error:', data.message);
                        alert('Failed to load product details: ' + (data.message || 'Unknown error'));
                        closeProductModal();
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    alert('An error occurred while loading product details: ' + error.message);
                    closeProductModal();
                });
        }

        function closeProductModal() {
            const modal = document.getElementById('productModal');
            const modalContent = document.querySelector('.product-modal-content');
            const modalBody = document.querySelector('.product-modal-body');
            
            if (modal) {
                modal.classList.remove('active');
                // Reset inline styles to prevent layout issues
                modal.style.display = '';
                modal.style.visibility = '';
                modal.style.opacity = '';
            }
            
            // Reset modal content styles
            if (modalContent) {
                modalContent.style.display = '';
                modalContent.style.visibility = '';
            }
            
            if (modalBody) {
                modalBody.style.display = '';
            }
            
            document.body.style.overflow = '';
            currentProductData = null;
            modalQuantity = 1;
        }

        function updateModalContent(product) {
            console.log('Updating modal with product:', product);
            
            // Update text content
            const titleEl = document.getElementById('modal-product-title');
            const descEl = document.getElementById('modal-product-description');
            const priceEl = document.getElementById('modal-product-price');
            
            if (titleEl) titleEl.textContent = product.name || 'Product Name';
            if (descEl) descEl.textContent = product.description || 'No description available.';
            if (priceEl) priceEl.textContent = '$' + parseFloat(product.price || 0).toFixed(2);
            
            // Set image with proper error handling
            const imageElement = document.getElementById('modal-product-image');
            if (imageElement && product.image_path) {
                // Use the image path directly (browsers handle spaces in URLs)
                imageElement.src = product.image_path;
                imageElement.style.display = 'block';
                imageElement.style.visibility = 'visible';
                imageElement.style.opacity = '1';
                
                imageElement.onerror = function() {
                    console.error('Failed to load image:', product.image_path);
                    // Try with encoded filename
                    const parts = product.image_path.split('/');
                    const encodedPath = parts.slice(0, -1).join('/') + '/' + encodeURIComponent(parts[parts.length - 1]);
                    this.src = encodedPath;
                    this.onerror = function() {
                        console.error('Failed to load image with encoded path too');
                        this.src = 'placeholder.png';
                    };
                };
                imageElement.onload = function() {
                    console.log('Image loaded successfully:', product.image_path);
                };
            } else {
                console.error('No image_path in product data or image element not found');
                if (imageElement) {
                    imageElement.src = 'placeholder.png';
                }
            }
            
            // Update favorite icon (now positioned in top right corner)
            const favoriteIcon = document.getElementById('modal-favorite-icon');
            if (favoriteIcon) {
                favoriteIcon.src = product.is_favorite ? 'Icons/heart-fill.svg' : 'Icons/heart.svg';
                favoriteIcon.setAttribute('data-item-type', product.item_type);
                favoriteIcon.setAttribute('data-item-id', product.id);
            }
            
            // Modal visibility is handled by CSS via .active class
            // No need to set inline styles
        }

        // Make product items clickable and set up modal event listeners
        document.addEventListener("DOMContentLoaded", () => {
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
            
            const btn = document.getElementById('modal-add-to-cart');
            const originalText = btn.textContent;
            btn.disabled = true;
            btn.textContent = 'Adding...';
            
            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('item_type', currentProductData.item_type);
            formData.append('item_id', currentProductData.id);
            formData.append('quantity', modalQuantity);
            
            try {
                const response = await fetch('api/cart.php', {
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
                        showLoginWarning("Please log in to add items to cart", "auth/sign-in.php");
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

            // Modal favorite toggle (icon in top right corner)
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
                        const response = await fetch("api/favorites.php", {
                            method: "POST",
                            body: formData
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            if (result.is_favorite) {
                                icon.src = "Icons/heart-fill.svg";
                                if (currentProductData) currentProductData.is_favorite = true;
                            } else {
                                icon.src = "Icons/heart.svg";
                                if (currentProductData) currentProductData.is_favorite = false;
                            }
                        } else {
                            if (result.requires_login) {
                                showLoginWarning("Please log in to add favorites", "auth/sign-in.php");
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

            // Make product items clickable
            const productItems = document.querySelectorAll(".product-item");
            console.log('Found product items:', productItems.length);
            productItems.forEach(item => {
                item.addEventListener('click', (e) => {
                    // Don't open modal if clicking on buttons
                    if (e.target.closest('.add-to-cart-btn') || e.target.closest('.favorite-btn')) {
                        console.log('Click on button, ignoring');
                        return;
                    }
                    const itemType = item.getAttribute('data-item-type');
                    const itemId = item.getAttribute('data-item-id');
                    console.log('Product item clicked:', itemType, itemId);
                    if (itemType && itemId) {
                        openProductModal(itemType, itemId);
                    } else {
                        console.error('Missing item type or ID:', itemType, itemId);
                    }
                });
            });
        });

        // Close modal on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeProductModal();
            }
        });

        // Add to cart functionality (for non-modal buttons)
        document.addEventListener("DOMContentLoaded", () => {
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
                        
                        const response = await fetch('api/cart.php', {
                            method: 'POST',
                            body: formData
                        });
                        
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
                                showLoginWarning("Please log in to add items to cart", "auth/sign-in.php");
                            } else {
                                alert(data.message || 'Failed to add to cart');
                            }
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        alert('An error occurred while adding to cart');
                        btn.disabled = false;
                        isProcessing = false;
                    }
                });
            });

            // Favorites functionality
            const favoriteButtons = document.querySelectorAll(".favorite-btn");
            
            favoriteButtons.forEach(btn => {
                btn.addEventListener("click", async (e) => {
                    e.stopPropagation();
                    const itemType = btn.getAttribute("data-item-type");
                    const itemId = btn.getAttribute("data-item-id");
                    const icon = btn.querySelector(".favorite-icon");
                    
                    if (!itemType || !itemId) {
                        console.error("Missing item type or ID");
                        return;
                    }
                    
                    if (!icon) {
                        console.error("Favorite icon not found");
                        return;
                    }
                    
                    const formData = new FormData();
                    formData.append("action", "toggle");
                    formData.append("item_type", itemType);
                    formData.append("item_id", itemId);
                    
                    try {
                        const response = await fetch("api/favorites.php", {
                            method: "POST",
                            body: formData
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            // Update icon based on favorite status
                            if (result.is_favorite) {
                                icon.src = "Icons/heart-fill.svg";
                            } else {
                                icon.src = "Icons/heart.svg";
                            }
                        } else {
                            if (result.requires_login) {
                                showLoginWarning("Please log in to add favorites", "auth/sign-in.php");
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
        });
        
        // Car selection form functionality
        const carMakeSelect = document.getElementById('make-select');
        const carModelSelect = document.getElementById('model-select');
        const carSeriesSelect = document.getElementById('series-select');
        
        if (carMakeSelect && carModelSelect && carSeriesSelect) {
            // Initialize - model and series are disabled by default
            carModelSelect.disabled = true;
            carSeriesSelect.disabled = true;
            
            // When brand changes
            carMakeSelect.addEventListener('change', async () => {
                const brand = carMakeSelect.value;
                
                if (!brand) {
                    carModelSelect.disabled = true;
                    carModelSelect.innerHTML = '<option value="">Any</option>';
                    carSeriesSelect.disabled = true;
                    carSeriesSelect.innerHTML = '<option value="">Any</option>';
                    return;
                }
                
                // Fetch models for selected brand
                try {
                    const response = await fetch(`api/car_filters.php?action=models&brand=${encodeURIComponent(brand)}`);
                    const data = await response.json();
                    
                    if (data.success && data.models.length > 0) {
                        carModelSelect.disabled = false;
                        carModelSelect.innerHTML = '<option value="">Any</option>';
                        data.models.forEach(model => {
                            const option = document.createElement('option');
                            option.value = model;
                            option.textContent = model.toUpperCase();
                            carModelSelect.appendChild(option);
                        });
                    } else {
                        carModelSelect.disabled = true;
                        carModelSelect.innerHTML = '<option value="">Any</option>';
                    }
                    
                    // Reset series
                    carSeriesSelect.disabled = true;
                    carSeriesSelect.innerHTML = '<option value="">Any</option>';
                } catch (error) {
                    console.error('Error fetching models:', error);
                    carModelSelect.disabled = true;
                    carModelSelect.innerHTML = '<option value="">Any</option>';
                }
            });
            
            // When model changes
            carModelSelect.addEventListener('change', async () => {
                const brand = carMakeSelect.value;
                const model = carModelSelect.value;
                
                if (!brand || !model) {
                    carSeriesSelect.disabled = true;
                    carSeriesSelect.innerHTML = '<option value="">Any</option>';
                    return;
                }
                
                // Fetch series for selected brand and model
                try {
                    const response = await fetch(`api/car_filters.php?action=series&brand=${encodeURIComponent(brand)}&model=${encodeURIComponent(model)}`);
                    const data = await response.json();
                    
                    if (data.success && data.series.length > 0) {
                        carSeriesSelect.disabled = false;
                        carSeriesSelect.innerHTML = '<option value="">Any</option>';
                        data.series.forEach(serie => {
                            const option = document.createElement('option');
                            option.value = serie;
                            option.textContent = serie.toUpperCase();
                            carSeriesSelect.appendChild(option);
                        });
                    } else {
                        carSeriesSelect.disabled = true;
                        carSeriesSelect.innerHTML = '<option value="">Any</option>';
                    }
                } catch (error) {
                    console.error('Error fetching series:', error);
                    carSeriesSelect.disabled = true;
                    carSeriesSelect.innerHTML = '<option value="">Any</option>';
                }
            });
            
            // Find button functionality
            const findBtn = document.getElementById('find-btn');
            if (findBtn) {
                findBtn.addEventListener('click', () => {
                    const brand = carMakeSelect.value;
                    const model = carModelSelect.value;
                    const series = carSeriesSelect.value;
                    
                    // Build URL with parameters
                    const params = new URLSearchParams();
                    if (brand) params.append('brand', brand);
                    if (model) params.append('model', model);
                    if (series) params.append('series', series);
                    
                    const queryString = params.toString();
                    const url = queryString ? `tools.php?${queryString}` : 'tools.php';
                    window.location.href = url;
                });
            }
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

