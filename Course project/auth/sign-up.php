<?php
session_start();

// Database connection
require_once __DIR__ . '/../config/database.php';
$mysqli = getDBConnection();

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['passwordConfirm'] ?? '';

    // Validate input
    if (empty($name) || empty($email) || empty($password) || empty($passwordConfirm)) {
        $error = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($password !== $passwordConfirm) {
        $error = "Passwords do not match.";
    } else {
        // Check if email already exists
        $stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ?");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = "An account with this email already exists.";
                $stmt->close();
            } else {
                // Hash password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // Close the SELECT statement
                $stmt->close();
                
                // Insert new user (matching admin panel pattern)
                $stmt = $mysqli->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'user')");
                if ($stmt) {
                    $stmt->bind_param("sss", $name, $email, $hashedPassword);
                    
                    if ($stmt->execute()) {
                        // Get the new user ID
                        $user_id = $mysqli->insert_id;
                        
                        // Set session variables
                        $_SESSION['user_id'] = $user_id;
                        $_SESSION['name'] = $name;
                        $_SESSION['email'] = $email;
                        $_SESSION['role'] = 'user';
                        
                        $stmt->close();
                        
                        // Redirect to main page
                        header("Location: ../main.php");
                        exit;
                    } else {
                        $error = "Registration failed: " . $stmt->error;
                        $stmt->close();
                    }
                } else {
                    $error = "Database error: " . $mysqli->error;
                }
            }
        } else {
            $error = "Database error: " . $mysqli->error;
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
            <a href="../user/profile.php">Profile</a>
        </div>
    </header>

    <main class="main-container">
        <div class="content-card">
            <div class="image-section">
                <img src="../Login/car3.jpg">
            </div>
            <div class="form-section">
                <h1 class="welcome-title">Get Started</h1>
                <p class="welcome-subtitle">Welcome to TunedUp - Let's get started</p>
                
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
                        <label for="name">Enter your name</label>
                        <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Enter email</label>
                        <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Enter password</label>
                        <input type="password" id="password" name="password" required>
                    </div>

                    <div class="form-group">
                        <label for="passwordConfirm">Confirm password</label>
                        <input type="password" id="passwordConfirm" name="passwordConfirm" required>
                    </div>

                    <button type="submit" class="sign-in-btn">SIGN UP</button>
                    <p class="sign-btn-subtitle">Already have an account? <a href="sign-in.php">Sign in</a></p>
                </form>
            </div>
        </div>
    </main>
</body>
</html>

