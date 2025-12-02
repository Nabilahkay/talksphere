<?php
session_start();
require_once '../config/database.php';
require_once 'config/admin_auth.php';

checkAdminLogin();
$admin = getCurrentAdmin();

$success_message = '';
$error_message = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    
    if (empty($full_name) || empty($email)) {
        $error_message = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } else {
        // Check if email already exists (except current admin)
        $stmt = $conn->prepare("SELECT admin_id FROM admin_users WHERE email = ? AND admin_id != ?");
        $stmt->bind_param("si", $email, $admin['admin_id']);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $error_message = 'This email is already in use.';
        } else {
            $stmt = $conn->prepare("UPDATE admin_users SET full_name = ?, email = ? WHERE admin_id = ?");
            $stmt->bind_param("ssi", $full_name, $email, $admin['admin_id']);
            
            if ($stmt->execute()) {
                $success_message = 'Profile updated successfully!';
                logAdminActivity('update', 'admin', $admin['admin_id'], 'Updated profile');
                
                // Refresh admin data
                $_SESSION['admin_name'] = $full_name;
                $admin['full_name'] = $full_name;
                $admin['email'] = $email;
            } else {
                $error_message = 'Failed to update profile.';
            }
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = 'Please fill in all password fields.';
    } elseif ($new_password !== $confirm_password) {
        $error_message = 'New passwords do not match.';
    } elseif (strlen($new_password) < 6) {
        $error_message = 'New password must be at least 6 characters long.';
    } else {
        // Verify current password
        if (password_verify($current_password, $admin['password'])) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("UPDATE admin_users SET password = ? WHERE admin_id = ?");
            $stmt->bind_param("si", $hashed_password, $admin['admin_id']);
            
            if ($stmt->execute()) {
                $success_message = 'Password changed successfully!';
                logAdminActivity('update', 'admin', $admin['admin_id'], 'Changed password');
            } else {
                $error_message = 'Failed to change password.';
            }
        } else {
            $error_message = 'Current password is incorrect.';
        }
    }
}

// Get system statistics
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_vocabulary = $conn->query("SELECT COUNT(*) as count FROM vocabulary")->fetch_assoc()['count'];
$total_themes = $conn->query("SELECT COUNT(*) as count FROM themes")->fetch_assoc()['count'];
$total_quizzes = $conn->query("SELECT COUNT(*) as count FROM quiz_results")->fetch_assoc()['count'];
$total_admins = $conn->query("SELECT COUNT(*) as count FROM admin_users WHERE is_active = 1")->fetch_assoc()['count'];

