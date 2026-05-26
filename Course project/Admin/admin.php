<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/sign-in.php?redirect=admin");
    exit;
}

// Check if role is set in session
if (!isset($_SESSION['role'])) {
    $_SESSION['role'] = 'user'; // Default to user if not set
}

require_once __DIR__ . '/../config/database.php';
$mysqli = getDBConnection();

// Verify role from database to ensure it hasn't changed
$user_id = $_SESSION['user_id'];
$stmt = $mysqli->prepare("SELECT role FROM users WHERE id = ?");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $actual_role = $user['role'];
        
        // Update session with actual role from database
        $_SESSION['role'] = $actual_role;
        
        // Check if user is still admin
        if ($actual_role !== 'admin') {
            header("Location: ../main.php?error=admin_access_denied");
            exit;
        }
    } else {
        // User doesn't exist in database
        session_destroy();
        header("Location: ../auth/sign-in.php?error=user_not_found");
        exit;
    }
    $stmt->close();
} else {
    die("Database error: " . $mysqli->error);
}

$section = $_GET['section'] ?? 'tools';
include 'admin_functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel - TunedUp</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../css/admin.css">

</head>
<body>
<header>
    <div class="header-left">
        <a href="../main.php" class="logo">TunedUp Admin</a>
    </div>
    <div class="nav">
        <a href="admin.php?section=cars">Cars</a>
        <a href="admin.php?section=colors">Colors</a>
        <a href="admin.php?section=lights">Lights</a>
        <a href="admin.php?section=tools">Tools</a>
        <a href="admin.php?section=tool_car">Tool-Car Relations</a>
        <a href="admin.php?section=stickers">Stickers</a>
        <a href="admin.php?section=orders">Orders</a>
        <a href="admin.php?section=reviews">Reviews</a>
        <a href="admin.php?section=users">Users</a>
    </div>
</header>

<main class="admin-main">
<?php
if(isset($_GET['error']) && $_GET['error'] === 'exists') {
    echo "<div style='background-color: #ffebee; color: #c62828; padding: 1rem; margin-bottom: 1rem; border-radius: 4px;'>This tool-car relation already exists!</div>";
}
switch($section) {
    case 'tools': showToolsAdmin(); break;
    case 'tool_car': showToolCarRelationsAdmin(); break;
    case 'stickers': showStickersAdmin(); break;
    case 'cars': showCarsAdmin(); break;
    case 'colors': showColorsAdmin(); break;
    case 'lights': showLightsAdmin(); break;
    case 'orders': showOrdersAdmin(); break;
    case 'reviews': showReviewsAdmin(); break;
    case 'users': showUsersAdmin(); break;
    default: echo "<h2>Welcome to Admin Panel</h2>";
}
?>
</main>
</body>
</html>
