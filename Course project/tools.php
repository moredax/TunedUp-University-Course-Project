<?php
session_start();

// Database connection
require_once __DIR__ . '/config/database.php';
$mysqli = getDBConnection();

// Check if user is logged in and get favorite status for tools
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$favorite_tools = [];
if ($user_id) {
    $favorites_query = "SELECT item_id FROM favorites WHERE user_id = ? AND item_type = 'tool'";
    $favorites_stmt = $mysqli->prepare($favorites_query);
    if ($favorites_stmt) {
        $favorites_stmt->bind_param("i", $user_id);
        $favorites_stmt->execute();
        $favorites_result = $favorites_stmt->get_result();
        while ($row = $favorites_result->fetch_assoc()) {
            $favorite_tools[] = $row['item_id'];
        }
        $favorites_stmt->close();
    }
}

// Get filter parameters from GET request
$car_id = isset($_GET['car_id']) ? (int)$_GET['car_id'] : null;
$brand_param = isset($_GET['brand']) ? $mysqli->real_escape_string($_GET['brand']) : null;
$model_param = isset($_GET['model']) ? $mysqli->real_escape_string($_GET['model']) : null;
$series_param = isset($_GET['series']) ? $mysqli->real_escape_string($_GET['series']) : null;

// Fetch all cars for dropdowns
$cars_query = "SELECT * FROM cars ORDER BY brand, model, series";
$cars_result = $mysqli->query($cars_query);
$cars = [];
$brands = [];
$models = [];
$series_list = [];

if ($cars_result) {
    while ($row = $cars_result->fetch_assoc()) {
        $cars[] = $row;
        if (!in_array($row['brand'], $brands)) {
            $brands[] = $row['brand'];
        }
    }
}

// Normalize brand, model, and series to match database values (case-insensitive matching)
$brand = null;
$model = null;
$series = null;

if ($brand_param) {
    // Find the actual brand from database that matches (case-insensitive)
    $brand_query = "SELECT DISTINCT brand FROM cars WHERE LOWER(brand) = LOWER('$brand_param') LIMIT 1";
    $brand_result = $mysqli->query($brand_query);
    if ($brand_result && $brand_row = $brand_result->fetch_assoc()) {
        $brand = $brand_row['brand'];
    }
}

if ($brand && $model_param) {
    // Find the actual model from database that matches (case-insensitive)
    $brand_escaped = $mysqli->real_escape_string($brand);
    $model_query = "SELECT DISTINCT model FROM cars WHERE LOWER(brand) = LOWER('$brand_escaped') AND LOWER(model) = LOWER('$model_param') LIMIT 1";
    $model_result = $mysqli->query($model_query);
    if ($model_result && $model_row = $model_result->fetch_assoc()) {
        $model = $model_row['model'];
    }
}

if ($brand && $model && $series_param) {
    // Find the actual series from database that matches (case-insensitive)
    $brand_escaped = $mysqli->real_escape_string($brand);
    $model_escaped = $mysqli->real_escape_string($model);
    $series_query = "SELECT DISTINCT series FROM cars WHERE LOWER(brand) = LOWER('$brand_escaped') AND LOWER(model) = LOWER('$model_escaped') AND LOWER(series) = LOWER('$series_param') LIMIT 1";
    $series_result = $mysqli->query($series_query);
    if ($series_result && $series_row = $series_result->fetch_assoc()) {
        $series = $series_row['series'];
    }
}

// Build tools query with optional car filtering
if ($car_id) {
    // Filter by specific car_id
    $tools_query = "SELECT DISTINCT tools.* FROM tools 
                    INNER JOIN car_tools ON tools.id = car_tools.tool_id 
                    WHERE car_tools.car_id = $car_id 
                    ORDER BY tools.id";
} elseif ($brand || $model || $series) {
    // Filter by brand/model/series
    $tools_query = "SELECT DISTINCT tools.* FROM tools 
                    INNER JOIN car_tools ON tools.id = car_tools.tool_id 
                    INNER JOIN cars ON car_tools.car_id = cars.id 
                    WHERE 1=1";
    if ($brand) {
        $brand_escaped = $mysqli->real_escape_string($brand);
        $tools_query .= " AND LOWER(cars.brand) = LOWER('$brand_escaped')";
    }
    if ($model) {
        $model_escaped = $mysqli->real_escape_string($model);
        $tools_query .= " AND LOWER(cars.model) = LOWER('$model_escaped')";
    }
    if ($series) {
        $series_escaped = $mysqli->real_escape_string($series);
        $tools_query .= " AND LOWER(cars.series) = LOWER('$series_escaped')";
    }
    $tools_query .= " ORDER BY tools.id";
} else {
    // Show all tools if no filter
    $tools_query = "SELECT * FROM tools ORDER BY tools.id";
}

