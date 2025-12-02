<?php
session_start();
require_once '../config/database.php';
require_once 'config/admin_auth.php';

checkAdminLogin();
$admin = getCurrentAdmin();

$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$success_message = '';
$error_message = '';

if ($user_id == 0) {
    header("Location: users.php");
    exit();
}

// Get user details
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header("Location: users.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $new_password = trim($_POST['new_password']);
    
    // Validate inputs
    if (empty($username) || empty($full_name) || empty($email)) {
        $error_message = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } else {
        // Check if username or email already exists (except for current user)
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE (username = ? OR email = ?) AND user_id != ?");
        $stmt->bind_param("ssi", $username, $email, $user_id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $error_message = 'Username or email already exists.';
        } else {
            // Update user
            if (!empty($new_password)) {
                // Update with new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET username = ?, full_name = ?, email = ?, password = ?, updated_at = NOW() WHERE user_id = ?");
                $stmt->bind_param("ssssi", $username, $full_name, $email, $hashed_password, $user_id);
            } else {
                // Update without password change
                $stmt = $conn->prepare("UPDATE users SET username = ?, full_name = ?, email = ?, updated_at = NOW() WHERE user_id = ?");
                $stmt->bind_param("sssi", $username, $full_name, $email, $user_id);
            }
            
            if ($stmt->execute()) {
                $success_message = 'User updated successfully!';
                logAdminActivity('update', 'user', $user_id, 'Updated user: ' . $username);
                
                // Refresh user data
                $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();
            } else {
                $error_message = 'Failed to update user.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - <?php echo htmlspecialchars($user['full_name']); ?></title>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .edit-form {
            background: white;
            border-radius: 15px;
            padding: 40px;
            max-width: 800px;
        }

        .form-section {
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 18px;
            color: #333;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .form-label.required::after {
            content: ' *';
            color: #f44336;
        }

        .form-input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: #8B0000;
        }

        .form-help {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
        }

        .btn-save {
            padding: 12px 30px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-save:hover {
            background: #45a049;
            transform: translateY(-2px);
        }

        .btn-cancel {
            padding: 12px 30px;
            background: #f0f0f0;
            color: #333;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .btn-cancel:hover {
            background: #e0e0e0;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-size: 14px;
            font-weight: 500;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="admin-sidebar">
        <div class="admin-logo">
            <span class="logo-icon">🔧</span>
            <span class="logo-text">Admin Panel</span>
        </div>
        <nav class="admin-nav">
            <a href="dashboard.php"><span>📊</span> Dashboard</a>
            <a href="users.php" class="active"><span>👥</span> Users</a>
            <a href="vocabulary.php"><span>📚</span> Vocabulary</a>
            <a href="themes.php"><span>🎨</span> Themes</a>
            <a href="quizzes.php"><span>📝</span> Quizzes</a>
            <a href="activity.php"><span>📈</span> Activity</a>
            <a href="settings.php"><span>⚙️</span> Settings</a>
        </nav>
        <div class="admin-user">
            <div class="admin-avatar">👤</div>
            <div class="admin-info">
                <div class="admin-name"><?php echo htmlspecialchars($admin['full_name']); ?></div>
                <div class="admin-role"><?php echo ucfirst($admin['role']); ?></div>
            </div>
            <a href="logout.php" class="logout-btn" title="Logout">🚪</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="admin-main">
        <div class="admin-header">
            <h1>Edit User</h1>
            <div class="header-actions">
                <a href="user-detail.php?id=<?php echo $user_id; ?>" class="btn-view-site">View Details</a>
                <a href="users.php" class="btn-view-site">← Back to Users</a>
            </div>
        </div>

        <div class="content-wrapper">
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    ✅ <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    ❌ <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="edit-form">
                <!-- Basic Information -->
                <div class="form-section">
                    <div class="section-title">👤 Basic Information</div>
                    
                    <div class="form-group">
                        <label class="form-label required">Username</label>
                        <input 
                            type="text" 
                            name="username" 
                            class="form-input" 
                            value="<?php echo htmlspecialchars($user['username']); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label required">Full Name</label>
                        <input 
                            type="text" 
                            name="full_name" 
                            class="form-input" 
                            value="<?php echo htmlspecialchars($user['full_name']); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label required">Email</label>
                        <input 
                            type="email" 
                            name="email" 
                            class="form-input" 
                            value="<?php echo htmlspecialchars($user['email']); ?>"
                            required
                        >
                    </div>
                </div>

                <!-- Password Section -->
                <div class="form-section">
                    <div class="section-title">🔐 Change Password</div>
                    
                    <div class="form-group">
                        <label class="form-label">New Password</label>
                        <input 
                            type="password" 
                            name="new_password" 
                            class="form-input" 
                            placeholder="Leave blank to keep current password"
                        >
                        <div class="form-help">Only fill this if you want to change the user's password</div>
                    </div>
                </div>

                <!-- Account Info (Read-only) -->
                <div class="form-section">
                    <div class="section-title">📋 Account Information</div>
                    
                    <div class="form-group">
                        <label class="form-label">User ID</label>
                        <input 
                            type="text" 
                            class="form-input" 
                            value="#<?php echo $user['user_id']; ?>"
                            readonly
                            style="background: #f8f9fa; cursor: not-allowed;"
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label">Registration Date</label>
                        <input 
                            type="text" 
                            class="form-input" 
                            value="<?php echo date('F d, Y g:i A', strtotime($user['created_at'])); ?>"
                            readonly
                            style="background: #f8f9fa; cursor: not-allowed;"
                        >
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn-save">💾 Save Changes</button>
                    <a href="user-detail.php?id=<?php echo $user_id; ?>" class="btn-cancel">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Auto-hide alerts after 3 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.3s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            });
        }, 3000);
    </script>
</body>
</html>
