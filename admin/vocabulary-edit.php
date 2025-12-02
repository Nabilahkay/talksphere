<?php
session_start();
require_once '../config/database.php';
require_once 'config/admin_auth.php';

checkAdminLogin();
$admin = getCurrentAdmin();

$vocab_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$success_message = '';
$error_message = '';

if ($vocab_id == 0) {
    header("Location: vocabulary.php");
    exit();
}

// Get vocabulary details
$stmt = $conn->prepare("SELECT * FROM vocabulary WHERE vocab_id = ?");
$stmt->bind_param("i", $vocab_id);
$stmt->execute();
$vocab = $stmt->get_result()->fetch_assoc();

if (!$vocab) {
    header("Location: vocabulary.php");
    exit();
}

// Get all themes
$themes = $conn->query("SELECT * FROM themes ORDER BY theme_name");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $chinese_character = trim($_POST['chinese_character']);
    $pinyin = trim($_POST['pinyin']);
    $english_meaning = trim($_POST['english_meaning']);
    $theme_id = intval($_POST['theme_id']);
    $difficulty_level = $_POST['difficulty_level'];
    
    // Validate inputs
    if (empty($chinese_character) || empty($pinyin) || empty($english_meaning)) {
        $error_message = 'Please fill in all required fields.';
    } elseif ($theme_id == 0) {
        $error_message = 'Please select a theme.';
    } else {
        // Check if word already exists (except current word)
        $stmt = $conn->prepare("SELECT vocab_id FROM vocabulary WHERE chinese_character = ? AND theme_id = ? AND vocab_id != ?");
        $stmt->bind_param("sii", $chinese_character, $theme_id, $vocab_id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $error_message = 'This word already exists in the selected theme.';
        } else {
            // Update vocabulary
            $stmt = $conn->prepare("UPDATE vocabulary SET chinese_character = ?, pinyin = ?, english_meaning = ?, theme_id = ?, difficulty_level = ? WHERE vocab_id = ?");
            $stmt->bind_param("sssssi", $chinese_character, $pinyin, $english_meaning, $theme_id, $difficulty_level, $vocab_id);
            
            if ($stmt->execute()) {
                $success_message = 'Vocabulary updated successfully!';
                logAdminActivity('update', 'vocabulary', $vocab_id, 'Updated vocabulary: ' . $chinese_character);
                
                // Refresh vocab data
                $stmt = $conn->prepare("SELECT * FROM vocabulary WHERE vocab_id = ?");
                $stmt->bind_param("i", $vocab_id);
                $stmt->execute();
                $vocab = $stmt->get_result()->fetch_assoc();
            } else {
                $error_message = 'Failed to update vocabulary.';
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
    <title>Edit Vocabulary - <?php echo htmlspecialchars($vocab['chinese_character']); ?></title>
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
        .form-select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .form-input:focus,
        .form-select:focus {
            outline: none;
            border-color: #8B0000;
        }

        .form-help {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }

        .form-preview {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
        }

        .preview-character {
            font-size: 72px;
            color: #8B0000;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .preview-pinyin {
            font-size: 24px;
            color: #666;
            margin-bottom: 10px;
            font-style: italic;
        }

        .preview-meaning {
            font-size: 20px;
            color: #333;
            font-weight: 600;
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

        .radio-group {
            display: flex;
            gap: 20px;
        }

        .radio-option {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .radio-option input[type="radio"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .radio-option label {
            cursor: pointer;
            font-size: 14px;
            color: #333;
        }

        .vocab-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e0e0e0;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #666;
            font-size: 13px;
        }

        .info-value {
            color: #333;
            font-weight: 600;
            font-size: 13px;
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
            <a href="vocabulary.php" class="active"><span>📚</span> Vocabulary</a>
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
            <h1>Edit Vocabulary</h1>
            <div class="header-actions">
                <a href="vocabulary.php" class="btn-view-site">← Back to Vocabulary</a>
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
                <!-- Word Information -->
                <div class="form-section">
                    <div class="section-title">📝 Word Information</div>
                    
                    <!-- Vocab Info -->
                    <div class="vocab-info">
                        <div class="info-row">
                            <span class="info-label">Vocabulary ID:</span>
                            <span class="info-value">#<?php echo $vocab['vocab_id']; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Created:</span>
                            <span class="info-value"><?php echo isset($vocab['created_at']) ? date('M d, Y', strtotime($vocab['created_at'])) : 'N/A'; ?></span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required">Chinese Character</label>
                        <input 
                            type="text" 
                            name="chinese_character" 
                            id="chinese_character"
                            class="form-input" 
                            value="<?php echo htmlspecialchars($vocab['chinese_character']); ?>"
                            required
                            oninput="updatePreview()"
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label required">Pinyin</label>
                        <input 
                            type="text" 
                            name="pinyin" 
                            id="pinyin"
                            class="form-input" 
                            value="<?php echo htmlspecialchars($vocab['pinyin']); ?>"
                            required
                            oninput="updatePreview()"
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label required">English Meaning</label>
                        <input 
                            type="text" 
                            name="english_meaning" 
                            id="english_meaning"
                            class="form-input" 
                            value="<?php echo htmlspecialchars($vocab['english_meaning']); ?>"
                            required
                            oninput="updatePreview()"
                        >
                    </div>
                </div>

                <!-- Classification -->
                <div class="form-section">
                    <div class="section-title">🎨 Classification</div>
                    
                    <div class="form-group">
                        <label class="form-label required">Theme</label>
                        <select name="theme_id" class="form-select" required>
                            <option value="0">Select a theme</option>
                            <?php while($theme = $themes->fetch_assoc()): ?>
                                <option value="<?php echo $theme['theme_id']; ?>"
                                        <?php echo $vocab['theme_id'] == $theme['theme_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($theme['theme_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label required">Difficulty Level</label>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio" name="difficulty_level" value="beginner" id="beginner" 
                                       <?php echo ($vocab['difficulty_level'] == 'beginner') ? 'checked' : ''; ?>>
                                <label for="beginner">Beginner</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" name="difficulty_level" value="intermediate" id="intermediate"
                                       <?php echo ($vocab['difficulty_level'] == 'intermediate') ? 'checked' : ''; ?>>
                                <label for="intermediate">Intermediate</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" name="difficulty_level" value="advanced" id="advanced"
                                       <?php echo ($vocab['difficulty_level'] == 'advanced') ? 'checked' : ''; ?>>
                                <label for="advanced">Advanced</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Preview -->
                <div class="form-section">
                    <div class="section-title">👁️ Preview</div>
                    <div class="form-preview">
                        <div class="preview-character" id="previewCharacter"><?php echo htmlspecialchars($vocab['chinese_character']); ?></div>
                        <div class="preview-pinyin" id="previewPinyin"><?php echo htmlspecialchars($vocab['pinyin']); ?></div>
                        <div class="preview-meaning" id="previewMeaning"><?php echo htmlspecialchars($vocab['english_meaning']); ?></div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn-save">💾 Save Changes</button>
                    <a href="vocabulary.php" class="btn-cancel">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function updatePreview() {
            const character = document.getElementById('chinese_character').value;
            const pinyin = document.getElementById('pinyin').value;
            const meaning = document.getElementById('english_meaning').value;
            
            document.getElementById('previewCharacter').textContent = character;
            document.getElementById('previewPinyin').textContent = pinyin;
            document.getElementById('previewMeaning').textContent = meaning;
        }

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