// Get database size
$db_name = 'mandarin_learning';
$db_size_query = $conn->query("
    SELECT 
        ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
    FROM information_schema.TABLES
    WHERE table_schema = '$db_name'
");
$db_size = $db_size_query->fetch_assoc()['size_mb'] ?? 0;

// Get all admins (for super admin only)
$all_admins = null;
if ($admin['role'] === 'super_admin') {
    $all_admins = $conn->query("SELECT * FROM admin_users ORDER BY created_at DESC");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings - Admin Panel</title>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .settings-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .settings-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .settings-card.full-width {
            grid-column: 1 / -1;
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .card-icon {
            font-size: 32px;
        }

        .card-title {
            font-size: 20px;
            font-weight: 600;
            color: #333;
        }

        .card-subtitle {
            font-size: 13px;
            color: #999;
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
            box-shadow: 0 0 0 3px rgba(139, 0, 0, 0.1);
        }

        .form-input[readonly] {
            background: #f8f9fa;
            cursor: not-allowed;
        }

        .form-help {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }

        .btn-save {
            padding: 12px 30px;
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(76, 175, 80, 0.3);
        }

        .btn-save:hover {
            background: linear-gradient(135deg, #45a049 0%, #388E3C 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.4);
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-size: 14px;
            font-weight: 500;
            animation: slideInDown 0.5s ease;
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

        @keyframes slideInDown {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .info-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #8B0000;
        }

        .info-label {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 20px;
            font-weight: 700;
            color: #333;
        }

        .system-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            margin-bottom: 15px;
        }

        .system-label {
            font-size: 14px;
            color: #666;
            font-weight: 600;
        }

        .system-value {
            font-size: 16px;
            font-weight: 700;
            color: #8B0000;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .admin-table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
            color: #666;
            border-bottom: 2px solid #e0e0e0;
        }

        .admin-table td {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }

        .admin-table tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .role-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            background: #e3f2fd;
            color: #1565c0;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: white;
            margin: 0 auto 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .profile-name {
            text-align: center;
            font-size: 24px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }

        .profile-role {
            text-align: center;
            font-size: 14px;
            color: #999;
            text-transform: uppercase;
            margin-bottom: 20px;
        }

        .danger-zone {
            border: 2px solid #f44336;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }

        .danger-zone h4 {
            color: #f44336;
            font-size: 16px;
            margin-bottom: 10px;
        }

        .btn-danger {
            padding: 10px 20px;
            background: #f44336;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-danger:hover {
            background: #d32f2f;
            transform: translateY(-2px);
        }

        @media (max-width: 1200px) {
            .settings-grid {
                grid-template-columns: 1fr;
            }
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
            <a href="users.php"><span>👥</span> Users</a>
            <a href="vocabulary.php"><span>📚</span> Vocabulary</a>
            <a href="themes.php"><span>🎨</span> Themes</a>
            <a href="quizzes.php"><span>📝</span> Quizzes</a>
            <a href="activity.php"><span>📈</span> Activity</a>
            <a href="settings.php" class="active"><span>⚙️</span> Settings</a>
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
            <h1>Admin Settings</h1>
            <div class="header-actions">
                <a href="dashboard.php" class="btn-view-site">← Dashboard</a>
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

            <!-- Settings Grid -->
            <div class="settings-grid">
                <!-- Profile Settings -->
                <div class="settings-card">
                    <div class="card-header">
                        <div class="card-icon">👤</div>
                        <div>
                            <div class="card-title">Profile Settings</div>
                            <div class="card-subtitle">Update your personal information</div>
                        </div>
                    </div>

                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($admin['full_name'], 0, 1)); ?>
                    </div>
                    <div class="profile-name"><?php echo htmlspecialchars($admin['full_name']); ?></div>
                    <div class="profile-role"><?php echo htmlspecialchars($admin['role']); ?></div>

                    <form method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="form-group">
                            <label class="form-label">Username</label>
                            <input 
                                type="text" 
                                class="form-input" 
                                value="<?php echo htmlspecialchars($admin['username']); ?>"
                                readonly
                            >
                            <div class="form-help">Username cannot be changed</div>
                        </div>

                        <div class="form-group">
                            <label class="form-label required">Full Name</label>
                            <input 
                                type="text" 
                                name="full_name" 
                                class="form-input" 
                                value="<?php echo htmlspecialchars($admin['full_name']); ?>"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label class="form-label required">Email</label>
                            <input 
                                type="email" 
                                name="email" 
                                class="form-input" 
                                value="<?php echo htmlspecialchars($admin['email']); ?>"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label class="form-label">Last Login</label>
                            <input 
                                type="text" 
                                class="form-input" 
                                value="<?php echo $admin['last_login'] ? date('F d, Y g:i A', strtotime($admin['last_login'])) : 'Never'; ?>"
                                readonly
                            >
                        </div>

                        <button type="submit" class="btn-save">💾 Update Profile</button>
                    </form>
                </div>

                <!-- Password Settings -->
                <div class="settings-card">
                    <div class="card-header">
                        <div class="card-icon">🔐</div>
                        <div>
                            <div class="card-title">Change Password</div>
                            <div class="card-subtitle">Update your account password</div>
                        </div>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="form-group">
                            <label class="form-label required">Current Password</label>
                            <input 
                                type="password" 
                                name="current_password" 
                                class="form-input" 
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label class="form-label required">New Password</label>
                            <input 
                                type="password" 
                                name="new_password" 
                                class="form-input" 
                                minlength="6"
                                required
                            >
                            <div class="form-help">Minimum 6 characters</div>
                        </div>

                        <div class="form-group">
                            <label class="form-label required">Confirm New Password</label>
                            <input 
                                type="password" 
                                name="confirm_password" 
                                class="form-input" 
                                minlength="6"
                                required
                            >
                        </div>

                        <button type="submit" class="btn-save">🔒 Change Password</button>
                    </form>

                    <div class="danger-zone">
                        <h4>⚠️ Danger Zone</h4>
                        <p style="font-size: 13px; color: #666; margin-bottom: 10px;">
                            Changing your password will log you out from all devices.
                        </p>
                    </div>
                </div>

                <!-- System Information -->
                <div class="settings-card full-width">
                    <div class="card-header">
                        <div class="card-icon">💻</div>
                        <div>
                            <div class="card-title">System Information</div>
                            <div class="card-subtitle">Platform statistics and health</div>
                        </div>
                    </div>

                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Total Users</div>
                            <div class="info-value"><?php echo $total_users; ?></div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Vocabulary Words</div>
                            <div class="info-value"><?php echo $total_vocabulary; ?></div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Themes</div>
                            <div class="info-value"><?php echo $total_themes; ?></div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Quiz Attempts</div>
                            <div class="info-value"><?php echo $total_quizzes; ?></div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Active Admins</div>
                            <div class="info-value"><?php echo $total_admins; ?></div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Database Size</div>
                            <div class="info-value"><?php echo $db_size; ?> MB</div>
                        </div>
                    </div>

                    <div style="margin-top: 30px;">
                        <h4 style="margin-bottom: 15px; color: #333;">Server Details</h4>
                        
                        <div class="system-info">
                            <span class="system-label">PHP Version</span>
                            <span class="system-value"><?php echo phpversion(); ?></span>
                        </div>

                        <div class="system-info">
                            <span class="system-label">Server Software</span>
                            <span class="system-value"><?php echo $_SERVER['SERVER_SOFTWARE']; ?></span>
                        </div>

                        <div class="system-info">
                            <span class="system-label">MySQL Version</span>
                            <span class="system-value"><?php echo $conn->server_info; ?></span>
                        </div>

                        <div class="system-info">
                            <span class="system-label">Platform</span>
                            <span class="system-value">TalkSphere Mandarin Learning v1.0</span>
                        </div>
                    </div>
                </div>

                <!-- Admin Users (Super Admin Only) -->
                <?php if ($admin['role'] === 'super_admin' && $all_admins): ?>
                <div class="settings-card full-width">
                    <div class="card-header">
                        <div class="card-icon">👨‍💼</div>
                        <div>
                            <div class="card-title">Admin Users</div>
                            <div class="card-subtitle">Manage administrator accounts</div>
                        </div>
                    </div>

                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Last Login</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($adm = $all_admins->fetch_assoc()): ?>
                            <tr>
                                <td><strong>#<?php echo $adm['admin_id']; ?></strong></td>
                                <td><?php echo htmlspecialchars($adm['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($adm['username']); ?></td>
                                <td><?php echo htmlspecialchars($adm['email']); ?></td>
                                <td>
                                    <span class="role-badge">
                                        <?php echo ucfirst(str_replace('_', ' ', $adm['role'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $adm['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $adm['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td><?php echo $adm['last_login'] ? date('M d, Y', strtotime($adm['last_login'])) : 'Never'; ?></td>
                                <td><?php echo date('M d, Y', strtotime($adm['created_at'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Auto-hide alerts
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.3s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            });
        }, 3000);

        // Password confirmation validation
        const newPassword = document.querySelector('input[name="new_password"]');
        const confirmPassword = document.querySelector('input[name="confirm_password"]');

        if (newPassword && confirmPassword) {
            confirmPassword.addEventListener('input', function() {
                if (this.value !== newPassword.value) {
                    this.setCustomValidity('Passwords do not match');
                } else {
                    this.setCustomValidity('');
                }
            });
        }

        // Profile avatar animation
        const avatar = document.querySelector('.profile-avatar');
        if (avatar) {
            avatar.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.1) rotate(5deg)';
            });
            
            avatar.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1) rotate(0deg)';
            });
        }
    </script>
</body>
</html>