$tools_result = $mysqli->query($tools_query);
$tools = [];
if ($tools_result) {
    while ($row = $tools_result->fetch_assoc()) {
        $tools[] = $row;
    }
}

// Get models for selected brand
$filtered_models = [];
if ($brand) {
    $brand_escaped = $mysqli->real_escape_string($brand);
    $models_query = "SELECT DISTINCT model FROM cars WHERE LOWER(brand) = LOWER('$brand_escaped') ORDER BY model";
    $models_result = $mysqli->query($models_query);
    if ($models_result) {
        while ($row = $models_result->fetch_assoc()) {
            $filtered_models[] = $row['model'];
        }
    }
}

// Get series for selected brand and model
$filtered_series = [];
if ($brand && $model) {
    $brand_escaped = $mysqli->real_escape_string($brand);
    $model_escaped = $mysqli->real_escape_string($model);
    $series_query = "SELECT DISTINCT series FROM cars WHERE LOWER(brand) = LOWER('$brand_escaped') AND LOWER(model) = LOWER('$model_escaped') AND series IS NOT NULL AND series != '' ORDER BY series";
    $series_result = $mysqli->query($series_query);
    if ($series_result) {
        while ($row = $series_result->fetch_assoc()) {
            $filtered_series[] = $row['series'];
        }
    }
}

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


    <main>

        <section class="recommendations">
            <img src="Headings/Tools.png" class="heading-tools" alt="TOOLS" />

            <section class="select-car">
                <h2 class="section-title small">SELECT YOUR CAR</h2>
                <form class="select-car-form" method="GET" action="tools.php">
                    <div class="select-group">
                        <label for="brand-select">Brand</label>
                        <select id="brand-select" name="brand">
                            <option value="" <?= !$brand ? 'selected' : '' ?>>Any</option>
                            <?php foreach ($brands as $b): ?>
                                <option value="<?= htmlspecialchars($b) ?>" <?= $brand === $b ? 'selected' : '' ?>>
                                    <?= htmlspecialchars(strtoupper($b)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="select-group">
                        <label for="model-select">Model</label>
                        <select id="model-select" name="model" <?= !$brand ? 'disabled' : '' ?>>
                            <option value="" <?= !$model ? 'selected' : '' ?>>Any</option>
                            <?php if ($brand): ?>
                                <?php foreach ($filtered_models as $m): ?>
                                    <option value="<?= htmlspecialchars($m) ?>" <?= $model === $m ? 'selected' : '' ?>>
                                        <?= htmlspecialchars(strtoupper($m)) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="select-group">
                        <label for="series-select">Series</label>
                        <select id="series-select" name="series" <?= !$model ? 'disabled' : '' ?>>
                            <option value="" <?= !$series ? 'selected' : '' ?>>Any</option>
                            <?php if ($brand && $model): ?>
                                <?php foreach ($filtered_series as $s): ?>
                                    <option value="<?= htmlspecialchars($s) ?>" <?= $series === $s ? 'selected' : '' ?>>
                                        <?= htmlspecialchars(strtoupper($s)) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn secondary-btn">Find</button>
                </form>
                <?php if ($brand || $model || $series): ?>
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="tools.php" class="btn secondary-btn">Clear</a>
                    </div>
                <?php endif; ?>
            </section>

            <div class="recommendation-list">
                <?php if (!empty($tools)): ?>
                    <?php foreach ($tools as $tool): ?>
                        <?php $is_favorite = in_array($tool['id'], $favorite_tools); ?>
                        <div class="item product-item" 
                             data-item-type="tool" 
                             data-item-id="<?= htmlspecialchars($tool['id']) ?>"
                             style="cursor: pointer;">
                            <img src="Tools/<?= htmlspecialchars($tool['image']) ?>" class="image" alt="<?= htmlspecialchars($tool['name']) ?>" />
                            <h3 class="item-name"><?= htmlspecialchars($tool['name']) ?></h3>
                            <div class="item-price-row">
                                <p>$<?= number_format($tool['price'], 2) ?></p>
                                <button class="add-to-cart-btn" 
                                        data-item-type="tool" 
                                        data-item-id="<?= htmlspecialchars($tool['id']) ?>">Add to Cart</button>
                            </div>
                            <div class="corner-circle favorite-btn" 
                                 data-item-type="tool" 
                                 data-item-id="<?= htmlspecialchars($tool['id']) ?>"
                                 style="cursor: pointer;"
                                 onclick="event.stopPropagation();">
                                <img src="Icons/<?= $is_favorite ? 'heart-fill.svg' : 'heart.svg' ?>" 
                                     class="corner-icon favorite-icon" 
                                     alt="Favorite" />
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="grid-column: 1 / -1; text-align: center; padding: 2rem;">No tools found. Please try different filters or add tools in the admin panel.</p>
                <?php endif; ?>
            </div>
        </section>
    </main>

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

<script>
    // Update models and series dynamically without submitting form
    document.addEventListener("DOMContentLoaded", () => {
        const brandSelect = document.getElementById('brand-select');
        const modelSelect = document.getElementById('model-select');
        const seriesSelect = document.getElementById('series-select');
        
        if (!brandSelect || !modelSelect || !seriesSelect) return;
        
        // When brand changes - fetch models dynamically
        brandSelect.addEventListener('change', async () => {
            const brand = brandSelect.value;
            
            if (!brand) {
                modelSelect.disabled = true;
                modelSelect.innerHTML = '<option value="">Any</option>';
                seriesSelect.disabled = true;
                seriesSelect.innerHTML = '<option value="">Any</option>';
                return;
            }
            
            // Fetch models for selected brand
            try {
                const response = await fetch(`api/car_filters.php?action=models&brand=${encodeURIComponent(brand)}`);
                const data = await response.json();
                
                if (data.success && data.models.length > 0) {
                    modelSelect.disabled = false;
                    modelSelect.innerHTML = '<option value="">Any</option>';
                    data.models.forEach(model => {
                        const option = document.createElement('option');
                        option.value = model;
                        option.textContent = model.toUpperCase();
                        modelSelect.appendChild(option);
                    });
                } else {
                    modelSelect.disabled = true;
                    modelSelect.innerHTML = '<option value="">Any</option>';
                }
                
                // Reset series
                seriesSelect.disabled = true;
                seriesSelect.innerHTML = '<option value="">Any</option>';
            } catch (error) {
                console.error('Error fetching models:', error);
                modelSelect.disabled = true;
                modelSelect.innerHTML = '<option value="">Any</option>';
            }
        });
        
        // When model changes - fetch series dynamically
        modelSelect.addEventListener('change', async () => {
            const brand = brandSelect.value;
            const model = modelSelect.value;
            
            if (!brand || !model) {
                seriesSelect.disabled = true;
                seriesSelect.innerHTML = '<option value="">Any</option>';
                return;
            }
            
            // Fetch series for selected brand and model
            try {
                const response = await fetch(`api/car_filters.php?action=series&brand=${encodeURIComponent(brand)}&model=${encodeURIComponent(model)}`);
                const data = await response.json();
                
                if (data.success && data.series.length > 0) {
                    seriesSelect.disabled = false;
                    seriesSelect.innerHTML = '<option value="">Any</option>';
                    data.series.forEach(serie => {
                        const option = document.createElement('option');
                        option.value = serie;
                        option.textContent = serie.toUpperCase();
                        seriesSelect.appendChild(option);
                    });
                } else {
                    seriesSelect.disabled = true;
                    seriesSelect.innerHTML = '<option value="">Any</option>';
                }
            } catch (error) {
                console.error('Error fetching series:', error);
                seriesSelect.disabled = true;
                seriesSelect.innerHTML = '<option value="">Any</option>';
            }
        });
    });
    
    // Add to cart functionality
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
                        alert(data.message || 'Failed to add to cart');
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
            
            fetch(`api/product_details.php?item_type=${encodeURIComponent(itemType)}&item_id=${itemId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.product) {
                        currentProductData = data.product;
                        updateModalContent(data.product);
                    } else {
                        alert('Failed to load product details');
                        closeProductModal();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while loading product details');
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
                imageElement.src = product.image_path;
                imageElement.onerror = function() {
                    const parts = product.image_path.split('/');
                    const encodedPath = parts.slice(0, -1).join('/') + '/' + encodeURIComponent(parts[parts.length - 1]);
                    this.src = encodedPath;
                    this.onerror = function() {
                        this.src = 'placeholder.png';
                    };
                };
            }
            
            const favoriteIcon = document.getElementById('modal-favorite-icon');
            if (favoriteIcon) {
                favoriteIcon.src = product.is_favorite ? 'Icons/heart-fill.svg' : 'Icons/heart.svg';
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
                    const response = await fetch("api/favorites.php", {
                        method: "POST",
                        body: formData
                    });
                    const result = await response.json();
                    
                    if (result.success) {
                        icon.src = result.is_favorite ? "Icons/heart-fill.svg" : "Icons/heart.svg";
                        if (currentProductData) currentProductData.is_favorite = result.is_favorite;
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

        // Close modal on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeProductModal();
            }
        });
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

