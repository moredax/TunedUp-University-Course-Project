<?php
session_start();
header('Content-Type: application/json');

// Database connection
require_once __DIR__ . '/../config/database.php';
$mysqli = getDBConnection();

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$item_type = $_POST['item_type'] ?? $_GET['item_type'] ?? '';
$item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : (isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0);
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : (isset($_GET['quantity']) ? (int)$_GET['quantity'] : 1);

// Validate item_type
$allowed_types = ['tool', 'sticker', 'color', 'light'];
if ($item_type && !in_array($item_type, $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Invalid item type']);
    exit;
}

// Get item price based on type
function getItemPrice($mysqli, $item_type, $item_id) {
    $price = 0;
    switch ($item_type) {
        case 'tool':
            $stmt = $mysqli->prepare("SELECT price FROM tools WHERE id = ?");
            break;
        case 'sticker':
            $stmt = $mysqli->prepare("SELECT price FROM stickers WHERE id = ?");
            break;
        case 'color':
            $stmt = $mysqli->prepare("SELECT price FROM car_colors WHERE id = ?");
            break;
        case 'light':
            $stmt = $mysqli->prepare("SELECT price FROM car_lights WHERE id = ?");
            break;
        default:
            return 0;
    }
    
    if ($stmt) {
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $price = (float)$row['price'];
        }
        $stmt->close();
    }
    return $price;
}

// Get item details (name, image) based on type
function getItemDetails($mysqli, $item_type, $item_id) {
    $details = ['name' => '', 'image' => ''];
    switch ($item_type) {
        case 'tool':
            $stmt = $mysqli->prepare("SELECT name, image FROM tools WHERE id = ?");
            break;
        case 'sticker':
            $stmt = $mysqli->prepare("SELECT name, image FROM stickers WHERE id = ?");
            break;
        case 'color':
            $stmt = $mysqli->prepare("SELECT name, image FROM car_colors WHERE id = ?");
            break;
        case 'light':
            $stmt = $mysqli->prepare("SELECT name, image FROM car_lights WHERE id = ?");
            break;
        default:
            return $details;
    }
    
    if ($stmt) {
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $details['name'] = $row['name'] ?? '';
            $details['image'] = $row['image'] ?? '';
        }
        $stmt->close();
    }
    return $details;
}

