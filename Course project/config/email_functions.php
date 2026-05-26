<?php
/**
 * Email Notification Functions
 * 
 * This file contains functions for sending order-related emails using PHP's mail() function.
 */

/**
 * Send order confirmation email to customer
 * 
 * @param mysqli $mysqli Database connection
 * @param int $order_id Order ID
 * @param string $customer_email Customer email address
 * @param string $customer_name Customer name
 * @return bool True if email sent successfully, false otherwise
 */
function sendOrderConfirmationEmail($mysqli, $order_id, $customer_email, $customer_name) {
    // Get order details
    $order_stmt = $mysqli->prepare("SELECT * FROM orders WHERE id = ?");
    if (!$order_stmt) {
        return false;
    }
    
    $order_stmt->bind_param("i", $order_id);
    $order_stmt->execute();
    $order_result = $order_stmt->get_result();
    
    if ($order_result->num_rows === 0) {
        $order_stmt->close();
        return false;
    }
    
    $order = $order_result->fetch_assoc();
    $order_stmt->close();
    
    // Get order items
    $items_stmt = $mysqli->prepare("SELECT * FROM order_items WHERE order_id = ?");
    if (!$items_stmt) {
        return false;
    }
    
    $items_stmt->bind_param("i", $order_id);
    $items_stmt->execute();
    $items_result = $items_stmt->get_result();
    
    $order_items = [];
    while ($item_row = $items_result->fetch_assoc()) {
        // Get item name based on type
        $item_name = '';
        switch ($item_row['item_type']) {
            case 'tool':
                $item_query = $mysqli->prepare("SELECT name FROM tools WHERE id = ?");
                break;
            case 'sticker':
                $item_query = $mysqli->prepare("SELECT name FROM stickers WHERE id = ?");
                break;
            case 'color':
                $item_query = $mysqli->prepare("SELECT name FROM car_colors WHERE id = ?");
                break;
            case 'light':
                $item_query = $mysqli->prepare("SELECT name FROM car_lights WHERE id = ?");
                break;
            default:
                $item_query = null;
        }
        
        if ($item_query) {
            $item_query->bind_param("i", $item_row['item_id']);
            $item_query->execute();
            $item_result = $item_query->get_result();
            if ($item_data = $item_result->fetch_assoc()) {
                $item_name = $item_data['name'];
            }
            $item_query->close();
        }
        
        $order_items[] = [
            'name' => $item_name ?: 'Unknown Item',
            'type' => ucfirst($item_row['item_type']),
            'quantity' => $item_row['quantity'],
            'price' => $item_row['price']
        ];
    }
    $items_stmt->close();
    
    // Prepare email content
    $to = $customer_email;
    $subject = "Order Confirmation - Order #{$order_id}";
    
    // Build email body
    $message = "Dear {$customer_name},\n\n";
    $message .= "Thank you for your order! We're excited to confirm that your order has been received and is being processed.\n\n";
    $message .= "ORDER DETAILS:\n";
    $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $message .= "Order ID: #{$order_id}\n";
    $message .= "Order Date: " . date('F j, Y g:i A', strtotime($order['created_at'])) . "\n";
    $message .= "Status: " . ucfirst($order['status']) . "\n\n";
    
    $message .= "ORDER ITEMS:\n";
    $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    foreach ($order_items as $item) {
        $item_total = $item['price'] * $item['quantity'];
        $message .= "• {$item['name']} ({$item['type']})\n";
        $message .= "  Quantity: {$item['quantity']} x $" . number_format($item['price'], 2) . " = $" . number_format($item_total, 2) . "\n\n";
    }
    
    $message .= "SHIPPING INFORMATION:\n";
    $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $message .= "Shipping Address: {$order['shipment_address']}\n";
    $message .= "Payment Method: " . ucfirst($order['payment_method']) . "\n\n";
    
    $message .= "TOTAL AMOUNT: $" . number_format($order['total_price'], 2) . "\n\n";
    
    $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    $message .= "We'll send you another email once your order ships. If you have any questions, please don't hesitate to contact us.\n\n";
    $message .= "Thank you for choosing TunedUp!\n\n";
    $message .= "Best regards,\n";
    $message .= "TunedUp Team";
    
    // Email headers
    $headers = "From: TunedUp <tunedup@eigiva.lt>\r\n";
    $headers .= "Reply-To: 	tunedup@eigiva.lt\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    // Send email
    return mail($to, $subject, $message, $headers);
}

/**
 * Send order status change notification email to customer
 * 
 * @param mysqli $mysqli Database connection
 * @param int $order_id Order ID
 * @param string $old_status Previous order status
 * @param string $new_status New order status
 * @return bool True if email sent successfully, false otherwise
 */
function sendOrderStatusNotification($mysqli, $order_id, $old_status, $new_status) {
    // Get order details
    $order_stmt = $mysqli->prepare("SELECT * FROM orders WHERE id = ?");
    if (!$order_stmt) {
        return false;
    }
    
    $order_stmt->bind_param("i", $order_id);
    $order_stmt->execute();
    $order_result = $order_stmt->get_result();
    
    if ($order_result->num_rows === 0) {
        $order_stmt->close();
        return false;
    }
    
    $order = $order_result->fetch_assoc();
    $order_stmt->close();
    
    // Don't send email if status hasn't actually changed
    if ($old_status === $new_status) {
        return true;
    }
    
    $customer_email = $order['email'];
    $customer_name = $order['name'] ?: 'Customer';
    
    // Prepare email content based on status
    $to = $customer_email;
    $subject = "Order Status Update - Order #{$order_id}";
    
    // Build email body
    $message = "Dear {$customer_name},\n\n";
    $message .= "We're writing to inform you that your order status has been updated.\n\n";
    $message .= "ORDER INFORMATION:\n";
    $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $message .= "Order ID: #{$order_id}\n";
    $message .= "Previous Status: " . ucfirst(str_replace('_', ' ', $old_status)) . "\n";
    $message .= "Current Status: " . ucfirst(str_replace('_', ' ', $new_status)) . "\n\n";
    
    // Add status-specific message
    switch ($new_status) {
        case 'confirmed':
            $message .= "Your order has been confirmed and is being prepared for shipment.\n";
            $message .= "We'll notify you once your order ships.\n\n";
            break;
            
        case 'in_delivery':
            $message .= "Great news! Your order is now in transit and on its way to you.\n";
            $message .= "You can expect to receive your order soon. Please ensure someone is available to receive the delivery.\n\n";
            break;
            
        case 'delivered':
            $message .= "Your order has been delivered! We hope you're happy with your purchase.\n";
            $message .= "If you have any questions or concerns, please don't hesitate to contact us.\n\n";
            break;
            
        case 'cancelled':
            $message .= "We're sorry to inform you that your order has been cancelled.\n";
            $message .= "If you have any questions about this cancellation, please contact our support team.\n\n";
            break;
            
        default:
            $message .= "Your order status has been updated. If you have any questions, please contact us.\n\n";
    }
    
    $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    $message .= "Thank you for your patience and understanding.\n\n";
    $message .= "Best regards,\n";
    $message .= "TunedUp Team";
    
    // Email headers
    $headers = "From: TunedUp <noreply@tunedup.com>\r\n";
    $headers .= "Reply-To: noreply@tunedup.com\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    // Send email
    return mail($to, $subject, $message, $headers);
}

?>

