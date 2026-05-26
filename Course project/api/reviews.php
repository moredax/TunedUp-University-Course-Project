<?php
session_start();
header('Content-Type: application/json');

// Database connection
require_once __DIR__ . '/../config/database.php';
$mysqli = getDBConnection();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to add reviews', 'requires_login' => true]);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$item_type = $_POST['item_type'] ?? $_GET['item_type'] ?? '';
$item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : (isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0);

// Validate item_type
$allowed_types = ['tool', 'sticker', 'color', 'light'];
if (!in_array($item_type, $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Invalid item type']);
    exit;
}

// Validate item_id
if ($item_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
    exit;
}

switch ($action) {
    case 'add':
    case 'edit':
        // Verify user has purchased this item AND the order has been delivered
        $purchase_check = $mysqli->prepare("
            SELECT oi.id 
            FROM order_items oi
            INNER JOIN orders o ON oi.order_id = o.id
            WHERE o.user_id = ? AND oi.item_type = ? AND oi.item_id = ? AND o.status = 'delivered'
            LIMIT 1
        ");
        if ($purchase_check) {
            $purchase_check->bind_param("isi", $user_id, $item_type, $item_id);
            $purchase_check->execute();
            $purchase_result = $purchase_check->get_result();
            
            if ($purchase_result->num_rows === 0) {
                $purchase_check->close();
                echo json_encode(['success' => false, 'message' => 'You can only review items from orders that have been delivered']);
                exit;
            }
            $purchase_check->close();
        }
        
        // Get rating and comment
        $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
        $comment = $_POST['comment'] ?? '';
        
        // Validate rating
        if ($rating < 1 || $rating > 5) {
            echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5']);
            exit;
        }
        
        // Handle photo upload if provided
        $photo_path = null;
        if (isset($_FILES['photo']) && !empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            // Use absolute path to avoid path resolution issues
            $base_dir = dirname(__DIR__); // Go up from api/ to project root
            $upload_dir = $base_dir . '/Reviews/';
            
            if (!file_exists($upload_dir)) {
                if (!mkdir($upload_dir, 0777, true)) {
                    echo json_encode(['success' => false, 'message' => 'Failed to create upload directory']);
                    exit;
                }
            }
            
            $file_extension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($file_extension, $allowed_extensions)) {
                $file_name = uniqid('review_', true) . '.' . $file_extension;
                $file_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $file_path)) {
                    if (file_exists($file_path)) {
                        $photo_path = 'Reviews/' . $file_name;
                    }
                }
            }
        }
        
        // Check if review already exists
        $check_stmt = $mysqli->prepare("SELECT id, photo FROM reviews WHERE user_id = ? AND item_type = ? AND item_id = ?");
        if ($check_stmt) {
            $check_stmt->bind_param("isi", $user_id, $item_type, $item_id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            $existing_review = $result->fetch_assoc();
            $check_stmt->close();
            
            if ($existing_review) {
                // Update existing review
                $review_id = $existing_review['id'];
                // Keep existing photo if no new photo uploaded, otherwise use new photo
                $final_photo = $photo_path ? $photo_path : ($existing_review['photo'] ? $existing_review['photo'] : '');
                
                $update_stmt = $mysqli->prepare("UPDATE reviews SET rating = ?, comment = ?, photo = ?, created_at = NOW() WHERE id = ?");
                if ($update_stmt) {
                    $update_stmt->bind_param("issi", $rating, $comment, $final_photo, $review_id);
                    if ($update_stmt->execute()) {
                        echo json_encode(['success' => true, 'message' => 'Review updated successfully', 'review_id' => $review_id]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Failed to update review']);
                    }
                    $update_stmt->close();
                } else {
                    echo json_encode(['success' => false, 'message' => 'Database error']);
                }
            } else {
                // Insert new review
                // Only save photo path if file was actually uploaded
                $photo_value = $photo_path ? $photo_path : null;
                
                $insert_stmt = $mysqli->prepare("INSERT INTO reviews (user_id, item_type, item_id, rating, comment, photo, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                if ($insert_stmt) {
                    $insert_stmt->bind_param("isiiss", $user_id, $item_type, $item_id, $rating, $comment, $photo_value);
                    if ($insert_stmt->execute()) {
                        echo json_encode(['success' => true, 'message' => 'Review added successfully', 'review_id' => $mysqli->insert_id]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Failed to add review']);
                    }
                    $insert_stmt->close();
                } else {
                    echo json_encode(['success' => false, 'message' => 'Database error']);
                }
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
        break;
        
    case 'get':
        // Get review for specific item by current user
        $stmt = $mysqli->prepare("SELECT id, rating, comment, photo, created_at FROM reviews WHERE user_id = ? AND item_type = ? AND item_id = ?");
        if ($stmt) {
            $stmt->bind_param("isi", $user_id, $item_type, $item_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($review = $result->fetch_assoc()) {
                echo json_encode(['success' => true, 'review' => $review]);
            } else {
                echo json_encode(['success' => true, 'review' => null]);
            }
            $stmt->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
        break;
        
    case 'check':
        // Check if user has purchased this item from a delivered order and if review exists
        $purchase_check = $mysqli->prepare("
            SELECT oi.id 
            FROM order_items oi
            INNER JOIN orders o ON oi.order_id = o.id
            WHERE o.user_id = ? AND oi.item_type = ? AND oi.item_id = ? AND o.status = 'delivered'
            LIMIT 1
        ");
        $has_purchased = false;
        if ($purchase_check) {
            $purchase_check->bind_param("isi", $user_id, $item_type, $item_id);
            $purchase_check->execute();
            $purchase_result = $purchase_check->get_result();
            $has_purchased = $purchase_result->num_rows > 0;
            $purchase_check->close();
        }
        
        $review_check = $mysqli->prepare("SELECT id FROM reviews WHERE user_id = ? AND item_type = ? AND item_id = ?");
        $has_review = false;
        if ($review_check) {
            $review_check->bind_param("isi", $user_id, $item_type, $item_id);
            $review_check->execute();
            $review_result = $review_check->get_result();
            $has_review = $review_result->num_rows > 0;
            $review_check->close();
        }
        
        echo json_encode([
            'success' => true, 
            'has_purchased' => $has_purchased,
            'has_review' => $has_review
        ]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

$mysqli->close();
?>

