<?php
// Suppress error output to prevent breaking JSON response
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start output buffering to catch any unexpected output
ob_start();

session_start();
header('Content-Type: application/json');

// Function to send JSON response and exit
function sendJsonResponse($data) {
    ob_clean();
    echo json_encode($data);
    exit;
}

// Database connection
try {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../config/email_functions.php';
    $mysqli = getDBConnection();
} catch (Exception $e) {
    sendJsonResponse(['success' => false, 'message' => 'Database connection error: ' . $e->getMessage()]);
}

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$action = $_POST['action'] ?? '';

if ($action !== 'checkout') {
    sendJsonResponse(['success' => false, 'message' => 'Invalid action']);
}

// Get form data
$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$address = $_POST['address'] ?? '';
$payment_method = $_POST['payment_method'] ?? 'cash';
$total = isset($_POST['total']) ? (float)$_POST['total'] : 0;

// Validate inputs
if (empty($name) || empty($email) || empty($address)) {
    sendJsonResponse(['success' => false, 'message' => 'Please fill in all required fields']);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendJsonResponse(['success' => false, 'message' => 'Invalid email address']);
}

if ($total <= 0) {
    sendJsonResponse(['success' => false, 'message' => 'Invalid total amount']);
}

// Get cart items
$cart_items = [];
$calculated_total = 0;

if ($user_id) {
    // Logged in: Get from database
    $stmt = $mysqli->prepare("SELECT item_type, item_id, quantity FROM cart_items WHERE user_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            // Get item price
            $price = 0;
            switch ($row['item_type']) {
                case 'tool':
                    $item_stmt = $mysqli->prepare("SELECT price FROM tools WHERE id = ?");
                    break;
                case 'sticker':
                    $item_stmt = $mysqli->prepare("SELECT price FROM stickers WHERE id = ?");
                    break;
                case 'color':
                    $item_stmt = $mysqli->prepare("SELECT price FROM car_colors WHERE id = ?");
                    break;
                case 'light':
                    $item_stmt = $mysqli->prepare("SELECT price FROM car_lights WHERE id = ?");
                    break;
            }
            
            if (isset($item_stmt)) {
                $item_stmt->bind_param("i", $row['item_id']);
                $item_stmt->execute();
                $item_result = $item_stmt->get_result();
                if ($item_row = $item_result->fetch_assoc()) {
                    $price = (float)$item_row['price'];
                }
                $item_stmt->close();
            }
            
            $item_total = $price * $row['quantity'];
            $calculated_total += $item_total;
            
            $cart_items[] = [
                'item_type' => $row['item_type'],
                'item_id' => $row['item_id'],
                'quantity' => $row['quantity'],
                'price' => $price
            ];
        }
        $stmt->close();
    }
} else {
    // Not logged in: Get from session
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $item_total = $item['price'] * $item['quantity'];
            $calculated_total += $item_total;
            
            $cart_items[] = [
                'item_type' => $item['item_type'],
                'item_id' => $item['item_id'],
                'quantity' => $item['quantity'],
                'price' => $item['price']
            ];
        }
    }
}

if (empty($cart_items)) {
    sendJsonResponse(['success' => false, 'message' => 'Your cart is empty']);
}

// Verify total matches (with small tolerance for floating point)
if (abs($calculated_total - $total) > 0.01) {
    sendJsonResponse(['success' => false, 'message' => 'Cart total mismatch. Please refresh and try again.']);
}

// Create order
try {
    $status = 'confirmed';
    $stmt = null;
    
    // Handle NULL user_id for guest orders
    // For guest orders, we need to use a valid user_id that exists in the users table
    // We'll use or create a system guest user
    if ($user_id === null) {
        // Check if guest user exists (typically with a special email or id)
        $guest_check = $mysqli->query("SELECT id FROM users WHERE email = 'guest@system' OR id = 0 LIMIT 1");
        if ($guest_check && $guest_check->num_rows > 0) {
            $guest_row = $guest_check->fetch_assoc();
            $insert_user_id = $guest_row['id'];
        } else {
            // Create a guest user account for system use
            $guest_name = 'Guest User';
            $guest_email = 'guest@system';
            $guest_password = password_hash(uniqid('guest_', true), PASSWORD_DEFAULT);
            
            $guest_role = 'user'; // Use 'user' role for guest accounts to ensure compatibility
            $guest_stmt = $mysqli->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)");
            if ($guest_stmt) {
                $guest_stmt->bind_param("ssss", $guest_name, $guest_email, $guest_password, $guest_role);
                if ($guest_stmt->execute()) {
                    $insert_user_id = $mysqli->insert_id;
                    $guest_stmt->close();
                } else {
                    $guest_stmt->close();
                    sendJsonResponse(['success' => false, 'message' => 'Failed to create guest user: ' . $mysqli->error]);
                }
            } else {
                sendJsonResponse(['success' => false, 'message' => 'Failed to prepare guest user statement: ' . $mysqli->error]);
            }
        }
    } else {
        $insert_user_id = $user_id;
    }
    
    $stmt = $mysqli->prepare("INSERT INTO orders (user_id, total_price, shipment_address, email, payment_method, status, name, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    if ($stmt) {
        $stmt->bind_param("idsssss", $insert_user_id, $total, $address, $email, $payment_method, $status, $name);
    }

    if (!$stmt) {
        sendJsonResponse(['success' => false, 'message' => 'Database error: Failed to prepare statement - ' . $mysqli->error]);
    }

    if (!$stmt->execute()) {
        $stmt->close();
        sendJsonResponse(['success' => false, 'message' => 'Failed to create order: ' . $stmt->error]);
    }

    $order_id = $mysqli->insert_id;
    $stmt->close();
    
    // Insert order items
    $order_items_stmt = $mysqli->prepare("INSERT INTO order_items (order_id, item_type, item_id, price, quantity) VALUES (?, ?, ?, ?, ?)");
    if (!$order_items_stmt) {
        sendJsonResponse(['success' => false, 'message' => 'Failed to prepare order items statement: ' . $mysqli->error]);
    }
    
    foreach ($cart_items as $item) {
        $order_items_stmt->bind_param("isidi", $order_id, $item['item_type'], $item['item_id'], $item['price'], $item['quantity']);
        if (!$order_items_stmt->execute()) {
            $order_items_stmt->close();
            sendJsonResponse(['success' => false, 'message' => 'Failed to insert order item: ' . $order_items_stmt->error]);
        }
    }
    $order_items_stmt->close();
    
    // Clear cart
    if ($user_id) {
        $clear_stmt = $mysqli->prepare("DELETE FROM cart_items WHERE user_id = ?");
        if ($clear_stmt) {
            $clear_stmt->bind_param("i", $user_id);
            $clear_stmt->execute();
            $clear_stmt->close();
        }
    } else {
        $_SESSION['cart'] = [];
    }
    
    // Send order confirmation email
    try {
        sendOrderConfirmationEmail($mysqli, $order_id, $email, $name);
    } catch (Exception $e) {
        // Log error but don't fail the order if email fails
        error_log("Failed to send order confirmation email for order #{$order_id}: " . $e->getMessage());
    }
    
    $mysqli->close();
    sendJsonResponse(['success' => true, 'message' => 'Order placed successfully', 'order_id' => $order_id]);
    
} catch (Exception $e) {
    if (isset($stmt) && $stmt) {
        $stmt->close();
    }
    if (isset($order_items_stmt) && $order_items_stmt) {
        $order_items_stmt->close();
    }
    if (isset($mysqli)) {
        $mysqli->close();
    }
    sendJsonResponse(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
?>

