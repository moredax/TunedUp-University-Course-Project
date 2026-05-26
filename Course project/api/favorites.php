<?php
session_start();
header('Content-Type: application/json');

// Database connection
require_once __DIR__ . '/../config/database.php';
$mysqli = getDBConnection();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to add favorites', 'requires_login' => true]);
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
        // Check if favorite already exists
        $check_stmt = $mysqli->prepare("SELECT id FROM favorites WHERE user_id = ? AND item_type = ? AND item_id = ?");
        if ($check_stmt) {
            $check_stmt->bind_param("isi", $user_id, $item_type, $item_id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows > 0) {
                $check_stmt->close();
                echo json_encode(['success' => false, 'message' => 'Item is already in favorites']);
                exit;
            }
            $check_stmt->close();
        }
        
        // Add to favorites
        $stmt = $mysqli->prepare("INSERT INTO favorites (user_id, item_type, item_id, created_at) VALUES (?, ?, ?, NOW())");
        if ($stmt) {
            $stmt->bind_param("isi", $user_id, $item_type, $item_id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Added to favorites']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add to favorites']);
            }
            $stmt->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
        break;
        
    case 'remove':
        // Remove from favorites
        $stmt = $mysqli->prepare("DELETE FROM favorites WHERE user_id = ? AND item_type = ? AND item_id = ?");
        if ($stmt) {
            $stmt->bind_param("isi", $user_id, $item_type, $item_id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Removed from favorites']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to remove from favorites']);
            }
            $stmt->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
        break;
        
    case 'check':
        // Check if item is in favorites
        $stmt = $mysqli->prepare("SELECT id FROM favorites WHERE user_id = ? AND item_type = ? AND item_id = ?");
        if ($stmt) {
            $stmt->bind_param("isi", $user_id, $item_type, $item_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $is_favorite = $result->num_rows > 0;
            $stmt->close();
            echo json_encode(['success' => true, 'is_favorite' => $is_favorite]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
        break;
        
    case 'toggle':
        // Toggle favorite status
        $check_stmt = $mysqli->prepare("SELECT id FROM favorites WHERE user_id = ? AND item_type = ? AND item_id = ?");
        if ($check_stmt) {
            $check_stmt->bind_param("isi", $user_id, $item_type, $item_id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            $exists = $result->num_rows > 0;
            $check_stmt->close();
            
            if ($exists) {
                // Remove
                $stmt = $mysqli->prepare("DELETE FROM favorites WHERE user_id = ? AND item_type = ? AND item_id = ?");
                if ($stmt) {
                    $stmt->bind_param("isi", $user_id, $item_type, $item_id);
                    if ($stmt->execute()) {
                        echo json_encode(['success' => true, 'is_favorite' => false, 'message' => 'Removed from favorites']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Failed to remove from favorites']);
                    }
                    $stmt->close();
                }
            } else {
                // Add
                $stmt = $mysqli->prepare("INSERT INTO favorites (user_id, item_type, item_id, created_at) VALUES (?, ?, ?, NOW())");
                if ($stmt) {
                    $stmt->bind_param("isi", $user_id, $item_type, $item_id);
                    if ($stmt->execute()) {
                        echo json_encode(['success' => true, 'is_favorite' => true, 'message' => 'Added to favorites']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Failed to add to favorites']);
                    }
                    $stmt->close();
                }
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

$mysqli->close();
?>