switch ($action) {
    case 'add':
        if (!$item_type || $item_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid item type or ID']);
            exit;
        }
        
        if ($user_id) {
            // Logged in: Store in database
            // Check if item already exists in cart
            $check_stmt = $mysqli->prepare("SELECT id, quantity FROM cart_items WHERE user_id = ? AND item_type = ? AND item_id = ?");
            if ($check_stmt) {
                $check_stmt->bind_param("isi", $user_id, $item_type, $item_id);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                
                if ($result->num_rows > 0) {
                    // Update quantity
                    $row = $result->fetch_assoc();
                    $new_quantity = $row['quantity'] + $quantity;
                    $check_stmt->close();
                    
                    $update_stmt = $mysqli->prepare("UPDATE cart_items SET quantity = ? WHERE user_id = ? AND item_type = ? AND item_id = ?");
                    if ($update_stmt) {
                        $update_stmt->bind_param("iisi", $new_quantity, $user_id, $item_type, $item_id);
                        if ($update_stmt->execute()) {
                            echo json_encode(['success' => true, 'message' => 'Cart updated']);
                        } else {
                            echo json_encode(['success' => false, 'message' => 'Failed to update cart']);
                        }
                        $update_stmt->close();
                    }
                } else {
                    // Insert new item
                    $check_stmt->close();
                    $stmt = $mysqli->prepare("INSERT INTO cart_items (user_id, item_type, item_id, quantity) VALUES (?, ?, ?, ?)");
                    if ($stmt) {
                        $stmt->bind_param("isii", $user_id, $item_type, $item_id, $quantity);
                        if ($stmt->execute()) {
                            echo json_encode(['success' => true, 'message' => 'Added to cart']);
                        } else {
                            echo json_encode(['success' => false, 'message' => 'Failed to add to cart']);
                        }
                        $stmt->close();
                    }
                }
            }
        } else {
            // Not logged in: Store in session
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }
            
            $key = $item_type . '_' . $item_id;
            if (isset($_SESSION['cart'][$key])) {
                $_SESSION['cart'][$key]['quantity'] += $quantity;
            } else {
                $price = getItemPrice($mysqli, $item_type, $item_id);
                if ($price <= 0) {
                    echo json_encode(['success' => false, 'message' => 'Item not found or invalid']);
                    exit;
                }
                $details = getItemDetails($mysqli, $item_type, $item_id);
                $_SESSION['cart'][$key] = [
                    'item_type' => $item_type,
                    'item_id' => $item_id,
                    'quantity' => $quantity,
                    'price' => $price,
                    'name' => $details['name'] ?: 'Unknown Item',
                    'image' => $details['image'] ?: 'placeholder.png'
                ];
            }
            echo json_encode(['success' => true, 'message' => 'Added to cart']);
        }
        break;
        
    case 'remove':
        if (!$item_type || $item_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid item type or ID']);
            exit;
        }
        
        if ($user_id) {
            // Logged in: Remove from database
            $stmt = $mysqli->prepare("DELETE FROM cart_items WHERE user_id = ? AND item_type = ? AND item_id = ?");
            if ($stmt) {
                $stmt->bind_param("isi", $user_id, $item_type, $item_id);
                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Removed from cart']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to remove from cart']);
                }
                $stmt->close();
            }
        } else {
            // Not logged in: Remove from session
            $key = $item_type . '_' . $item_id;
            if (isset($_SESSION['cart'][$key])) {
                unset($_SESSION['cart'][$key]);
                echo json_encode(['success' => true, 'message' => 'Removed from cart']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Item not found in cart']);
            }
        }
        break;
        
    case 'update':
        if (!$item_type || $item_id <= 0 || $quantity <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            exit;
        }
        
        if ($user_id) {
            // Logged in: Update in database
            $stmt = $mysqli->prepare("UPDATE cart_items SET quantity = ? WHERE user_id = ? AND item_type = ? AND item_id = ?");
            if ($stmt) {
                $stmt->bind_param("iisi", $quantity, $user_id, $item_type, $item_id);
                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Cart updated']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update cart']);
                }
                $stmt->close();
            }
        } else {
            // Not logged in: Update in session
            $key = $item_type . '_' . $item_id;
            if (isset($_SESSION['cart'][$key])) {
                $_SESSION['cart'][$key]['quantity'] = $quantity;
                echo json_encode(['success' => true, 'message' => 'Cart updated']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Item not found in cart']);
            }
        }
        break;
        
    case 'get':
        // Get all cart items
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
                    $price = getItemPrice($mysqli, $row['item_type'], $row['item_id']);
                    $details = getItemDetails($mysqli, $row['item_type'], $row['item_id']);
                    $item_total = $price * $row['quantity'];
                    $total += $item_total;
                    
                    $cart_items[] = [
                        'item_type' => $row['item_type'],
                        'item_id' => $row['item_id'],
                        'quantity' => $row['quantity'],
                        'price' => $price,
                        'name' => $details['name'],
                        'image' => $details['image'],
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
        
        echo json_encode([
            'success' => true,
            'items' => $cart_items,
            'total' => round($total, 2),
            'count' => count($cart_items)
        ]);
        break;
        
    case 'clear':
        if ($user_id) {
            // Logged in: Clear from database
            $stmt = $mysqli->prepare("DELETE FROM cart_items WHERE user_id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $user_id);
                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Cart cleared']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to clear cart']);
                }
                $stmt->close();
            }
        } else {
            // Not logged in: Clear from session
            $_SESSION['cart'] = [];
            echo json_encode(['success' => true, 'message' => 'Cart cleared']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

$mysqli->close();
?>

