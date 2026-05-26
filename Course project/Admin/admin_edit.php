<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Access denied");
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/email_functions.php';
$mysqli = getDBConnection();

$type = $_GET['type'];
$action = $_GET['action'];
$id = $_GET['id'] ?? null;

// LOAD DATA FOR EDIT (needed before form processing)
$data = null;
if($action === 'edit' && $id) {
    if($type === 'car') {
        $table = 'cars';
    } elseif($type === 'color') {
        $table = 'car_colors';
    } elseif($type === 'light') {
        $table = 'car_lights';
    } elseif($type === 'order') {
        $table = 'orders';
    } elseif($type === 'user') {
        $table = 'users';
    } elseif($type === 'review') {
        $table = 'reviews';
    } elseif($type === 'tool_car') {
        $table = 'car_tools';
    } else {
        $table = $type.'s';
    }
    $res = $mysqli->query("SELECT * FROM $table WHERE id=$id");
    $data = $res->fetch_assoc();
}

// SAVE FORM
if($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Image upload function
    function handleImageUpload($type, $existing_image = 'placeholder.png') {
        // Determine upload directory based on type
        if($type === 'color') {
            $upload_dir = '../Cars/';
        } elseif($type === 'light') {
            $upload_dir = '../Lights/';
        } elseif($type === 'tool') {
            $upload_dir = '../Tools/';
        } elseif($type === 'sticker') {
            $upload_dir = '../Stickers/';
        } else {
            // For tool, sticker, etc.
            $upload_dir = 'uploads/images/';
        }
        
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        if(isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if(in_array($file_extension, $allowed_extensions)) {
                $new_filename = $type . '_' . time() . '_' . uniqid() . '.' . $file_extension;
                $target_path = $upload_dir . $new_filename;
                
                if(move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                    // Delete old image if it's not placeholder and exists
                    if($existing_image && $existing_image !== 'placeholder.png') {
                        $old_path = $upload_dir . $existing_image;
                        if(file_exists($old_path)) {
                            @unlink($old_path);
                        }
                    }
                    return $new_filename;
                }
            }
        }
        return $existing_image;
    }

    switch($type) {

        case 'tool':
        case 'sticker':
            $name = $_POST['name'];
            $price = $_POST['price'];
            $desc = $_POST['description'];
            $existing_image = ($action === 'edit' && isset($data['image'])) ? $data['image'] : 'placeholder.png';
            $image = handleImageUpload($type, $existing_image);

            if($action === 'add') {
                $mysqli->query("INSERT INTO {$type}s (name,description,price,image)
                                VALUES ('$name','$desc','$price','$image')");
            } else {
                $mysqli->query("UPDATE {$type}s 
                                SET name='$name', description='$desc', price='$price', image='$image'
                                WHERE id=$id");
            }
            break;

        case 'car':
            $brand = $_POST['brand'];
            $model = $_POST['model'];
            $series = $_POST['series'];

            if($action === 'add') {
                $mysqli->query("INSERT INTO cars (brand,model,series)
                                VALUES ('$brand','$model','$series')");
            } else {
                $mysqli->query("UPDATE cars 
                                SET brand='$brand', model='$model', series='$series'
                                WHERE id=$id");
            }
            break;

        case 'color':
            $car_id = $_POST['car_id'];
            $name = $_POST['name'];
            $price = $_POST['price'];
            $hex_code = $_POST['hex_code'] ?? '';
            $existing_image = ($action === 'edit' && isset($data['image'])) ? $data['image'] : 'placeholder.png';
            $image = handleImageUpload('color', $existing_image);

            if($action === 'add') {
                $mysqli->query("INSERT INTO car_colors (car_id,name,price,image,hex_code)
                                VALUES ('$car_id','$name','$price','$image','$hex_code')");
            } else {
                $mysqli->query("UPDATE car_colors 
                                SET car_id='$car_id', name='$name', price='$price', image='$image', hex_code='$hex_code'
                                WHERE id=$id");
            }
            break;

        case 'light':
            $car_id = $_POST['car_id'];
            $name = $_POST['name'];
            $price = $_POST['price'];
            $hex_code = $_POST['hex_code'] ?? '';
            $existing_image = ($action === 'edit' && isset($data['image'])) ? $data['image'] : 'placeholder.png';
            $image = handleImageUpload('light', $existing_image);

            if($action === 'add') {
                $mysqli->query("INSERT INTO car_lights (car_id,name,price,image,hex_code)
                                VALUES ('$car_id','$name','$price','$image','$hex_code')");
            } else {
                $mysqli->query("UPDATE car_lights 
                                SET car_id='$car_id', name='$name', price='$price', image='$image', hex_code='$hex_code'
                                WHERE id=$id");
            }
            break;

        case 'order':
            $status = $mysqli->real_escape_string($_POST['status']);
            $name = $mysqli->real_escape_string($_POST['name']);
            
            if($action === 'add') {
                $user_id = (int)$_POST['user_id'];
                $total_price = (float)$_POST['total_price'];
                $email = $mysqli->real_escape_string($_POST['email']);
                $shipment_address = $mysqli->real_escape_string($_POST['shipment_address']);
                $payment_method = $mysqli->real_escape_string($_POST['payment_method']);
                
                $mysqli->query("INSERT INTO orders (user_id, total_price, status, email, shipment_address, payment_method, name)
                                VALUES ($user_id, $total_price, '$status', '$email', '$shipment_address', '$payment_method', '$name')");
            } else {
                // Get old status before updating
                $old_status = '';
                if (isset($data['status'])) {
                    $old_status = $data['status'];
                }
                
                $email = $mysqli->real_escape_string($_POST['email']);
                $shipment_address = $mysqli->real_escape_string($_POST['shipment_address']);
                
                $mysqli->query("UPDATE orders 
                                SET status='$status', email='$email', shipment_address='$shipment_address', name='$name' WHERE id=$id");
                
                // Send status change notification email if status changed
                if ($old_status && $old_status !== $status) {
                    try {
                        sendOrderStatusNotification($mysqli, $id, $old_status, $status);
                    } catch (Exception $e) {
                        // Log error but don't fail the update if email fails
                        error_log("Failed to send status notification email for order #{$id}: " . $e->getMessage());
                    }
                }
            }
            break;

        case 'review':
            $rating = (int)$_POST['rating'];
            $comment = $mysqli->real_escape_string($_POST['comment']);
            
            // Handle photo upload if provided
            $existing_photo = ($action === 'edit' && isset($data['photo'])) ? $data['photo'] : '';
            $photo_path = $existing_photo;
            
            if (isset($_FILES['photo']) && !empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../Reviews/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_extension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array($file_extension, $allowed_extensions)) {
                    $file_name = uniqid('review_', true) . '.' . $file_extension;
                    $file_path = $upload_dir . $file_name;
                    
                    if (move_uploaded_file($_FILES['photo']['tmp_name'], $file_path)) {
                        // Delete old photo if it exists
                        if ($existing_photo && file_exists('../' . $existing_photo)) {
                            @unlink('../' . $existing_photo);
                        }
                        $photo_path = 'Reviews/' . $file_name;
                    }
                }
            }
            
            if($action === 'edit') {
                $mysqli->query("UPDATE reviews 
                                SET rating=$rating, comment='$comment', photo='$photo_path'
                                WHERE id=$id");
            }
            break;

        case 'user':
            $name = $_POST['name'];
            $email = $_POST['email'];
            $password = $_POST['password'];
            $role = $_POST['role'];

            if($action === 'add') {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $mysqli->query("INSERT INTO users (name, email, password_hash, role)
                                VALUES ('$name', '$email', '$hashed_password', '$role')");
            } else {
                // Get current role before update
                $current_user_res = $mysqli->query("SELECT role FROM users WHERE id=$id");
                $current_user = $current_user_res->fetch_assoc();
                $old_role = $current_user['role'] ?? null;
                
                // Update the name and role in database
                $mysqli->query("UPDATE users SET name='$name', role='$role' WHERE id=$id");
                
                // If this is the current logged-in user, update session
                if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $id) {
                    $_SESSION['name'] = $name;
                    $_SESSION['role'] = $role;
                    
                    // If role changed from admin to user, redirect to main page
                    if ($old_role === 'admin' && $role === 'user') {
                        header("Location: ../main.php");
                        exit;
                    }
                }
            }
            break;

        case 'tool_car':
            $car_id = $_POST['car_id'];
            $tool_id = $_POST['tool_id'];
            
            // Check if relation already exists
            $check = $mysqli->query("SELECT id FROM car_tools WHERE car_id=$car_id AND tool_id=$tool_id");
            if($check->num_rows > 0) {
                header("Location: admin.php?section=tool_car&error=exists");
                exit;
            }
            
            if($action === 'add') {
                $mysqli->query("INSERT INTO car_tools (car_id, tool_id) VALUES ('$car_id', '$tool_id')");
            }
            break;
    }

    $redirect_section = ($type === 'tool_car') ? 'tool_car' : (($type === 'review') ? 'reviews' : $type.'s');
    header("Location: admin.php?section={$redirect_section}");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= ucfirst($action)." ".ucfirst($type) ?> - Admin</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
<div class="form-container">
    <h2><?= ucfirst($action)." ".ucfirst($type) ?></h2>
    
    <form method="post" class="admin-form" enctype="multipart/form-data">
    <?php if(in_array($type,['tool','sticker'])): ?>
        <div class="form-group">
            <label for="name">Name <span class="required">*</span></label>
            <input type="text" id="name" name="name" placeholder="Enter name" value="<?= htmlspecialchars($data['name'] ?? '') ?>" required>
        </div>
        
        <div class="form-group">
            <label for="description">Description <span class="required">*</span></label>
            <textarea id="description" name="description" placeholder="Enter description" rows="4" required><?= htmlspecialchars($data['description'] ?? '') ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="price">Price <span class="required">*</span></label>
            <input type="number" id="price" name="price" step="0.01" min="0" placeholder="0.00" value="<?= htmlspecialchars($data['price'] ?? '') ?>" required>
        </div>
        
        <div class="form-group">
            <label for="image">Image</label>
            <?php if(isset($data['image']) && $data['image']): ?>
                <div class="current-image">
                    <?php 
                    $image_dir = ($type === 'sticker') ? '../Stickers/' : '../Tools/';
                    $image_path = $image_dir . htmlspecialchars($data['image']);
                    ?>
                    <img src="<?= $image_path ?>" alt="Current image" onerror="this.src='<?= $image_dir ?>placeholder.png'; this.onerror=null;">
                    <p>Current: <?= htmlspecialchars($data['image']) ?></p>
                </div>
            <?php endif; ?>
            <input type="file" id="image" name="image" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
            <small>Leave empty to keep current image</small>
        </div>

    <?php elseif($type === 'car'): ?>
        <div class="form-group">
            <label for="brand">Brand <span class="required">*</span></label>
            <input type="text" id="brand" name="brand" placeholder="Enter brand name" value="<?= htmlspecialchars($data['brand'] ?? '') ?>" required>
        </div>
        
        <div class="form-group">
            <label for="model">Model <span class="required">*</span></label>
            <input type="text" id="model" name="model" placeholder="Enter model name" value="<?= htmlspecialchars($data['model'] ?? '') ?>" required>
        </div>
        
        <div class="form-group">
            <label for="series">Series</label>
            <input type="text" id="series" name="series" placeholder="Enter series (optional)" value="<?= htmlspecialchars($data['series'] ?? '') ?>">
        </div>

    <?php elseif(in_array($type,['color','light'])): ?>
        <div class="form-group">
            <label for="car_id">Car ID <span class="required">*</span></label>
            <input type="number" id="car_id" name="car_id" min="1" placeholder="Enter car ID" value="<?= htmlspecialchars($data['car_id'] ?? '') ?>" required>
        </div>
        
        <div class="form-group">
            <label for="name">Name <span class="required">*</span></label>
            <input type="text" id="name" name="name" placeholder="Enter name" value="<?= htmlspecialchars($data['name'] ?? '') ?>" required>
        </div>
        
        <div class="form-group">
            <label for="price">Price <span class="required">*</span></label>
            <input type="number" id="price" name="price" step="0.01" min="0" placeholder="0.00" value="<?= htmlspecialchars($data['price'] ?? '') ?>" required>
        </div>
        
        <div class="form-group">
            <label for="hex_code">Hex Code</label>
            <input type="text" id="hex_code" name="hex_code" placeholder="#FFFFFF" pattern="^#[0-9A-Fa-f]{6}$" value="<?= htmlspecialchars($data['hex_code'] ?? '') ?>">
            <small>Enter hex color code (e.g., #FF5733). Leave empty if not applicable.</small>
        </div>
        
        <div class="form-group">
            <label for="image">Image</label>
            <?php if(isset($data['image']) && $data['image']): ?>
                <div class="current-image">
                    <?php 
                    $image_dir = ($type === 'color') ? '../Cars/' : '../Lights/';
                    $image_path = $image_dir . htmlspecialchars($data['image']);
                    ?>
                    <img src="<?= $image_path ?>" alt="Current image" onerror="this.src='<?= $image_dir ?>placeholder.png'; this.onerror=null;">
                    <p>Current: <?= htmlspecialchars($data['image']) ?></p>
                </div>
            <?php endif; ?>
            <input type="file" id="image" name="image" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
            <small>Leave empty to keep current image</small>
        </div>

    <?php elseif($type === 'order'): ?>
        <?php if($action === 'add'): ?>
            <div class="form-group">
                <label for="user_id">User ID <span class="required">*</span></label>
                <input type="number" id="user_id" name="user_id" min="1" placeholder="Enter user ID" value="<?= htmlspecialchars($data['user_id'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label for="name">Name <span class="required">*</span></label>
                <input type="text" id="name" name="name" placeholder="Enter customer name" value="<?= htmlspecialchars($data['name'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label for="total_price">Total Price <span class="required">*</span></label>
                <input type="number" id="total_price" name="total_price" step="0.01" min="0" placeholder="0.00" value="<?= htmlspecialchars($data['total_price'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email <span class="required">*</span></label>
                <input type="email" id="email" name="email" placeholder="Enter email address" value="<?= htmlspecialchars($data['email'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label for="shipment_address">Shipment Address <span class="required">*</span></label>
                <textarea id="shipment_address" name="shipment_address" placeholder="Enter shipment address" rows="3" required><?= htmlspecialchars($data['shipment_address'] ?? '') ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="payment_method">Payment Method <span class="required">*</span></label>
                <input type="text" id="payment_method" name="payment_method" placeholder="Enter payment method" value="<?= htmlspecialchars($data['payment_method'] ?? '') ?>" required>
            </div>
        <?php else: ?>
            <div class="form-group">
                <label for="name">Name <span class="required">*</span></label>
                <input type="text" id="name" name="name" placeholder="Enter customer name" value="<?= htmlspecialchars($data['name'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email <span class="required">*</span></label>
                <input type="email" id="email" name="email" placeholder="Enter email address" value="<?= htmlspecialchars($data['email'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label for="shipment_address">Shipment Address <span class="required">*</span></label>
                <textarea id="shipment_address" name="shipment_address" placeholder="Enter shipment address" rows="3" required><?= htmlspecialchars($data['shipment_address'] ?? '') ?></textarea>
            </div>
        <?php endif; ?>
        
        <div class="form-group">
            <label for="status">Status <span class="required">*</span></label>
            <select id="status" name="status" required>
                <option value="confirmed" <?= (isset($data['status']) && $data['status'] === 'confirmed') ? 'selected' : '' ?>>Confirmed</option>
                <option value="in_delivery" <?= (isset($data['status']) && $data['status'] === 'in_delivery') ? 'selected' : '' ?>>In Delivery</option>
                <option value="delivered" <?= (isset($data['status']) && $data['status'] === 'delivered') ? 'selected' : '' ?>>Delivered</option>
                <option value="cancelled" <?= (isset($data['status']) && $data['status'] === 'cancelled') ? 'selected' : '' ?>>Cancelled</option>
            </select>
        </div>

    <?php elseif($type === 'review'): ?>
        <?php
        // Get item name for display
        $item_name = 'Unknown';
        if (isset($data['item_type']) && isset($data['item_id'])) {
            $item_type = $data['item_type'];
            $item_id = $data['item_id'];
            switch($item_type) {
                case 'tool':
                    $item_res = $mysqli->query("SELECT name FROM tools WHERE id=$item_id");
                    break;
                case 'sticker':
                    $item_res = $mysqli->query("SELECT name FROM stickers WHERE id=$item_id");
                    break;
                case 'color':
                    $item_res = $mysqli->query("SELECT name FROM car_colors WHERE id=$item_id");
                    break;
                case 'light':
                    $item_res = $mysqli->query("SELECT name FROM car_lights WHERE id=$item_id");
                    break;
            }
            if (isset($item_res) && $item_res) {
                $item_row = $item_res->fetch_assoc();
                $item_name = $item_row['name'] ?? 'Unknown';
            }
        }
        
        // Get user name for display
        $user_name = 'Unknown';
        if (isset($data['user_id'])) {
            $user_res = $mysqli->query("SELECT name, email FROM users WHERE id={$data['user_id']}");
            if ($user_res) {
                $user_row = $user_res->fetch_assoc();
                $user_name = ($user_row['name'] ?? 'Unknown') . ' (' . ($user_row['email'] ?? '') . ')';
            }
        }
        ?>
        <div class="form-group">
            <label>User</label>
            <input type="text" value="<?= htmlspecialchars($user_name) ?>" readonly>
        </div>
        
        <div class="form-group">
            <label>Item Type</label>
            <input type="text" value="<?= htmlspecialchars($data['item_type'] ?? '') ?>" readonly>
        </div>
        
        <div class="form-group">
            <label>Item Name</label>
            <input type="text" value="<?= htmlspecialchars($item_name) ?>" readonly>
        </div>
        
        <div class="form-group">
            <label for="rating">Rating <span class="required">*</span></label>
            <select id="rating" name="rating" required>
                <option value="1" <?= (isset($data['rating']) && $data['rating'] == 1) ? 'selected' : '' ?>>1 Star</option>
                <option value="2" <?= (isset($data['rating']) && $data['rating'] == 2) ? 'selected' : '' ?>>2 Stars</option>
                <option value="3" <?= (isset($data['rating']) && $data['rating'] == 3) ? 'selected' : '' ?>>3 Stars</option>
                <option value="4" <?= (isset($data['rating']) && $data['rating'] == 4) ? 'selected' : '' ?>>4 Stars</option>
                <option value="5" <?= (isset($data['rating']) && $data['rating'] == 5) ? 'selected' : '' ?>>5 Stars</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="comment">Comment <span class="required">*</span></label>
            <textarea id="comment" name="comment" placeholder="Enter review comment" rows="5" required><?= htmlspecialchars($data['comment'] ?? '') ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="photo">Photo</label>
            <?php if(isset($data['photo']) && $data['photo']): ?>
                <div class="current-image">
                    <img src="../<?= htmlspecialchars($data['photo']) ?>" alt="Current photo" class="admin-thumbnail" onerror="this.style.display='none';">
                    <p>Current: <?= htmlspecialchars($data['photo']) ?></p>
                </div>
            <?php endif; ?>
            <input type="file" id="photo" name="photo" accept="image/jpeg,image/jpg,image/png,image/gif">
            <small>Leave empty to keep current photo</small>
        </div>

    <?php elseif($type === 'user'): ?>
        <div class="form-group">
            <label for="name">Name <span class="required">*</span></label>
            <input type="text" id="name" name="name" placeholder="Enter name" value="<?= htmlspecialchars($data['name'] ?? '') ?>" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email <span class="required">*</span></label>
            <input type="email" id="email" name="email" placeholder="Enter email address" value="<?= htmlspecialchars($data['email'] ?? '') ?>" <?= $action === 'add' ? 'required' : 'readonly' ?>>
        </div>
        
        <?php if($action === 'add'): ?>
            <div class="form-group">
                <label for="password">Password <span class="required">*</span></label>
                <input type="password" id="password" name="password" placeholder="Enter password" required>
            </div>
        <?php endif; ?>
        
        <div class="form-group">
            <label for="role">Role <span class="required">*</span></label>
            <select id="role" name="role" required>
                <option value="user" <?= (isset($data['role']) && $data['role'] === 'user') ? 'selected' : '' ?>>User</option>
                <option value="admin" <?= (isset($data['role']) && $data['role'] === 'admin') ? 'selected' : '' ?>>Admin</option>
            </select>
        </div>

    <?php elseif($type === 'tool_car'): ?>
        <?php
        // Fetch all cars for dropdown
        $cars_res = $mysqli->query("SELECT * FROM cars ORDER BY brand, model, series");
        $cars_list = [];
        while($car_row = $cars_res->fetch_assoc()) {
            $cars_list[] = $car_row;
        }
        
        // Fetch all tools for dropdown
        $tools_res = $mysqli->query("SELECT * FROM tools ORDER BY name");
        $tools_list = [];
        while($tool_row = $tools_res->fetch_assoc()) {
            $tools_list[] = $tool_row;
        }
        ?>
        <div class="form-group">
            <label for="car_id">Car <span class="required">*</span></label>
            <select id="car_id" name="car_id" required>
                <option value="">Select a car</option>
                <?php foreach($cars_list as $car): ?>
                    <option value="<?= $car['id'] ?>" <?= (isset($data['car_id']) && $data['car_id'] == $car['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($car['brand'] . ' ' . $car['model'] . ($car['series'] ? ' ' . $car['series'] : '')) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="tool_id">Tool <span class="required">*</span></label>
            <select id="tool_id" name="tool_id" required>
                <option value="">Select a tool</option>
                <?php foreach($tools_list as $tool): ?>
                    <option value="<?= $tool['id'] ?>" <?= (isset($data['tool_id']) && $data['tool_id'] == $tool['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($tool['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>

    <div class="form-actions">
        <button type="submit" class="btn-submit">Save</button>
        <a href="admin.php?section=<?= ($type === 'tool_car') ? 'tool_car' : (($type === 'review') ? 'reviews' : $type.'s') ?>" class="btn-cancel">Cancel</a>
    </div>
    </form>
</div>
</body>
</html>
