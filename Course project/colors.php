<?php
session_start();

// Database connection
require_once __DIR__ . '/config/database.php';
$mysqli = getDBConnection();

// Check if user is logged in and get favorite status for colors
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$favorite_colors = [];
if ($user_id) {
    $favorites_query = "SELECT item_id FROM favorites WHERE user_id = ? AND item_type = 'color'";
    $favorites_stmt = $mysqli->prepare($favorites_query);
    if ($favorites_stmt) {
        $favorites_stmt->bind_param("i", $user_id);
        $favorites_stmt->execute();
        $favorites_result = $favorites_stmt->get_result();
        while ($row = $favorites_result->fetch_assoc()) {
            $favorite_colors[] = $row['item_id'];
        }
        $favorites_stmt->close();
    }
}

// Fetch all car colors from database
$colors_query = "SELECT car_colors.*, cars.brand, cars.model FROM car_colors LEFT JOIN cars ON car_colors.car_id = cars.id ORDER BY car_colors.id";
$colors_result = $mysqli->query($colors_query);
$colors = [];
if ($colors_result) {
    while ($row = $colors_result->fetch_assoc()) {
        $colors[] = $row;
    }
}

// Get first color as default (or empty if no colors)
$default_color = !empty($colors) ? $colors[0] : null;

$mysqli->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="css/styles.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet" />
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

    <div class="sidebar">
        <a href="colors.php" class="icon" title="Colors">
            <div class="icon-bg"></div>
            <div class="icon-inner"></div>
            <img src="Icons/palette.svg" class="icon-svg" alt="">
        </a>
        <a href="lights.php" class="icon" title="Lights">
            <div class="icon-bg"></div>
            <div class="icon-inner"></div>
            <img src="Icons/lightbulb.svg" class="icon-svg" alt="">
        </a>
        <a href="tools.php" class="icon" title="Tools">
            <div class="icon-bg"></div>
            <div class="icon-inner"></div>
            <img src="Icons/nut.svg" class="icon-svg" alt="">
        </a>
        <a href="stickers.php" class="icon" title="Stickers">
            <div class="icon-bg"></div>
            <div class="icon-inner"></div>
            <img src="Icons/car.png" class="icon-svg" alt="">
        </a>
    </div>

    <main class="main-content">
        <div class="car-section">
            <div class="car-preview">
                <img src="Headings/Colors.png" class="heading-colors" alt="COLORS" />
                <?php if ($default_color): ?>
                    <img src="Cars/<?= htmlspecialchars($default_color['image']) ?>" alt="<?= htmlspecialchars($default_color['name']) ?> Car" class="car-image" data-color-id="<?= htmlspecialchars($default_color['id']) ?>" />
                <?php else: ?>
                    <img src="Cars/placeholder.png" alt="No colors available" class="car-image" />
                <?php endif; ?>
            </div>

            <div class="color-description">
                <div class="desc-base"></div>
                <div class="desc-white"></div>
                <div class="desc-accent" <?php if ($default_color && isset($default_color['hex_code'])): ?>style="background-color: <?= htmlspecialchars($default_color['hex_code']) ?>;"<?php endif; ?>></div>

                <div class="desc-content">
                    <div class="action-row">
                        <h2 class="color-name"><?= $default_color ? htmlspecialchars($default_color['name']) : 'No Colors Available' ?></h2>
                        <?php if ($default_color): ?>
                            <?php $is_favorite = in_array($default_color['id'], $favorite_colors); ?>
                            <div class="favorite-btn" 
                                 data-item-type="color" 
                                 data-item-id="<?= htmlspecialchars($default_color['id']) ?>"
                                 style="cursor: pointer; display: inline-block;">
                                <img src="Icons/<?= $is_favorite ? 'heart-fill.svg' : 'heart.svg' ?>" 
                                     alt="icon" 
                                     class="corner-svg-2 favorite-icon" />
                            </div>
                        <?php else: ?>
                            <img src="Icons/heart.svg" alt="icon" class="corner-svg-2" />
                        <?php endif; ?>
                    </div>

                    <p class="color-description-text"><?= $default_color ? (isset($default_color['description']) ? htmlspecialchars($default_color['description']) : 'A beautiful color option for your car.') : 'Please add colors in the admin panel.' ?></p>
                    <div class="item-price-row">
                        <h1 class="color-price">$<?= $default_color ? number_format($default_color['price'], 2) : '0.00' ?></h1>
                        <button class="add-to-cart-2" data-color-id="<?= $default_color ? htmlspecialchars($default_color['id']) : '' ?>">Add to Cart</button>
                    </div>
                </div>
            </div>

        </div>

        <div class="color-palette">
            <?php if (!empty($colors)): ?>
                <?php foreach ($colors as $color): ?>
                    <div class="color-option" 
                         style="background-color: <?= htmlspecialchars($color['hex_code'] ?? '#CCCCCC') ?>;" 
                         title="<?= htmlspecialchars($color['name']) ?>" 
                         data-image="Cars/<?= htmlspecialchars($color['image']) ?>"
                         data-color-id="<?= htmlspecialchars($color['id']) ?>"
                         data-name="<?= htmlspecialchars($color['name']) ?>"
                         data-price="<?= htmlspecialchars($color['price']) ?>"
                         data-hex="<?= htmlspecialchars($color['hex_code'] ?? '#CCCCCC') ?>"
                         data-description="<?= htmlspecialchars($color['description'] ?? 'A beautiful color option for your car.') ?>">
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No colors available. Please add colors in the admin panel.</p>
            <?php endif; ?>
        </div>
    </main>

