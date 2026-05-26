<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/sign-in.php");
    exit;
}

// Database connection
require_once __DIR__ . '/../config/database.php';
$mysqli = getDBConnection();

$user_id = $_SESSION['user_id'];

// Fetch user's orders
$orders = [];
$stmt = $mysqli->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        // Get order items for each order
        $order_items = [];
        $items_stmt = $mysqli->prepare("SELECT * FROM order_items WHERE order_id = ?");
        if ($items_stmt) {
            $items_stmt->bind_param("i", $row['id']);
            $items_stmt->execute();
            $items_result = $items_stmt->get_result();
            
            while ($item_row = $items_result->fetch_assoc()) {
                // Get item details based on type
                $item_name = '';
                $item_image = '';
                
                switch ($item_row['item_type']) {
                    case 'tool':
                        $item_stmt = $mysqli->prepare("SELECT name, image FROM tools WHERE id = ?");
                        break;
                    case 'sticker':
                        $item_stmt = $mysqli->prepare("SELECT name, image FROM stickers WHERE id = ?");
                        break;
                    case 'color':
                        $item_stmt = $mysqli->prepare("SELECT name, image FROM car_colors WHERE id = ?");
                        break;
                    case 'light':
                        $item_stmt = $mysqli->prepare("SELECT name, image FROM car_lights WHERE id = ?");
                        break;
                }
                
                if (isset($item_stmt)) {
                    $item_stmt->bind_param("i", $item_row['item_id']);
                    $item_stmt->execute();
                    $item_result = $item_stmt->get_result();
                    if ($item_data = $item_result->fetch_assoc()) {
                        $item_name = $item_data['name'];
                        $item_image = $item_data['image'];
                    }
                    $item_stmt->close();
                }
                
                // Check if user has reviewed this item for THIS specific order
                // Only show review if it was created after this order was created
                // This prevents showing reviews from previous orders of the same item
                $has_review = false;
                $review_data = null;
                
                // Only check for review if order is delivered
                if ($row['status'] === 'delivered') {
                    // Get order creation date
                    $order_created_at = $row['created_at'];
                    
                    $review_stmt = $mysqli->prepare("
                        SELECT id, rating, comment, photo, created_at 
                        FROM reviews 
                        WHERE user_id = ? AND item_type = ? AND item_id = ? 
                        AND created_at >= ?
                        ORDER BY created_at DESC
                        LIMIT 1
                    ");
                    if ($review_stmt) {
                        $review_stmt->bind_param("isss", $user_id, $item_row['item_type'], $item_row['item_id'], $order_created_at);
                        $review_stmt->execute();
                        $review_result = $review_stmt->get_result();
                        if ($review_row = $review_result->fetch_assoc()) {
                            $has_review = true;
                            $review_data = $review_row;
                        }
                        $review_stmt->close();
                    }
                }
                
                $order_items[] = [
                    'item_type' => $item_row['item_type'],
                    'item_id' => $item_row['item_id'],
                    'name' => $item_name,
                    'image' => $item_image,
                    'price' => $item_row['price'],
                    'quantity' => $item_row['quantity'],
                    'has_review' => $has_review,
                    'review' => $review_data
                ];
            }
            $items_stmt->close();
        }
        
        $row['items'] = $order_items;
        $orders[] = $row;
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
    <link rel="stylesheet" href="../css/profile.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <style>
        .orders-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .orders-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .orders-header h1 {
            font-size: clamp(20px, 2.5vw, 28px);
            font-weight: 700;
            color: #333;
        }
        
        .back-btn {
            padding: 10px 20px;
            background-color: #dde9ff;
            color: #333;
            text-decoration: none;
            border-radius: 999px;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        
        .back-btn:hover {
            background-color: #b9c9f0;
        }
        
        .order-card {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            border: 1px solid #e6e9f3;
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 2px solid #e6e9f3;
        }
        
        .order-header-right {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 12px;
        }
        
        .cancel-order-btn {
            padding: 8px 16px;
            background-color: #ff5252;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .cancel-order-btn:hover {
            background-color: #ff1744;
        }
        
        .cancel-order-btn:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
        
        .order-info {
            flex: 1;
        }
        
        .order-id {
            font-size: 14px;
            color: #666;
            margin-bottom: 4px;
        }
        
        .order-date {
            font-size: 13px;
            color: #999;
        }
        
        .order-status {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            text-transform: capitalize;
        }
        
        .order-status.confirmed {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        
        .order-status.in_delivery {
            background-color: #e3f2fd;
            color: #1976d2;
        }
        
        .order-status.delivered {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        
        .order-status.cancelled {
            background-color: #ffebee;
            color: #c62828;
        }
        
        .order-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 20px;
        }
        
        .detail-item {
            font-size: 14px;
        }
        
        .detail-label {
            color: #666;
            margin-bottom: 4px;
            font-weight: 600;
        }
        
        .detail-value {
            color: #333;
        }
        
        .order-items {
            margin-top: 20px;
        }
        
        .order-items-title {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 16px;
            color: #333;
        }
        
        .order-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 12px;
            background: #f8f9ff;
            border-radius: 12px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }
        
        .order-item-image {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
            background: #e6e9f3;
        }
        
        .order-item-info {
            flex: 1;
        }
        
        .order-item-name {
            font-weight: 600;
            font-size: 14px;
            color: #333;
            margin-bottom: 4px;
        }
        
        .order-item-type {
            font-size: 12px;
            color: #666;
            text-transform: capitalize;
        }
        
        .order-item-price {
            font-weight: 700;
            color: #333;
            font-size: 14px;
        }
        
        .order-item-quantity {
            font-size: 13px;
            color: #666;
        }
        
        .order-item-actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
            align-items: flex-end;
            min-width: 120px;
        }
        
        .review-btn {
            padding: 8px 16px;
            background-color: #4a90e2;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .review-btn:hover {
            background-color: #357abd;
        }
        
        .review-btn.edit {
            background-color: #28a745;
        }
        
        .review-btn.edit:hover {
            background-color: #218838;
        }
        
        .review-status {
            font-size: 12px;
            color: #28a745;
            font-weight: 600;
        }
        
        /* Review Modal */
        .review-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            overflow: auto;
        }
        
        .review-modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 30px;
            border-radius: 16px;
            max-width: 600px;
            width: 90%;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }
        
        .review-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid #e6e9f3;
        }
        
        .review-modal-header h2 {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            margin: 0;
        }
        
        .close-modal {
            background: none;
            border: none;
            font-size: 28px;
            color: #999;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .close-modal:hover {
            color: #333;
        }
        
        .review-form-group {
            margin-bottom: 20px;
        }
        
        .review-form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
            font-size: 14px;
        }
        
        .rating-input {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        
        .star-rating {
            display: flex;
            gap: 4px;
            font-size: 28px;
            cursor: pointer;
        }
        
        .star-rating .star {
            color: #ddd;
            transition: color 0.2s;
        }
        
        .star-rating .star.active {
            color: #ffc107;
        }
        
        .star-rating .star:hover {
            color: #ffc107;
        }
        
        .rating-value {
            font-size: 14px;
            color: #666;
            margin-left: 8px;
        }
        
        .review-form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e6e9f3;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            resize: vertical;
            min-height: 120px;
        }
        
        .review-form-group textarea:focus {
            outline: none;
            border-color: #4a90e2;
        }
        
        .review-form-group input[type="file"] {
            width: 100%;
            padding: 8px;
            border: 2px solid #e6e9f3;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .review-photo-preview {
            margin-top: 12px;
            max-width: 200px;
            max-height: 200px;
            border-radius: 8px;
            display: none;
        }
        
        .review-photo-preview img {
            width: 100%;
            height: auto;
            border-radius: 8px;
        }
        
        .review-form-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 24px;
        }
        
        .review-submit-btn {
            padding: 12px 24px;
            background-color: #4a90e2;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .review-submit-btn:hover {
            background-color: #357abd;
        }
        
        .review-cancel-btn {
            padding: 12px 24px;
            background-color: #e6e9f3;
            color: #333;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .review-cancel-btn:hover {
            background-color: #d0d5e0;
        }
        
        .review-error {
            color: #c62828;
            font-size: 13px;
            margin-top: 8px;
            display: none;
        }
        
        .review-success {
            color: #2e7d32;
            font-size: 13px;
            margin-top: 8px;
            display: none;
        }
        
        .order-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding-top: 16px;
            border-top: 2px solid #e6e9f3;
        }
        
        .order-total-label {
            font-size: 18px;
            font-weight: 700;
            color: #333;
        }
        
        .order-total-value {
            font-size: 20px;
            font-weight: 700;
            color: #333;
        }
        
        .no-orders {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .no-orders-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }
        
        .no-orders-text {
            font-size: 16px;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .order-details {
                grid-template-columns: 1fr;
            }
            
            .order-header {
                flex-direction: column;
                gap: 12px;
            }
            
            .order-header-right {
                align-items: flex-start;
                width: 100%;
            }
            
            .order-item {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .order-item-actions {
                width: 100%;
                align-items: flex-start;
                margin-top: 8px;
            }
            
            .review-modal-content {
                margin: 10% auto;
                padding: 20px;
                width: 95%;
            }
        }
    </style>
</head>
<body>

    <!-- HEADER -->
    <header>
        <div class="header-left">
            <a href="../main.php" class="logo">TunedUp</a>
        </div>
        <div class="nav">
            <a href="../cart.php">Cart</a>
            <a href="saved.php">Saved</a>
            <a href="profile.php">Profile</a>
        </div>
    </header>

    <!-- MAIN -->
    <main class="profile-wrapper" style="padding-top: 100px;">
        <div class="orders-container">
            <div class="orders-header">
                <h1>My Orders</h1>
                <a href="profile.php" class="back-btn">← Back to Profile</a>
            </div>

            <?php if (empty($orders)): ?>
                <div class="no-orders">
                    <div class="no-orders-icon">📦</div>
                    <div class="no-orders-text">You haven't placed any orders yet.</div>
                    <a href="../main.php" class="back-btn">Start Shopping</a>
                </div>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <div class="order-card" id="order-<?= $order['id'] ?>">
                        <div class="order-header">
                            <div class="order-info">
                                <div class="order-id">Order #<?= htmlspecialchars($order['id']) ?></div>
                                <div class="order-date"><?= date('F j, Y g:i A', strtotime($order['created_at'])) ?></div>
                            </div>
                            <div class="order-header-right">
                                <div class="order-status <?= htmlspecialchars($order['status']) ?>">
                                    <?= htmlspecialchars(str_replace('_', ' ', $order['status'])) ?>
                                </div>
                                <?php if ($order['status'] === 'confirmed'): ?>
                                    <button class="cancel-order-btn" onclick="cancelOrder(<?= $order['id'] ?>)">
                                        Cancel Order
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="order-details">
                            <div class="detail-item">
                                <div class="detail-label">Email</div>
                                <div class="detail-value"><?= htmlspecialchars($order['email']) ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Shipping Address</div>
                                <div class="detail-value"><?= htmlspecialchars($order['shipment_address']) ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Payment Method</div>
                                <div class="detail-value"><?= htmlspecialchars(ucfirst($order['payment_method'])) ?></div>
                            </div>
                        </div>

                        <?php if (!empty($order['items'])): ?>
                            <div class="order-items">
                                <div class="order-items-title">Order Items</div>
                                <?php foreach ($order['items'] as $item): ?>
                                    <?php
                                    // Determine image path
                                    $image_path = '';
                                    if ($item['item_type'] === 'tool') {
                                        $image_path = '../Tools/' . htmlspecialchars($item['image']);
                                    } elseif ($item['item_type'] === 'sticker') {
                                        $image_path = '../Stickers/' . htmlspecialchars($item['image']);
                                    } elseif ($item['item_type'] === 'color') {
                                        $image_path = '../Cars/' . htmlspecialchars($item['image']);
                                    } elseif ($item['item_type'] === 'light') {
                                        $image_path = '../Lights/' . htmlspecialchars($item['image']);
                                    } else {
                                        $image_path = '../placeholder.png';
                                    }
                                    ?>
                                    <div class="order-item">
                                        <img src="<?= $image_path ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="order-item-image" onerror="this.src='../placeholder.png'">
                                        <div class="order-item-info">
                                            <div class="order-item-name"><?= htmlspecialchars($item['name']) ?></div>
                                            <div class="order-item-type"><?= htmlspecialchars($item['item_type']) ?></div>
                                        </div>
                                        <div style="text-align: right;">
                                            <div class="order-item-price">$<?= number_format($item['price'], 2) ?></div>
                                            <div class="order-item-quantity">Qty: <?= htmlspecialchars($item['quantity']) ?></div>
                                        </div>
                                        <div class="order-item-actions">
                                            <?php if ($item['has_review']): ?>
                                                <div class="review-status">✓ Reviewed</div>
                                                <button class="review-btn edit" 
                                                        onclick="openReviewModal('<?= htmlspecialchars($item['item_type']) ?>', <?= $item['item_id'] ?>, '<?= htmlspecialchars($item['name']) ?>', <?= $item['review']['rating'] ?? 0 ?>, '<?= htmlspecialchars(addslashes($item['review']['comment'] ?? '')) ?>', '<?= htmlspecialchars($item['review']['photo'] ?? '') ?>')">
                                                    Edit Review
                                                </button>
                                            <?php else: ?>
                                                <button class="review-btn" 
                                                        onclick="openReviewModal('<?= htmlspecialchars($item['item_type']) ?>', <?= $item['item_id'] ?>, '<?= htmlspecialchars($item['name']) ?>')">
                                                    Write Review
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="order-total">
                            <div class="order-total-label">Total</div>
                            <div class="order-total-value">$<?= number_format($order['total_price'], 2) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- Review Modal -->
    <div id="reviewModal" class="review-modal">
        <div class="review-modal-content">
            <div class="review-modal-header">
                <h2 id="reviewModalTitle">Write Review</h2>
                <button class="close-modal" onclick="closeReviewModal()">&times;</button>
            </div>
            <form id="reviewForm" enctype="multipart/form-data">
                <input type="hidden" id="reviewItemType" name="item_type">
                <input type="hidden" id="reviewItemId" name="item_id">
                <input type="hidden" id="reviewAction" name="action" value="add">
                
                <div class="review-form-group">
                    <label>Item</label>
                    <div id="reviewItemName" style="font-size: 16px; font-weight: 600; color: #333;"></div>
                </div>
                
                <div class="review-form-group">
                    <label>Rating <span style="color: #c62828;">*</span></label>
                    <div class="rating-input">
                        <div class="star-rating" id="starRating">
                            <span class="star" data-rating="1">★</span>
                            <span class="star" data-rating="2">★</span>
                            <span class="star" data-rating="3">★</span>
                            <span class="star" data-rating="4">★</span>
                            <span class="star" data-rating="5">★</span>
                        </div>
                        <span class="rating-value" id="ratingValue">0 / 5</span>
                    </div>
                    <input type="hidden" id="reviewRating" name="rating" value="0" required>
                </div>
                
                <div class="review-form-group">
                    <label>Comment</label>
                    <textarea id="reviewComment" name="comment" placeholder="Share your experience with this product..."></textarea>
                </div>
                
                <div class="review-form-group">
                    <label>Photo (optional)</label>
                    <input type="file" id="reviewPhoto" name="photo" accept="image/*">
                    <div class="review-photo-preview" id="photoPreview">
                        <img id="photoPreviewImg" src="" alt="Preview">
                    </div>
                </div>
                
                <div class="review-error" id="reviewError"></div>
                <div class="review-success" id="reviewSuccess"></div>
                
                <div class="review-form-actions">
                    <button type="button" class="review-cancel-btn" onclick="closeReviewModal()">Cancel</button>
                    <button type="submit" class="review-submit-btn">Submit Review</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentRating = 0;
        let isEditMode = false;
        
        // Star rating functionality
        const stars = document.querySelectorAll('.star');
        const ratingValue = document.getElementById('ratingValue');
        const ratingInput = document.getElementById('reviewRating');
        
        stars.forEach(star => {
            star.addEventListener('click', function() {
                currentRating = parseInt(this.getAttribute('data-rating'));
                updateStarRating(currentRating);
                ratingInput.value = currentRating;
                ratingValue.textContent = currentRating + ' / 5';
            });
            
            star.addEventListener('mouseenter', function() {
                const hoverRating = parseInt(this.getAttribute('data-rating'));
                updateStarRating(hoverRating);
            });
        });
        
        document.getElementById('starRating').addEventListener('mouseleave', function() {
            updateStarRating(currentRating);
        });
        
        function updateStarRating(rating) {
            stars.forEach((star, index) => {
                if (index < rating) {
                    star.classList.add('active');
                } else {
                    star.classList.remove('active');
                }
            });
        }
        
        // Photo preview
        document.getElementById('reviewPhoto').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('photoPreviewImg').src = e.target.result;
                    document.getElementById('photoPreview').style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
        
        // Open review modal
        function openReviewModal(itemType, itemId, itemName, rating = 0, comment = '', photo = '') {
            isEditMode = rating > 0;
            currentRating = rating;
            
            document.getElementById('reviewItemType').value = itemType;
            document.getElementById('reviewItemId').value = itemId;
            document.getElementById('reviewItemName').textContent = itemName;
            document.getElementById('reviewAction').value = isEditMode ? 'edit' : 'add';
            document.getElementById('reviewModalTitle').textContent = isEditMode ? 'Edit Review' : 'Write Review';
            
            // Set rating
            if (rating > 0) {
                updateStarRating(rating);
                ratingInput.value = rating;
                ratingValue.textContent = rating + ' / 5';
            } else {
                updateStarRating(0);
                ratingInput.value = 0;
                ratingValue.textContent = '0 / 5';
            }
            
            // Set comment
            document.getElementById('reviewComment').value = comment;
            
            // Set photo preview if exists
            if (photo) {
                document.getElementById('photoPreviewImg').src = '../' + photo;
                document.getElementById('photoPreview').style.display = 'block';
            } else {
                document.getElementById('photoPreview').style.display = 'none';
            }
            
            // Reset form errors
            document.getElementById('reviewError').style.display = 'none';
            document.getElementById('reviewSuccess').style.display = 'none';
            
            document.getElementById('reviewModal').style.display = 'block';
        }
        
        // Close review modal
        function closeReviewModal() {
            document.getElementById('reviewModal').style.display = 'none';
            document.getElementById('reviewForm').reset();
            document.getElementById('photoPreview').style.display = 'none';
            currentRating = 0;
            updateStarRating(0);
            ratingInput.value = 0;
            ratingValue.textContent = '0 / 5';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('reviewModal');
            if (event.target === modal) {
                closeReviewModal();
            }
        }
        
        // Handle form submission
        document.getElementById('reviewForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', document.getElementById('reviewAction').value);
            
            // Validate rating
            const rating = parseInt(document.getElementById('reviewRating').value);
            if (rating < 1 || rating > 5) {
                showError('Please select a rating between 1 and 5 stars');
                return;
            }
            
            // Show loading state
            const submitBtn = document.querySelector('.review-submit-btn');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Submitting...';
            submitBtn.disabled = true;
            
            fetch('../api/reviews.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccess(data.message);
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showError(data.message);
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                }
            })
            .catch(error => {
                showError('An error occurred. Please try again.');
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            });
        });
        
        function showError(message) {
            const errorDiv = document.getElementById('reviewError');
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
            document.getElementById('reviewSuccess').style.display = 'none';
        }
        
        function showSuccess(message) {
            const successDiv = document.getElementById('reviewSuccess');
            successDiv.textContent = message;
            successDiv.style.display = 'block';
            document.getElementById('reviewError').style.display = 'none';
        }
        
        // Cancel order function
        function cancelOrder(orderId) {
            // Get order details from the DOM
            const orderCard = document.getElementById('order-' + orderId);
            if (!orderCard) {
                alert('Order not found');
                return;
            }
            
            const orderIdText = orderCard.querySelector('.order-id')?.textContent || 'Order #' + orderId;
            const orderDate = orderCard.querySelector('.order-date')?.textContent || '';
            const orderTotal = orderCard.querySelector('.order-total-value')?.textContent || '';
            
            // Show cancel confirmation modal
            showCancelOrderConfirmation(orderId, orderIdText, orderDate, orderTotal);
        }
        
        function showCancelOrderConfirmation(orderId, orderIdText, orderDate, orderTotal) {
            const modal = document.getElementById('cancel-order-modal');
            const orderIdEl = document.getElementById('cancel-order-id');
            const orderDateEl = document.getElementById('cancel-order-date');
            const orderTotalEl = document.getElementById('cancel-order-total');
            const confirmBtn = document.getElementById('cancel-order-confirm-btn');
            const cancelBtn = document.getElementById('cancel-order-cancel-btn');
            const closeBtn = document.querySelector('.cancel-order-modal-close');
            
            // Set order details
            orderIdEl.textContent = orderIdText;
            orderDateEl.textContent = orderDate;
            orderTotalEl.textContent = orderTotal;
            
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
                performCancelOrder(orderId);
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
        
        function showCancelOrderSuccess(message) {
            const modal = document.getElementById('cancel-order-success-modal');
            const messageEl = document.getElementById('cancel-success-message');
            const confirmBtn = document.getElementById('cancel-success-confirm-btn');
            const closeBtn = document.querySelector('.cancel-order-success-close');
            
            // Set success message
            messageEl.textContent = message;
            
            // Show modal
            modal.classList.add('show');
            
            // Close modal function
            function closeModal() {
                modal.classList.remove('show');
                location.reload();
            }
            
            // Remove existing event listeners by cloning and replacing
            const newConfirmBtn = confirmBtn.cloneNode(true);
            const newCloseBtn = closeBtn.cloneNode(true);
            
            confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
            closeBtn.parentNode.replaceChild(newCloseBtn, closeBtn);
            
            // Set up event listeners
            newConfirmBtn.addEventListener('click', closeModal);
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
        
        function performCancelOrder(orderId) {
            const formData = new FormData();
            formData.append('action', 'cancel');
            formData.append('order_id', orderId);
            
            // Find the cancel button in the order card
            const orderCard = document.getElementById('order-' + orderId);
            const cancelBtn = orderCard ? orderCard.querySelector('.cancel-order-btn') : null;
            const originalText = cancelBtn ? cancelBtn.textContent : 'Cancel Order';
            
            if (cancelBtn) {
                cancelBtn.textContent = 'Cancelling...';
                cancelBtn.disabled = true;
            }
            
            fetch('../api/orders.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Close confirmation modal
                    const confirmModal = document.getElementById('cancel-order-modal');
                    if (confirmModal) {
                        confirmModal.classList.remove('show');
                    }
                    // Show success modal
                    showCancelOrderSuccess(data.message);
                } else {
                    alert('Error: ' + data.message);
                    if (cancelBtn) {
                        cancelBtn.textContent = originalText;
                        cancelBtn.disabled = false;
                    }
                }
            })
            .catch(error => {
                alert('An error occurred. Please try again.');
                if (cancelBtn) {
                    cancelBtn.textContent = originalText;
                    cancelBtn.disabled = false;
                }
            });
        }
    </script>

    <!-- Cancel Order Confirmation Modal -->
    <div id="cancel-order-modal" class="cancel-order-modal">
        <div class="cancel-order-modal-content">
            <div class="cancel-order-modal-header">
                <h2>Cancel Order</h2>
                <span class="cancel-order-modal-close">&times;</span>
            </div>
            <div class="cancel-order-modal-body">
                <div class="cancel-warning-icon">⚠</div>
                <p class="cancel-warning-message">Are you sure you want to cancel this order?</p>
                <div class="cancel-order-details">
                    <div class="cancel-detail-item">
                        <strong>Order ID:</strong>
                        <span id="cancel-order-id"></span>
                    </div>
                    <div class="cancel-detail-item">
                        <strong>Order Date:</strong>
                        <span id="cancel-order-date"></span>
                    </div>
                    <div class="cancel-detail-item">
                        <strong>Total Amount:</strong>
                        <span id="cancel-order-total"></span>
                    </div>
                </div>
                <p class="cancel-note">This action cannot be undone. The order will be permanently cancelled.</p>
            </div>
            <div class="cancel-order-modal-footer">
                <button class="cancel-order-modal-btn cancel-order-cancel-btn" id="cancel-order-cancel-btn">Keep Order</button>
                <button class="cancel-order-modal-btn cancel-order-confirm-btn" id="cancel-order-confirm-btn">Cancel Order</button>
            </div>
        </div>
    </div>

    <!-- Cancel Order Success Modal -->
    <div id="cancel-order-success-modal" class="cancel-order-modal">
        <div class="cancel-order-modal-content">
            <div class="cancel-order-modal-header">
                <h2>Order Cancelled</h2>
                <span class="cancel-order-success-close">&times;</span>
            </div>
            <div class="cancel-order-modal-body">
                <div class="cancel-success-icon">✓</div>
                <p class="cancel-success-message" id="cancel-success-message">Order cancelled successfully!</p>
            </div>
            <div class="cancel-order-modal-footer">
                <button class="cancel-order-modal-btn cancel-order-success-btn" id="cancel-success-confirm-btn">OK</button>
            </div>
        </div>
    </div>

</body>
</html>

