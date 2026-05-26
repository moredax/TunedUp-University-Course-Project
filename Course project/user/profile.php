<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/sign-in.php");
    exit;
}

// Database connection
require_once __DIR__ . '/../config/database.php';
$mysqli = getDBConnection();

$user_id = $_SESSION['user_id'];
$error = "";
$success = "";

// Fetch current user data
$stmt = $mysqli->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
    } else {
        // User not found, destroy session
        session_destroy();
        header("Location: ../auth/sign-in.php");
        exit;
    }
    $stmt->close();
} else {
    die("Database error");
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        // Update name
        $name = trim($_POST['name'] ?? '');
        
        if (empty($name)) {
            $error = "Name cannot be empty.";
        } else {
            $stmt = $mysqli->prepare("UPDATE users SET name = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("si", $name, $user_id);
                if ($stmt->execute()) {
                    $_SESSION['name'] = $name;
                    $user['name'] = $name;
                    $success = "Profile updated successfully.";
                } else {
                    $error = "Failed to update profile.";
                }
                $stmt->close();
            } else {
                $error = "Database error.";
            }
        }
    } elseif ($action === 'change_password') {
        // Change password
        $old_password = $_POST['old_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
            $error = "All password fields are required.";
        } elseif (strlen($new_password) < 6) {
            $error = "New password must be at least 6 characters long.";
        } elseif ($new_password !== $confirm_password) {
            $error = "New passwords do not match.";
        } else {
            // Verify old password
            $stmt = $mysqli->prepare("SELECT password_hash FROM users WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows === 1) {
                    $user_data = $result->fetch_assoc();
                    $stmt->close();
                    
                    if (password_verify($old_password, $user_data['password_hash'])) {
                        // Update password
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        
                        $stmt = $mysqli->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                        if ($stmt) {
                            $stmt->bind_param("si", $hashed_password, $user_id);
                            if ($stmt->execute()) {
                                $success = "Password changed successfully.";
                            } else {
                                $error = "Failed to change password: " . $stmt->error;
                            }
                            $stmt->close();
                        } else {
                            $error = "Database error: " . $mysqli->error;
                        }
                    } else {
                        $error = "Old password is incorrect.";
                    }
                } else {
                    $error = "User not found.";
                    $stmt->close();
                }
            } else {
                $error = "Database error: " . $mysqli->error;
            }
        }
    } elseif ($action === 'delete_account') {
        // Delete account
        $confirm_delete = $_POST['confirm_delete'] ?? '';
        
        if ($confirm_delete === 'DELETE') {
            // Delete user's favorites first (foreign key constraint)
            $stmt = $mysqli->prepare("DELETE FROM favorites WHERE user_id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $stmt->close();
            }
            
            // Delete user's cart items
            $stmt = $mysqli->prepare("DELETE FROM cart_items WHERE user_id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $stmt->close();
            }
            
            // Delete user's reviews (and their photo files)
            $stmt = $mysqli->prepare("SELECT id, photo FROM reviews WHERE user_id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $review_id = $row['id'];
                    $photo_path = $row['photo'];
                    
                    // Delete photo file if it exists
                    if (!empty($photo_path) && file_exists('../' . $photo_path)) {
                        @unlink('../' . $photo_path);
                    }
                }
                $stmt->close();
            }
            
            // Delete reviews
            $stmt = $mysqli->prepare("DELETE FROM reviews WHERE user_id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $stmt->close();
            }
            
            // Delete user's orders (and order items)
            $stmt = $mysqli->prepare("SELECT id FROM orders WHERE user_id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $order_id = $row['id'];
                    $delete_order_items = $mysqli->prepare("DELETE FROM order_items WHERE order_id = ?");
                    if ($delete_order_items) {
                        $delete_order_items->bind_param("i", $order_id);
                        $delete_order_items->execute();
                        $delete_order_items->close();
                    }
                }
                $stmt->close();
            }
            
            // Delete orders
            $stmt = $mysqli->prepare("DELETE FROM orders WHERE user_id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $stmt->close();
            }
            
            // Finally delete user
            $stmt = $mysqli->prepare("DELETE FROM users WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $user_id);
                if ($stmt->execute()) {
                    $stmt->close();
                    session_destroy();
                    header("Location: ../auth/sign-in.php?deleted=1");
                    exit;
                } else {
                    $error = "Failed to delete account.";
                }
                if (isset($stmt)) $stmt->close();
            } else {
                $error = "Database error.";
            }
        } else {
            $error = "Please type 'DELETE' to confirm account deletion.";
        }
    }
}

