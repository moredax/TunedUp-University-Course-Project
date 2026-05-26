<?php
session_start();

// Database connection
require_once __DIR__ . '/config/database.php';
$mysqli = getDBConnection();

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Get cart items
$cart_items = [];
$total = 0;

if ($user_id) {
    // Logged in: Get from database
    $stmt = $mysqli->prepare("SELECT item_type, item_id, quantity FROM cart_items WHERE user_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            // Get item price and details
            $price = 0;
            $name = '';
            $image = '';
            
            switch ($row['item_type']) {
                case 'tool':
                    $item_stmt = $mysqli->prepare("SELECT name, price, image FROM tools WHERE id = ?");
                    break;
                case 'sticker':
                    $item_stmt = $mysqli->prepare("SELECT name, price, image FROM stickers WHERE id = ?");
                    break;
                case 'color':
                    $item_stmt = $mysqli->prepare("SELECT name, price, image FROM car_colors WHERE id = ?");
                    break;
                case 'light':
                    $item_stmt = $mysqli->prepare("SELECT name, price, image FROM car_lights WHERE id = ?");
                    break;
            }
            
            if (isset($item_stmt)) {
                $item_stmt->bind_param("i", $row['item_id']);
                $item_stmt->execute();
                $item_result = $item_stmt->get_result();
                if ($item_row = $item_result->fetch_assoc()) {
                    $price = (float)$item_row['price'];
                    $name = $item_row['name'];
                    $image = $item_row['image'];
                }
                $item_stmt->close();
            }
            
            $item_total = $price * $row['quantity'];
            $total += $item_total;
            
            $cart_items[] = [
                'item_type' => $row['item_type'],
                'item_id' => $row['item_id'],
                'quantity' => $row['quantity'],
                'price' => $price,
                'name' => $name,
                'image' => $image,
                'total' => $item_total
            ];
        }
        $stmt->close();
    }
} else {
    // Not logged in: Get from session
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $item_total = $item['price'] * $item['quantity'];
            $total += $item_total;
            $cart_items[] = [
                'item_type' => $item['item_type'],
                'item_id' => $item['item_id'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'name' => $item['name'],
                'image' => $item['image'],
                'total' => $item_total
            ];
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
            <img src="Headings/Cart.png" class="heading-cart" alt="CART" />
        </section>

        <!-- NEW TWO-COLUMN CART LAYOUT -->
        <section class="cart-layout">

            <!-- LEFT SIDE: PRODUCTS -->
            <div class="cart-left">
                <?php if (empty($cart_items)): ?>
                    <div class="cart-empty">
                        <p>Your cart is empty</p>
                        <a href="main.php" class="continue-shopping-btn">Continue Shopping</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($cart_items as $item): ?>
                        <?php
                        // Determine image path based on item type
                        $image_path = '';
                        $image_name = $item['image'] ?? 'placeholder.png';
                        if ($item['item_type'] === 'tool') {
                            $image_path = 'Tools/' . htmlspecialchars($image_name);
                        } elseif ($item['item_type'] === 'sticker') {
                            $image_path = 'Stickers/' . htmlspecialchars($image_name);
                        } elseif ($item['item_type'] === 'color') {
                            $image_path = 'Cars/' . htmlspecialchars($image_name);
                        } elseif ($item['item_type'] === 'light') {
                            $image_path = 'Lights/' . htmlspecialchars($image_name);
                        } else {
                            $image_path = 'placeholder.png';
                        }
                        ?>
                        <div class="cart-item" data-item-type="<?= htmlspecialchars($item['item_type']) ?>" data-item-id="<?= htmlspecialchars($item['item_id']) ?>">
                            <div class="cart-image-wrapper">
                                <div class="cart-image-bg"></div>
                                <img src="<?= $image_path ?>" class="cart-image" alt="<?= htmlspecialchars($item['name']) ?>">
                            </div>

                            <div class="cart-info">
                                <h3 class="cart-title"><?= htmlspecialchars($item['name']) ?></h3>
                                <p class="cart-price">$<?= number_format($item['price'], 2) ?></p>

                                <div class="cart-quantity">
                                    <button class="qty-btn qty-decrease">−</button>
                                    <span class="qty-number"><?= htmlspecialchars($item['quantity']) ?></span>
                                    <button class="qty-btn qty-increase">+</button>
                                </div>
                            </div>

                            <img src="Icons/trash.svg" class="cart-remove" alt="Remove" title="Remove from cart">
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- RIGHT SIDE: ORDER SUMMARY -->
            <div class="cart-right">

                <div class="cart-summary-v2">
                    <h3 class="summary-title">Checkout Details</h3>

                    <div class="summary-box">
                        <!-- Total Display -->
                        <div class="cart-total-section">
                            <div class="total-row">
                                <span class="total-label">Subtotal:</span>
                                <span class="total-value" id="cart-subtotal">$<?= number_format($total, 2) ?></span>
                            </div>
                            <div class="total-row">
                                <span class="total-label">Total:</span>
                                <span class="total-value total-bold" id="cart-total">$<?= number_format($total, 2) ?></span>
                            </div>
                        </div>

                        <div class="checkout-field">
                            <label>Full Name</label>
                            <input type="text" id="checkout-name" placeholder="John Doe" <?= $user_id && isset($_SESSION['name']) ? 'value="' . htmlspecialchars($_SESSION['name']) . '"' : '' ?> required>
                        </div>

                        <div class="checkout-field">
                            <label>Email</label>
                            <input type="email" id="checkout-email" placeholder="example@mail.com" <?= $user_id && isset($_SESSION['email']) ? 'value="' . htmlspecialchars($_SESSION['email']) . '"' : '' ?> required>
                        </div>

                        <div class="checkout-field">
                            <label>Shipping Address</label>
                            <input type="text" id="checkout-address" placeholder="Street, City, ZIP" required>
                        </div>

                        <div class="payment-method">
                            <label>Payment Method</label>

                            <div class="payment-options">
                                <label class="payment-option">
                                    <input type="radio" name="payment" value="cash" checked>
                                    <span>Cash</span>
                                </label>

                                <label class="payment-option">
                                    <input type="radio" name="payment" value="card">
                                    <span>Card</span>
                                </label>
                            </div>
                        </div>

                    </div>

                    <button class="checkout-btn-v2" id="checkout-btn" <?= empty($cart_items) ? 'disabled' : '' ?>>Buy</button>
                </div>

            </div>


        </section>
        <div id="footer-placeholder"></div>

    </main>

    <!-- Order Confirmation Modal -->
    <div id="order-confirmation-modal" class="order-modal">
        <div class="order-modal-content">
            <div class="order-modal-header">
                <h2>Order Confirmed!</h2>
                <span class="order-modal-close">&times;</span>
            </div>
            <div class="order-modal-body">
                <div class="order-success-icon">✓</div>
                <p class="order-success-message">Your order has been placed successfully!</p>
                <div class="order-details">
                    <p><strong>Order ID:</strong> <span id="order-id-display"></span></p>
                    <p><strong>Total Amount:</strong> <span id="order-total-display"></span></p>
                    <p class="order-note">You will receive a confirmation email shortly.</p>
                </div>
            </div>
            <div class="order-modal-footer">
                <button class="order-modal-btn" id="order-confirm-btn">Continue Shopping</button>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="delete-confirmation-modal" class="delete-modal">
        <div class="delete-modal-content">
            <div class="delete-modal-header">
                <h2>Remove Item</h2>
                <span class="delete-modal-close">&times;</span>
            </div>
            <div class="delete-modal-body">
                <div class="delete-warning-icon">⚠</div>
                <p class="delete-warning-message">Are you sure you want to remove this item from your cart?</p>
                <div class="delete-item-preview">
                    <img id="delete-item-image" src="" alt="Item" class="delete-item-img">
                    <div class="delete-item-info">
                        <h3 id="delete-item-name"></h3>
                        <p class="delete-item-price" id="delete-item-price"></p>
                    </div>
                </div>
                <p class="delete-note">This action cannot be undone.</p>
            </div>
            <div class="delete-modal-footer">
                <button class="delete-modal-btn delete-cancel-btn" id="delete-cancel-btn">Cancel</button>
                <button class="delete-modal-btn delete-confirm-btn" id="delete-confirm-btn">Remove</button>
            </div>
        </div>
    </div>

    <script src="main.js"></script>
    <script src="cart.js"></script>

</body>
</html>

