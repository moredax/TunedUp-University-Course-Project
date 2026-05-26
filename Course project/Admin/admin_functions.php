<?php
function showToolsAdmin() {
    global $mysqli;
    $res = $mysqli->query("SELECT * FROM tools");
    echo "<h2>Tools</h2><a href='admin_edit.php?type=tool&action=add'>Add Tool</a>";
    echo "<table class='admin-table'><tr><th>ID</th><th>Image</th><th>Name</th><th>Price</th><th>Actions</th></tr>";
    while($row = $res->fetch_assoc()) {
        $image_path = '../Tools/' . htmlspecialchars($row['image'] ?? 'placeholder.png');
        echo "<tr>
            <td>{$row['id']}</td>
            <td><img src='$image_path' alt='{$row['name']}' class='admin-thumbnail' onerror=\"this.src='../Tools/placeholder.png'; this.onerror=null;\"></td>
            <td>{$row['name']}</td>
            <td>{$row['price']}</td>
            <td>
                <a href='admin_edit.php?type=tool&action=edit&id={$row['id']}'>Edit</a>
                <a href='admin_delete.php?type=tool&id={$row['id']}'>Delete</a>
            </td>
        </tr>";
    }
    echo "</table>";
}

function showStickersAdmin() {
    global $mysqli;
    $res = $mysqli->query("SELECT * FROM stickers");
    echo "<h2>Stickers</h2><a href='admin_edit.php?type=sticker&action=add'>Add Sticker</a>";
    echo "<table class='admin-table'><tr><th>ID</th><th>Image</th><th>Name</th><th>Price</th><th>Actions</th></tr>";
    while($row = $res->fetch_assoc()) {
        $image_path = '../Stickers/' . htmlspecialchars($row['image'] ?? 'placeholder.png');
        echo "<tr>
            <td>{$row['id']}</td>
            <td><img src='$image_path' alt='{$row['name']}' class='admin-thumbnail' onerror=\"this.src='../Stickers/placeholder.png'; this.onerror=null;\"></td>
            <td>{$row['name']}</td>
            <td>{$row['price']}</td>
            <td>
                <a href='admin_edit.php?type=sticker&action=edit&id={$row['id']}'>Edit</a>
                <a href='admin_delete.php?type=sticker&id={$row['id']}'>Delete</a>
            </td>
        </tr>";
    }
    echo "</table>";
}

function showCarsAdmin() {
    global $mysqli;
    $res = $mysqli->query("SELECT * FROM cars");
    echo "<h2>Cars</h2><a href='admin_edit.php?type=car&action=add'>Add Car</a>";
    echo "<table class='admin-table'><tr><th>ID</th><th>Brand</th><th>Model</th><th>Series</th><th>Actions</th></tr>";
    while($row = $res->fetch_assoc()) {
        echo "<tr>
            <td>{$row['id']}</td>
            <td>{$row['brand']}</td>
            <td>{$row['model']}</td>
            <td>{$row['series']}</td>
            <td>
                <a href='admin_edit.php?type=car&action=edit&id={$row['id']}'>Edit</a>
                <a href='admin_delete.php?type=car&id={$row['id']}'>Delete</a>
            </td>
        </tr>";
    }
    echo "</table>";
}

function showColorsAdmin() {
    global $mysqli;
    $res = $mysqli->query("SELECT car_colors.*, cars.brand, cars.model FROM car_colors LEFT JOIN cars ON car_colors.car_id = cars.id");
    echo "<h2>Colors</h2><a href='admin_edit.php?type=color&action=add'>Add Color</a>";
    echo "<table class='admin-table'><tr><th>ID</th><th>Image</th><th>Car</th><th>Name</th><th>Price</th><th>Hex Code</th><th>Actions</th></tr>";
    while($row = $res->fetch_assoc()) {
        $image_path = '../Cars/' . htmlspecialchars($row['image'] ?? 'placeholder.png');
        $hex_code = htmlspecialchars($row['hex_code'] ?? '');
        $hex_display = $hex_code ? $hex_code : '-';
        $hex_style = $hex_code ? "style='background-color: {$hex_code}; width: 30px; height: 30px; display: inline-block; border: 1px solid #ccc; vertical-align: middle; margin-right: 5px;'" : '';
        echo "<tr>
            <td>{$row['id']}</td>
            <td><img src='$image_path' alt='{$row['name']}' class='admin-thumbnail' onerror=\"this.src='../Cars/placeholder.png'; this.onerror=null;\"></td>
            <td>{$row['brand']} {$row['model']}</td>
            <td>{$row['name']}</td>
            <td>{$row['price']}</td>
            <td><span $hex_style></span>{$hex_display}</td>
            <td>
                <a href='admin_edit.php?type=color&action=edit&id={$row['id']}'>Edit</a>
                <a href='admin_delete.php?type=color&id={$row['id']}'>Delete</a>
            </td>
        </tr>";
    }
    echo "</table>";
}

