<?php
session_start();
require_once '../config/database.php';
require_once 'config/admin_auth.php';

checkAdminLogin();
$admin = getCurrentAdmin();

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $theme_name = trim($_POST['theme_name']);
    $description = trim($_POST['description']);
    $icon = trim($_POST['icon']);
    
    // Validate inputs
    if (empty($theme_name)) {
        $error_message = 'Theme name is required.';
    } else {
        // Check if theme already exists
        $stmt = $conn->prepare("SELECT theme_id FROM themes WHERE theme_name = ?");
        $stmt->bind_param("s", $theme_name);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $error_message = 'A theme with this name already exists.';
        } else {
            // Insert new theme
            $stmt = $conn->prepare("INSERT INTO themes (theme_name, description) VALUES (?, ?)");
            $stmt->bind_param("ss", $theme_name, $description);
            
            if ($stmt->execute()) {
                $theme_id = $conn->insert_id;
                $success_message = 'Theme created successfully!';
                logAdminActivity('create', 'theme', $theme_id, 'Created new theme: ' . $theme_name);
                
                // Clear form
                $_POST = [];
            } else {
                $error_message = 'Failed to create theme.';
            }
        }
    }
}

// Theme suggestions
$theme_suggestions = [
    ['name' => 'Colors', 'icon' => '🎨', 'desc' => 'Learn basic colors in Mandarin'],
    ['name' => 'Numbers', 'icon' => '🔢', 'desc' => 'Master counting and numbers'],
    ['name' => 'Fruits', 'icon' => '🍎', 'desc' => 'Explore different types of fruits'],
    ['name' => 'Animals', 'icon' => '🐼', 'desc' => 'Learn animal names'],
    ['name' => 'Family', 'icon' => '👨‍👩‍👧', 'desc' => 'Family members and relationships'],
    ['name' => 'Food & Drinks', 'icon' => '🍜', 'desc' => 'Common food and beverage terms'],
    ['name' => 'Body Parts', 'icon' => '🖐️', 'desc' => 'Parts of the human body'],
    ['name' => 'Weather', 'icon' => '🌤️', 'desc' => 'Weather conditions and seasons'],
    ['name' => 'Transportation', 'icon' => '🚗', 'desc' => 'Vehicles and transport'],
    ['name' => 'Time', 'icon' => '⏰', 'desc' => 'Days, months, and time expressions']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Theme - Admin Panel</title>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .add-form {
            background: white;
            border-radius: 15px;
            padding: 40px;
            max-width: 900px;
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

        .suggestions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .suggestion-card {
            padding: 15px;
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }

        .suggestion-card:hover {
            border-color: #8B0000;
            background: #fff;
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .suggestion-icon {
            font-size: 36px;
            margin-bottom: 8px;
        }

        .suggestion-name {
            font-size: 14px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .suggestion-desc {
            font-size: 11px;
            color: #999;
        }

        .form-preview {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 30px;
            border-radius: 15px;
            text-align: center;
        }

        .preview-icon {
            font-size: 80px;
            margin-bottom: 15px;
        }

        .preview-name {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }

        .preview-desc {
            font-size: 16px;
            color: #666;
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
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
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

        .icon-picker {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(50px, 1fr));
            gap: 10px;
            margin-top: 10px;
            max-height: 200px;
            overflow-y: auto;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .icon-option {
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            cursor: pointer;
            border: 2px solid transparent;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .icon-option:hover {
            background: white;
            border-color: #8B0000;
            transform: scale(1.1);
        }

        .icon-option.selected {
            background: #8B0000;
            border-color: #8B0000;
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
            <h1>Add New Theme</h1>
            <div class="header-actions">
                <a href="themes.php" class="btn-view-site">← Back to Themes</a>
            </div>
        </div>

        <div class="content-wrapper">
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    ✅ <?php echo $success_message; ?> 
                    <a href="theme-add.php" style="color: #155724; text-decoration: underline;">Add another theme</a> or
                    <a href="vocabulary-add.php" style="color: #155724; text-decoration: underline;">Add vocabulary</a>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    ❌ <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="add-form" id="themeForm">
                <!-- Theme Information -->
                <div class="form-section">
                    <div class="section-title">📝 Theme Information</div>
                    
                    <div class="form-group">
                        <label class="form-label required">Theme Name</label>
                        <input 
                            type="text" 
                            name="theme_name" 
                            id="theme_name"
                            class="form-input" 
                            value="<?php echo isset($_POST['theme_name']) ? htmlspecialchars($_POST['theme_name']) : ''; ?>"
                            placeholder="e.g., Colors, Numbers, Animals"
                            required
                            oninput="updatePreview()"
                        >
                        <div class="form-help">Enter a descriptive name for this theme</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea 
                            name="description" 
                            id="description"
                            class="form-textarea" 
                            placeholder="e.g., Learn basic colors in Mandarin Chinese"
                            oninput="updatePreview()"
                        ><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                        <div class="form-help">Provide a brief description of what this theme covers</div>
                    </div>

                    <input type="hidden" name="icon" id="selected_icon" value="📁">
                </div>

                <!-- Theme Suggestions -->
                <div class="form-section">
                    <div class="section-title">💡 Quick Start Templates</div>
                    <p style="color: #666; font-size: 14px; margin-bottom: 15px;">
                        Click on a suggestion to quickly fill in the form
                    </p>
                    
                    <div class="suggestions-grid">
                        <?php foreach ($theme_suggestions as $suggestion): ?>
                        <div class="suggestion-card" onclick="fillForm('<?php echo $suggestion['name']; ?>', '<?php echo $suggestion['desc']; ?>', '<?php echo $suggestion['icon']; ?>')">
                            <div class="suggestion-icon"><?php echo $suggestion['icon']; ?></div>
                            <div class="suggestion-name"><?php echo $suggestion['name']; ?></div>
                            <div class="suggestion-desc"><?php echo $suggestion['desc']; ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Icon Picker -->
                <div class="form-section">
                    <div class="section-title">🎨 Choose an Icon (Optional)</div>
                    <div class="icon-picker" id="iconPicker">
                        <?php
                        $icons = ['🎨', '🔢', '🍎', '🐼', '👨‍👩‍👧', '🍜', '🖐️', '🌤️', '🚗', '⏰', 
                                  '🏠', '📱', '💼', '🎓', '⚽', '🎵', '🌍', '💰', '🏥', '✈️',
                                  '🎉', '📚', '🌺', '⭐', '🔥', '💎', '🎯', '🎪', '🎭', '🎬'];
                        foreach ($icons as $icon):
                        ?>
                            <div class="icon-option" onclick="selectIcon('<?php echo $icon; ?>')"><?php echo $icon; ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Preview -->
                <div class="form-section">
                    <div class="section-title">👁️ Preview</div>
                    <div class="form-preview">
                        <div class="preview-icon" id="previewIcon">📁</div>
                        <div class="preview-name" id="previewName">Theme Name</div>
                        <div class="preview-desc" id="previewDesc">Theme description will appear here</div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn-save">💾 Create Theme</button>
                    <a href="themes.php" class="btn-cancel">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Update preview in real-time
        function updatePreview() {
            const name = document.getElementById('theme_name').value || 'Theme Name';
            const desc = document.getElementById('description').value || 'Theme description will appear here';
            const icon = document.getElementById('selected_icon').value || '📁';
            
            document.getElementById('previewName').textContent = name;
            document.getElementById('previewDesc').textContent = desc;
            document.getElementById('previewIcon').textContent = icon;
        }

        // Fill form with suggestion
        function fillForm(name, desc, icon) {
            document.getElementById('theme_name').value = name;
            document.getElementById('description').value = desc;
            document.getElementById('selected_icon').value = icon;
            selectIcon(icon);
            updatePreview();
            
            // Scroll to form
            document.getElementById('theme_name').scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            // Add highlight effect
            document.getElementById('theme_name').style.background = '#fff3cd';
            setTimeout(() => {
                document.getElementById('theme_name').style.background = '';
            }, 1000);
        }

        // Select icon
        function selectIcon(icon) {
            document.getElementById('selected_icon').value = icon;
            document.getElementById('previewIcon').textContent = icon;
            
            // Update UI
            document.querySelectorAll('.icon-option').forEach(opt => {
                opt.classList.remove('selected');
                if (opt.textContent === icon) {
                    opt.classList.add('selected');
                }
            });
        }

        // Initialize preview
        updatePreview();

        // Auto-hide alerts
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                if (!alert.querySelector('a')) {
                    alert.style.transition = 'opacity 0.3s ease';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 300);
                }
            });
        }, 5000);

        // Add animation to suggestion cards
        document.querySelectorAll('.suggestion-card').forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            setTimeout(() => {
                card.style.transition = 'all 0.5s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 50);
        });
    </script>
</body>
</html>