$mysqli->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="../css/profile.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
</head>

<body>

    <!-- HEADER -->
    <header>
        <div class="header-left">
            <a href="../main.php" class="logo">TunedUp</a>
        </div>
        <div class="nav">
            <a href="../cart.php">Cart</a>
            <a href="saved.php">Saved</a>
            <a href="profile.php">Profile</a>
        </div>
    </header>

    <!-- MAIN -->
    <main class="profile-wrapper">

        <section class="profile-container">

            <!-- LEFT PANEL -->
            <div class="profile-left">
                <div class="avatar"></div>
                <h2 class="username"><?= htmlspecialchars($user['name']) ?></h2>
                <p class="email"><?= htmlspecialchars($user['email']) ?></p>

                <div class="left-action-row">
                    <button class="left-action-btn signout"
                            onclick="window.location.href='../auth/sign-out.php'">
                        Sign Out
                    </button>

                    <button class="left-action-btn danger" onclick="showDeleteModal()">
                        Delete
                    </button>
                </div>
                
                <div class="left-action-row" style="margin-top: 12px;">
                    <button class="left-action-btn orders"
                            onclick="window.location.href='orders.php'"
                            style="width: 100%;">
                        Orders
                    </button>
                </div>
            </div>


            <!-- RIGHT PANEL -->
            <section class="profile-content">

                <h1>Your profile</h1>

                <?php if ($error): ?>
                    <div style="background-color: #fee; color: #c33; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #fcc;">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div style="background-color: #efe; color: #3c3; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #cfc;">
                        <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>

                <form class="profile-form" method="POST" action="">
                    <input type="hidden" name="action" value="update_profile">

                    <div class="form-group">
                        <label>Full name</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                    </div>

                    <button type="submit" class="save-btn">Save changes</button>
                </form>

                <h2 class="section-title">Change password</h2>

                <form class="profile-form" method="POST" action="">
                    <input type="hidden" name="action" value="change_password">

                    <div class="form-group">
                        <label>Old password</label>
                        <input type="password" name="old_password" placeholder="Enter old password" required>
                    </div>

                    <div class="form-group">
                        <label>New password</label>
                        <input type="password" name="new_password" placeholder="Enter new password" required minlength="6">
                    </div>

                    <div class="form-group">
                        <label>Confirm new password</label>
                        <input type="password" name="confirm_password" placeholder="Confirm password" required minlength="6">
                    </div>

                    <button type="submit" class="save-btn">Change password</button>
                </form>

            </section>

        </section>

    </main>

    <!-- Delete Account Modal -->
    <div id="deleteModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: white; padding: 2rem; border-radius: 12px; max-width: 400px; width: 90%;">
            <h2 style="margin-top: 0; color: #c33;">Delete Account</h2>
            <p>Are you sure you want to delete your account? This action cannot be undone.</p>
            <p style="font-weight: 600; color: #c33;">Type <strong>DELETE</strong> to confirm:</p>
            <form method="POST" action="" style="margin-top: 1rem;">
                <input type="hidden" name="action" value="delete_account">
                <input type="text" name="confirm_delete" placeholder="Type DELETE" required style="width: 100%; padding: 0.5rem; margin-bottom: 1rem; border: 1px solid #ddd; border-radius: 6px;">
                <div style="display: flex; gap: 1rem;">
                    <button type="submit" style="flex: 1; padding: 0.75rem; background: #c33; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">
                        Delete Account
                    </button>
                    <button type="button" onclick="hideDeleteModal()" style="flex: 1; padding: 0.75rem; background: #ddd; color: #333; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showDeleteModal() {
            document.getElementById('deleteModal').style.display = 'flex';
        }

        function hideDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        // Close modal when clicking outside
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideDeleteModal();
            }
        });
    </script>

</body>
</html>

