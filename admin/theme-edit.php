<?php
session_start();
require_once '../config/database.php';
require_once 'config/admin_auth.php';

checkAdminLogin();
$admin = getCurrentAdmin();

$theme_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$success_message = '';
$error_message = '';

if ($theme_id == 0) {
    header("Location: themes.php");
    exit();
}

// Get theme details
$stmt = $conn->prepare("SELECT * FROM themes WHERE theme_id = ?");
$stmt->bind_param("i", $theme_id);
$stmt->execute();
$theme = $stmt->get_result()->fetch_assoc();

if (!$theme) {
    header("Location: themes.php");
    exit();
}

// Get theme statistics
$word_count = $conn->query("SELECT COUNT(*) as count FROM vocabulary WHERE theme_id = $theme_id")->fetch_assoc()['count'];
$quiz_count = $conn->query("SELECT COUNT(*) as count FROM quiz_results WHERE theme_id = $theme_id")->fetch_assoc()['count'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $theme_name = trim($_POST['theme_name']);
    $description = trim($_POST['description']);
    
    // Validate inputs
    if (empty($theme_name)) {
        $error_message = 'Theme name is required.';
    } else {
        // Check if theme name already exists (except current theme)
        $stmt = $conn->prepare("SELECT theme_id FROM themes WHERE theme_name = ? AND theme_id != ?");
        $stmt->bind_param("si", $theme_name, $theme_id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $error_message = 'A theme with this name already exists.';
        } else {
            // Update theme
            $stmt = $conn->prepare("UPDATE themes SET theme_name = ?, description = ? WHERE theme_id = ?");
            $stmt->bind_param("ssi", $theme_name, $description, $theme_id);
            
            if ($stmt->execute()) {
                $success_message = 'Theme updated successfully!';
                logAdminActivity('update', 'theme', $theme_id, 'Updated theme: ' . $theme_name);
                
                // Refresh theme data
                $stmt = $conn->prepare("SELECT * FROM themes WHERE theme_id = ?");
                $stmt->bind_param("i", $theme_id);
                $stmt->execute();
                $theme = $stmt->get_result()->fetch_assoc();
            } else {
                $error_message = 'Failed to update theme.';
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
    <title>Edit Theme - <?php echo htmlspecialchars($theme['theme_name']); ?></title>
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

        .form-input,
        .form-textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .form-input:focus,
        .form-textarea:focus {
            outline: none;
            border-color: #8B0000;
            box-shadow: 0 0 0 3px rgba(139, 0, 0, 0.1);
        }

        .form-textarea {
            resize: vertical;
            min-height: 100px;
            font-family: inherit;
        }

        .form-help {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }

        .theme-stats-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .stat-item {
            text-align: center;
            padding: 15px;
            background: white;
            border-radius: 8px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #8B0000;
        }

        .stat-label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
            text-transform: uppercase;
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

        .quick-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .quick-action-btn {
            flex: 1;
            padding: 12px;
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            text-align: center;
            text-decoration: none;
            color: #333;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .quick-action-btn:hover {
            background: white;
            border-color: #8B0000;
            color: #8B0000;
            transform: translateY(-2px);
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
            <a href="themes.php" class="active"><span>🎨</span> Themes</a>
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
            <h1>Edit Theme</h1>
            <div class="header-actions">
                <a href="vocabulary.php?theme=<?php echo $theme_id; ?>" class="btn-view-site">📚 View Vocabulary</a>
                <a href="themes.php" class="btn-view-site">← Back to Themes</a>
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
                <!-- Theme Statistics -->
                <div class="form-section">
                    <div class="section-title">📊 Theme Statistics</div>
                    <div class="theme-stats-box">
                        <div class="stats-grid">
                            <div class="stat-item">
                                <div class="stat-value"><?php echo $word_count; ?></div>
                                <div class="stat-label">Vocabulary Words</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value"><?php echo $quiz_count; ?></div>
                                <div class="stat-label">Quiz Attempts</div>
                            </div>
                        </div>
                        
                        <div class="quick-actions">
                            <a href="vocabulary.php?theme=<?php echo $theme_id; ?>" class="quick-action-btn">
                                📚 Manage Vocabulary
                            </a>
                            <a href="vocabulary-add.php?theme=<?php echo $theme_id; ?>" class="quick-action-btn">
                                ➕ Add New Word
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Theme Information -->
                <div class="form-section">
                    <div class="section-title">📝 Theme Information</div>
                    
                    <div class="form-group">
                        <label class="form-label">Theme ID</label>
                        <input 
                            type="text" 
                            class="form-input" 
                            value="#<?php echo $theme['theme_id']; ?>"
                            readonly
                            style="background: #f8f9fa; cursor: not-allowed;"
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label required">Theme Name</label>
                        <input 
                            type="text" 
                            name="theme_name" 
                            class="form-input" 
                            value="<?php echo htmlspecialchars($theme['theme_name']); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea 
                            name="description" 
                            class="form-textarea"
                        ><?php echo htmlspecialchars($theme['description'] ?? ''); ?></textarea>
                        <div class="form-help">Provide a brief description of what this theme covers</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Created Date</label>
                        <input 
                            type="text" 
                            class="form-input" 
                            value="<?php echo isset($theme['created_at']) ? date('F d, Y g:i A', strtotime($theme['created_at'])) : 'N/A'; ?>"
                            readonly
                            style="background: #f8f9fa; cursor: not-allowed;"
                        >
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn-save">💾 Save Changes</button>
                    <a href="themes.php" class="btn-cancel">Cancel</a>
                </div>
            </form>
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
    </script>
</body>
</html>