<script>
    (function () {
        const carImageEl = document.querySelector('.car-image');
        const titleEl = document.querySelector('.color-name');
        const descriptionEl = document.querySelector('.color-description-text');
        const priceEl = document.querySelector('.color-price');
        const accentEl = document.querySelector('.desc-accent');
        const addToCartBtn = document.querySelector('.add-to-cart-2');
        const options = Array.from(document.querySelectorAll('.color-option'));

        if (!carImageEl || options.length === 0) return;

        function updateAccent(hexColor) {
            if (!accentEl || !hexColor) return;
            accentEl.style.backgroundColor = hexColor;
        }

        function setActive(optionEl) {
            options.forEach(el => {
                el.style.outline = '';
                el.style.boxShadow = '';
            });
            if (optionEl) {
                optionEl.style.outline = '2px solid #333';
                optionEl.style.boxShadow = '0 0 0 4px rgba(0,0,0,0.08)';
            }
        }

        function updateDisplay(optionEl) {
            if (!optionEl) return;
            
            const imgPath = optionEl.getAttribute('data-image');
            const title = optionEl.getAttribute('data-name');
            const price = optionEl.getAttribute('data-price');
            const hex = optionEl.getAttribute('data-hex');
            const description = optionEl.getAttribute('data-description');
            const colorId = optionEl.getAttribute('data-color-id');

            if (imgPath && carImageEl) {
                carImageEl.src = imgPath;
                carImageEl.setAttribute('data-color-id', colorId);
            }
            if (title && titleEl) {
                titleEl.textContent = title;
            }
            if (price && priceEl) {
                priceEl.textContent = '$' + parseFloat(price).toFixed(2);
            }
            if (description && descriptionEl) {
                descriptionEl.textContent = description;
            }
            if (hex) {
                updateAccent(hex);
            }
            if (addToCartBtn && colorId) {
                addToCartBtn.setAttribute('data-color-id', colorId);
            }
        }

        options.forEach((opt) => {
            opt.addEventListener('click', () => {
                updateDisplay(opt);
                setActive(opt);
            });
        });

        // Initialize active state to the first color option
        if (options.length > 0) {
            const firstOption = options[0];
            updateDisplay(firstOption);
            setActive(firstOption);
        }

        // Update favorite button when color changes
        const favoriteBtn = document.querySelector('.favorite-btn');
        const favoriteIcon = document.querySelector('.favorite-icon');
        
        if (favoriteBtn && favoriteIcon) {
            // Store original updateDisplay function
            const originalUpdateDisplay = updateDisplay;
            
            // Override updateDisplay to also update favorite button
            updateDisplay = function(optionEl) {
                originalUpdateDisplay(optionEl);
                if (optionEl && favoriteBtn) {
                    const colorId = optionEl.getAttribute('data-color-id');
                    if (colorId) {
                        favoriteBtn.setAttribute('data-item-id', colorId);
                        // Check favorite status for this color
                        checkFavoriteStatus('color', colorId);
                    }
                }
            };
            
            // Check favorite status function
            async function checkFavoriteStatus(itemType, itemId) {
                try {
                    const response = await fetch(`api/favorites.php?action=check&item_type=${itemType}&item_id=${itemId}`);
                    const result = await response.json();
                    if (result.success && favoriteIcon) {
                        favoriteIcon.src = result.is_favorite ? "Icons/heart-fill.svg" : "Icons/heart.svg";
                    }
                } catch (error) {
                    console.error("Error checking favorite status:", error);
                }
            }
            
            // Favorite button click handler
            favoriteBtn.addEventListener('click', async (e) => {
                e.stopPropagation();
                const itemType = favoriteBtn.getAttribute("data-item-type");
                const itemId = favoriteBtn.getAttribute("data-item-id");
                
                if (!itemId) return;
                
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
                            favoriteIcon.src = "Icons/heart-fill.svg";
                        } else {
                            favoriteIcon.src = "Icons/heart.svg";
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
            
            // Check initial favorite status
            const initialColorId = carImageEl ? carImageEl.getAttribute('data-color-id') : null;
            if (initialColorId) {
                checkFavoriteStatus('color', initialColorId);
            }
        }
    })();
</script>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const btn = document.querySelector(".add-to-cart-2");

        if (btn) {
            let timeoutId = null;
            let isProcessing = false;
            const originalText = btn.textContent;
            
            btn.addEventListener("click", async () => {
                // Prevent multiple clicks
                if (isProcessing || btn.disabled) {
                    return;
                }
                
                const colorId = btn.getAttribute("data-color-id");
                if (!colorId) return;
                
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
                    formData.append('item_type', 'color');
                    formData.append('item_id', colorId);
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
                        alert(data.message || 'Failed to add to cart');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('An error occurred while adding to cart');
                    btn.disabled = false;
                    isProcessing = false;
                }
            });
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

