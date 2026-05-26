<?php
// Suppress error output to prevent breaking JSON response
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start output buffering to catch any unexpected output
ob_start();

// Set error handler to catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Server error: ' . $error['message']]);
        exit;
    }
});

try {
    session_start();
    header('Content-Type: application/json');
} catch (Exception $e) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Session error: ' . $e->getMessage()]);
    exit;
}

// Function to send JSON response and exit
function sendJsonResponse($data) {
    ob_clean();
    echo json_encode($data);
    exit;
}

// Database connection
try {
    require_once __DIR__ . '/../config/database.php';
    $mysqli = getDBConnection();
} catch (Exception $e) {
    sendJsonResponse(['success' => false, 'message' => 'Database connection error: ' . $e->getMessage()]);
}

// Get parameters
$item_type = $_GET['item_type'] ?? '';
$item_id = isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0;

if (empty($item_type) || $item_id <= 0) {
    sendJsonResponse(['success' => false, 'message' => 'Invalid parameters']);
}

// Check if user is logged in for favorite status
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$is_favorite = false;

if ($user_id) {
    $favorite_stmt = $mysqli->prepare("SELECT id FROM favorites WHERE user_id = ? AND item_type = ? AND item_id = ?");
    if ($favorite_stmt) {
        $favorite_stmt->bind_param("isi", $user_id, $item_type, $item_id);
        $favorite_stmt->execute();
        $favorite_result = $favorite_stmt->get_result();
        $is_favorite = $favorite_result->num_rows > 0;
        $favorite_stmt->close();
    }
}

// Fetch product details based on type
$product = null;
switch ($item_type) {
    case 'tool':
        $stmt = $mysqli->prepare("SELECT id, name, description, price, image FROM tools WHERE id = ?");
        break;
    case 'sticker':
        $stmt = $mysqli->prepare("SELECT id, name, description, price, image FROM stickers WHERE id = ?");
        break;
    case 'color':
        $stmt = $mysqli->prepare("SELECT id, name, price, image FROM car_colors WHERE id = ?");
        break;
    case 'light':
        $stmt = $mysqli->prepare("SELECT id, name, price, image FROM car_lights WHERE id = ?");
        break;
    default:
        sendJsonResponse(['success' => false, 'message' => 'Invalid item type']);
}

if (!$stmt) {
    sendJsonResponse(['success' => false, 'message' => 'Database error: Failed to prepare statement - ' . $mysqli->error]);
}

try {
    $stmt->bind_param("i", $item_id);
    if (!$stmt->execute()) {
        $stmt->close();
        $mysqli->close();
        sendJsonResponse(['success' => false, 'message' => 'Database query failed: ' . $stmt->error]);
    }
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt->close();
        $mysqli->close();
        sendJsonResponse(['success' => false, 'message' => 'Product not found']);
    }

    $product = $result->fetch_assoc();
    $stmt->close();
} catch (Exception $e) {
    if (isset($stmt)) $stmt->close();
    if (isset($mysqli)) $mysqli->close();
    sendJsonResponse(['success' => false, 'message' => 'Error fetching product: ' . $e->getMessage()]);
}

// Determine image path based on item type
// Path is relative to main.php (root directory)
try {
    $image_path = '';
    if ($item_type === 'tool') {
        $image_path = 'Tools/' . htmlspecialchars($product['image'] ?? '');
    } elseif ($item_type === 'sticker') {
        $image_path = 'Stickers/' . htmlspecialchars($product['image'] ?? '');
    } elseif ($item_type === 'color') {
        $image_path = 'Cars/' . htmlspecialchars($product['image'] ?? '');
    } elseif ($item_type === 'light') {
        $image_path = 'Lights/' . htmlspecialchars($product['image'] ?? '');
    }

    // Set description to empty string if not present (for colors and lights)
    if (!isset($product['description'])) {
        $product['description'] = '';
    }

    $product['image_path'] = $image_path;
    $product['item_type'] = $item_type;
    $product['is_favorite'] = $is_favorite;

    $mysqli->close();
    sendJsonResponse(['success' => true, 'product' => $product]);
} catch (Exception $e) {
    if (isset($mysqli)) $mysqli->close();
    sendJsonResponse(['success' => false, 'message' => 'Error processing product: ' . $e->getMessage()]);
}
?>

