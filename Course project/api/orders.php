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

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    sendJsonResponse(['success' => false, 'message' => 'You must be logged in to perform this action']);
}

// Database connection
try {
    require_once __DIR__ . '/../config/database.php';
    $mysqli = getDBConnection();
    if ($mysqli->connect_error) {
        sendJsonResponse(['success' => false, 'message' => 'Database connection failed: ' . $mysqli->connect_error]);
    }
} catch (Exception $e) {
    sendJsonResponse(['success' => false, 'message' => 'Database connection error']);
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($action !== 'cancel') {
    sendJsonResponse(['success' => false, 'message' => 'Invalid action']);
}

// Get order ID
$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;

if ($order_id <= 0) {
    sendJsonResponse(['success' => false, 'message' => 'Invalid order ID']);
}

// Verify the order belongs to the user and is confirmed
$stmt = $mysqli->prepare("SELECT id, status, user_id FROM orders WHERE id = ? AND user_id = ?");
if (!$stmt) {
    sendJsonResponse(['success' => false, 'message' => 'Database error: Failed to prepare statement - ' . $mysqli->error]);
}

$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $mysqli->close();
    sendJsonResponse(['success' => false, 'message' => 'Order not found or you do not have permission to cancel this order']);
}

$order = $result->fetch_assoc();
$stmt->close();

// Check if order is confirmed
if ($order['status'] !== 'confirmed') {
    $mysqli->close();
    sendJsonResponse(['success' => false, 'message' => 'Only confirmed orders can be cancelled']);
}

// Update order status to cancelled
$update_stmt = $mysqli->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ? AND user_id = ?");
if (!$update_stmt) {
    $mysqli->close();
    sendJsonResponse(['success' => false, 'message' => 'Database error: Failed to prepare update statement - ' . $mysqli->error]);
}

$update_stmt->bind_param("ii", $order_id, $user_id);

if (!$update_stmt->execute()) {
    $update_stmt->close();
    $mysqli->close();
    sendJsonResponse(['success' => false, 'message' => 'Failed to cancel order: ' . $update_stmt->error]);
}

$update_stmt->close();
$mysqli->close();

sendJsonResponse(['success' => true, 'message' => 'Order cancelled successfully']);
?>