function showLightsAdmin() {
    global $mysqli;
    $res = $mysqli->query("SELECT car_lights.*, cars.brand, cars.model FROM car_lights LEFT JOIN cars ON car_lights.car_id = cars.id");
    echo "<h2>Lights</h2><a href='admin_edit.php?type=light&action=add'>Add Light</a>";
    echo "<table class='admin-table'><tr><th>ID</th><th>Image</th><th>Car</th><th>Name</th><th>Price</th><th>Hex Code</th><th>Actions</th></tr>";
    while($row = $res->fetch_assoc()) {
        $image_path = '../Lights/' . htmlspecialchars($row['image'] ?? 'placeholder.png');
        $hex_code = htmlspecialchars($row['hex_code'] ?? '');
        $hex_display = $hex_code ? $hex_code : '-';
        $hex_style = $hex_code ? "style='background-color: {$hex_code}; width: 30px; height: 30px; display: inline-block; border: 1px solid #ccc; vertical-align: middle; margin-right: 5px;'" : '';
        echo "<tr>
            <td>{$row['id']}</td>
            <td><img src='$image_path' alt='{$row['name']}' class='admin-thumbnail' onerror=\"this.src='../Lights/placeholder.png'; this.onerror=null;\"></td>
            <td>{$row['brand']} {$row['model']}</td>
            <td>{$row['name']}</td>
            <td>{$row['price']}</td>
            <td><span $hex_style></span>{$hex_display}</td>
            <td>
                <a href='admin_edit.php?type=light&action=edit&id={$row['id']}'>Edit</a>
                <a href='admin_delete.php?type=light&id={$row['id']}'>Delete</a>
            </td>
        </tr>";
    }
    echo "</table>";
}

function showOrdersAdmin() {
    global $mysqli;
    $res = $mysqli->query("SELECT * FROM orders ORDER BY created_at DESC");
    echo "<h2>Orders</h2><a href='admin_edit.php?type=order&action=add'>Add Order</a>";
    echo "<table class='admin-table'><tr><th>ID</th><th>User ID</th><th>Name</th><th>Total</th><th>Status</th><th>Email</th><th>Shipment</th><th>Payment</th><th>Actions</th></tr>";
    while($row = $res->fetch_assoc()) {
        echo "<tr>
            <td>{$row['id']}</td>
            <td>{$row['user_id']}</td>
            <td>" . htmlspecialchars($row['name'] ?? '') . "</td>
            <td>{$row['total_price']}</td>
            <td>{$row['status']}</td>
            <td>{$row['email']}</td>
            <td>{$row['shipment_address']}</td>
            <td>{$row['payment_method']}</td>
            <td>
                <a href='admin_edit.php?type=order&action=edit&id={$row['id']}'>Edit</a>
            </td>
        </tr>";
    }
    echo "</table>";
}

function showUsersAdmin() {
    global $mysqli;
    $res = $mysqli->query("SELECT id, name, email, role, created_at FROM users");
    echo "<h2>Users</h2><a href='admin_edit.php?type=user&action=add'>Add User</a>";
    echo "<table class='admin-table'><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Created</th><th>Actions</th></tr>";
    while($row = $res->fetch_assoc()) {
        $toggleRole = $row['role'] == 'admin' ? 'user' : 'admin';
        echo "<tr>
            <td>{$row['id']}</td>
            <td>" . htmlspecialchars($row['name'] ?? '') . "</td>
            <td>{$row['email']}</td>
            <td>{$row['role']}</td>
            <td>{$row['created_at']}</td>
            <td>
                <a href='admin_edit.php?type=user&action=edit&id={$row['id']}'>Edit</a>
                <a href='admin_delete.php?type=user&id={$row['id']}'>Delete</a>
            </td>
        </tr>";
    }
    echo "</table>";
}

