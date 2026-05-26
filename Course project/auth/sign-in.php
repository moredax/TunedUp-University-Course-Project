<?php
session_start();

// Database connection
require_once __DIR__ . '/../config/database.php';
$mysqli = getDBConnection();

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate input
    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        // Prepare statement to prevent SQL injection
        $stmt = $mysqli->prepare("SELECT id, name, email, password_hash, role FROM users WHERE email = ?");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Verify password
                if (password_verify($password, $user['password_hash'])) {
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['name'] = $user['name'] ?? '';
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'] ?? 'user';
                    
                    // Redirect to main page
                    header("Location: ../main.php");
                    exit;
                } else {
                    $error = "Invalid email or password.";
                }
            } else {
                $error = "Invalid email or password.";
            }
            $stmt->close();
        } else {
            $error = "Database error. Please try again.";
        }
    }
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/sign-in-up.css">
</head>
<body>
    <header>
        <div class="header-left">
            <a href="../main.php" class="logo">TunedUp</a>
        </div>
        <div class="nav">
            <a href="../cart.php">Cart</a>
            <a href="../user/saved.php">Saved</a>
        </div>
    </header>

    <main class="main-container">
        <div class="content-card">
            <div class="image-section">
                <img src="../Login/car3.jpg">
            </div>
            <div class="form-section">
                <h1 class="welcome-title">Welcome back!</h1>
                <p class="welcome-subtitle">Glad to see you again!</p>
                
                <?php if ($error): ?>
                    <div style="background-color: #fee; color: #c33; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #fcc;">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div style="background-color: #efe; color: #3c3; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #cfc;">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="email">Enter email</label>
                        <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="password">Enter password</label>
                        <input type="password" id="password" name="password" required>
                    </div>

                    <button type="submit" class="sign-in-btn">SIGN IN</button>
                    <p class="sign-btn-subtitle">Don't have an account yet? <a href="sign-up.php">Sign up</a></p>
                </form>
            </div>
        </div>
    </main>
</body>
</html>