function showToolCarRelationsAdmin() {
    global $mysqli;
    $res = $mysqli->query("SELECT car_tools.id, car_tools.car_id, car_tools.tool_id, 
                           cars.brand, cars.model, cars.series, 
                           tools.name as tool_name 
                           FROM car_tools 
                           LEFT JOIN cars ON car_tools.car_id = cars.id 
                           LEFT JOIN tools ON car_tools.tool_id = tools.id 
                           ORDER BY cars.brand, cars.model, tools.name");
    echo "<h2>Tool-Car Relations</h2><a href='admin_edit.php?type=tool_car&action=add'>Add Relation</a>";
    echo "<table class='admin-table'><tr><th>ID</th><th>Car</th><th>Tool</th><th>Actions</th></tr>";
    while($row = $res->fetch_assoc()) {
        $car_display = htmlspecialchars($row['brand'] . ' ' . $row['model'] . ($row['series'] ? ' ' . $row['series'] : ''));
        $tool_display = htmlspecialchars($row['tool_name'] ?? 'Unknown');
        echo "<tr>
            <td>{$row['id']}</td>
            <td>{$car_display}</td>
            <td>{$tool_display}</td>
            <td>
                <a href='admin_delete.php?type=tool_car&id={$row['id']}'>Delete</a>
            </td>
        </tr>";
    }
    echo "</table>";
}

function showReviewsAdmin() {
    global $mysqli;
    $res = $mysqli->query("
        SELECT 
            r.id,
            r.user_id,
            r.item_type,
            r.item_id,
            r.rating,
            r.comment,
            r.photo,
            r.created_at,
            u.name as user_name,
            u.email as user_email,
            CASE 
                WHEN r.item_type = 'tool' THEN t.name
                WHEN r.item_type = 'sticker' THEN s.name
                WHEN r.item_type = 'color' THEN cc.name
                WHEN r.item_type = 'light' THEN cl.name
            END as item_name
        FROM reviews r
        INNER JOIN users u ON r.user_id = u.id
        LEFT JOIN tools t ON r.item_type = 'tool' AND r.item_id = t.id
        LEFT JOIN stickers s ON r.item_type = 'sticker' AND r.item_id = s.id
        LEFT JOIN car_colors cc ON r.item_type = 'color' AND r.item_id = cc.id
        LEFT JOIN car_lights cl ON r.item_type = 'light' AND r.item_id = cl.id
        ORDER BY r.created_at DESC
    ");
    echo "<h2>Reviews</h2>";
    echo "<table class='admin-table'><tr><th>ID</th><th>User</th><th>Item Type</th><th>Item Name</th><th>Rating</th><th>Comment</th><th>Photo</th><th>Created</th><th>Actions</th></tr>";
    while($row = $res->fetch_assoc()) {
        $user_display = htmlspecialchars($row['user_name'] ?? 'Unknown') . ' (' . htmlspecialchars($row['user_email'] ?? '') . ')';
        $item_name = htmlspecialchars($row['item_name'] ?? 'Unknown');
        $comment_preview = htmlspecialchars(substr($row['comment'] ?? '', 0, 50));
        if (strlen($row['comment'] ?? '') > 50) {
            $comment_preview .= '...';
        }
        $photo_display = '';
        if (!empty($row['photo'])) {
            $photo_path = '../' . htmlspecialchars($row['photo']);
            $photo_display = "<img src='$photo_path' alt='Review photo' class='admin-thumbnail' onerror=\"this.style.display='none';\">";
        } else {
            $photo_display = '-';
        }
        $rating_stars = str_repeat('★', $row['rating']) . str_repeat('☆', 5 - $row['rating']);
        echo "<tr>
            <td>{$row['id']}</td>
            <td>{$user_display}</td>
            <td>{$row['item_type']}</td>
            <td>{$item_name}</td>
            <td>{$rating_stars} ({$row['rating']}/5)</td>
            <td>{$comment_preview}</td>
            <td>{$photo_display}</td>
            <td>{$row['created_at']}</td>
            <td>
                <a href='admin_edit.php?type=review&action=edit&id={$row['id']}'>Edit</a>
                <a href='admin_delete.php?type=review&id={$row['id']}'>Delete</a>
            </td>
        </tr>";
    }
    echo "</table>";
}
?>
