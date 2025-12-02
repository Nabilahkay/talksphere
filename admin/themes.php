<?php
session_start();
require_once '../config/database.php';
require_once 'config/admin_auth.php';

checkAdminLogin();
$admin = getCurrentAdmin();

// Handle theme actions
$success_message = '';
$error_message = '';

// Delete theme
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $theme_id = intval($_GET['id']);
    
    // Check if theme has vocabulary
    $check_vocab = $conn->query("SELECT COUNT(*) as count FROM vocabulary WHERE theme_id = $theme_id")->fetch_assoc();
    
    if ($check_vocab['count'] > 0) {
        $error_message = "Cannot delete theme with {$check_vocab['count']} vocabulary word(s). Please delete or reassign the vocabulary first.";
    } else {
        $stmt = $conn->prepare("DELETE FROM themes WHERE theme_id = ?");
        $stmt->bind_param("i", $theme_id);
        
        if ($stmt->execute()) {
            $success_message = 'Theme deleted successfully!';
            logAdminActivity('delete', 'theme', $theme_id, 'Deleted theme ID: ' . $theme_id);
        } else {
            $error_message = 'Failed to delete theme.';
        }
    }
}

// Get all themes with statistics
$themes_query = "SELECT t.*, 
                 COUNT(v.vocab_id) as word_count,
                 (SELECT COUNT(*) FROM quiz_results qr WHERE qr.theme_id = t.theme_id) as quiz_count,
                 (SELECT COUNT(DISTINCT user_id) FROM user_progress up 
                  JOIN vocabulary v2 ON up.vocab_id = v2.vocab_id 
                  WHERE v2.theme_id = t.theme_id) as learner_count
                 FROM themes t
                 LEFT JOIN vocabulary v ON t.theme_id = v.theme_id
                 GROUP BY t.theme_id
                 ORDER BY t.theme_name ASC";

$themes = $conn->query($themes_query);

// Get overall statistics
$total_themes = $conn->query("SELECT COUNT(*) as count FROM themes")->fetch_assoc()['count'];
$total_words = $conn->query("SELECT COUNT(*) as count FROM vocabulary")->fetch_assoc()['count'];
$total_learners = $conn->query("SELECT COUNT(DISTINCT user_id) as count FROM user_progress")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Theme Management - Admin Panel</title>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .themes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .theme-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border-left: 5px solid #e0e0e0;
            position: relative;
            overflow: hidden;
        }

        .theme-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--theme-color, #8B0000) 0%, var(--theme-color-dark, #A52A2A) 100%);
        }

        .theme-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .theme-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .theme-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }

        .theme-name {
            font-size: 22px;
            font-weight: 700;
            color: #333;
            margin-bottom: 8px;
        }

        .theme-description {
            font-size: 14px;
            color: #666;
            line-height: 1.5;
            margin-bottom: 20px;
            min-height: 40px;
        }

        .theme-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 20px;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
        }

        .stat-box {
            text-align: center;
        }

        .stat-number {
            font-size: 24px;
            font-weight: 700;
            color: #8B0000;
            display: block;
        }

        .stat-text {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
            margin-top: 5px;
        }

        .theme-actions {
            display: flex;
            gap: 10px;
            padding-top: 15px;
            border-top: 2px solid #f0f0f0;
        }

        .theme-actions .btn-action {
            flex: 1;
            justify-content: center;
        }

        .theme-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            padding: 6px 12px;
            background: rgba(139, 0, 0, 0.1);
            color: #8B0000;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .empty-theme {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 15px;
            border: 2px dashed #e0e0e0;
        }

        .empty-theme-icon {
            font-size: 80px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-theme h3 {
            font-size: 20px;
            color: #666;
            margin-bottom: 10px;
        }

        .empty-theme p {
            color: #999;
            margin-bottom: 25px;
        }

        /* Theme Color Variations */
        .theme-card.colors {
            --theme-color: #f44336;
            --theme-color-dark: #d32f2f;
        }

        .theme-card.numbers {
            --theme-color: #2196F3;
            --theme-color-dark: #1976D2;
        }

        .theme-card.fruits {
            --theme-color: #4CAF50;
            --theme-color-dark: #388E3C;
        }

        .theme-card.animals {
            --theme-color: #FF9800;
            --theme-color-dark: #F57C00;
        }

        .theme-card.family {
            --theme-color: #9C27B0;
            --theme-color-dark: #7B1FA2;
        }

        .theme-card.food {
            --theme-color: #FF5722;
            --theme-color-dark: #E64A19;
        }

        .theme-card.body {
            --theme-color: #00BCD4;
            --theme-color-dark: #0097A7;
        }

        .theme-card.weather {
            --theme-color: #607D8B;
            --theme-color-dark: #455A64;
        }

        .progress-bar {
            height: 8px;
            background: #f0f0f0;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 15px;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--theme-color, #8B0000) 0%, var(--theme-color-dark, #A52A2A) 100%);
            border-radius: 10px;
            transition: width 0.5s ease;
        }

        .progress-label {
            font-size: 11px;
            color: #999;
            margin-top: 5px;
        }

        @media (max-width: 768px) {
            .themes-grid {
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
            <h1>Theme Management</h1>
            <div class="header-actions">
                <a href="theme-add.php" class="btn-add">➕ Add New Theme</a>
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

            <!-- Stats -->
            <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 30px;">
                <div class="stat-card blue">
                    <div class="stat-icon">🎨</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $total_themes; ?></div>
                        <div class="stat-label">Total Themes</div>
                    </div>
                </div>

                <div class="stat-card green">
                    <div class="stat-icon">📚</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $total_words; ?></div>
                        <div class="stat-label">Total Words</div>
                    </div>
                </div>

                <div class="stat-card purple">
                    <div class="stat-icon">👥</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $total_learners; ?></div>
                        <div class="stat-label">Active Learners</div>
                    </div>
                </div>
            </div>

            <!-- Themes Grid -->
            <?php if ($themes->num_rows > 0): ?>
                <div class="themes-grid">
                    <?php while($theme = $themes->fetch_assoc()): 
                        // Determine class based on theme name
                        $theme_class = strtolower(str_replace(' ', '', $theme['theme_name']));
                        
                        // Get icon based on theme
                        $icons = [
                            'colors' => '🎨',
                            'numbers' => '🔢',
                            'fruits' => '🍎',
                            'animals' => '🐼',
                            'family' => '👨‍👩‍👧',
                            'food' => '🍜',
                            'body' => '🖐️',
                            'weather' => '🌤️',
                            'transportation' => '🚗',
                            'time' => '⏰'
                        ];
                        
                        $icon = $icons[$theme_class] ?? '📁';
                        
                        // Calculate completion percentage
                        $total_vocab = $conn->query("SELECT COUNT(*) as count FROM vocabulary")->fetch_assoc()['count'];
                        $completion = $total_vocab > 0 ? round(($theme['word_count'] / $total_vocab) * 100) : 0;
                    ?>
                    <div class="theme-card <?php echo $theme_class; ?>">
                        <?php if ($theme['word_count'] == 0): ?>
                            <span class="theme-badge">Empty</span>
                        <?php endif; ?>
                        
                        <div class="theme-header">
                            <div>
                                <div class="theme-icon"><?php echo $icon; ?></div>
                                <div class="theme-name"><?php echo htmlspecialchars($theme['theme_name']); ?></div>
                            </div>
                        </div>

                        <div class="theme-description">
                            <?php echo htmlspecialchars($theme['description'] ?? 'No description available'); ?>
                        </div>

                        <div class="theme-stats">
                            <div class="stat-box">
                                <span class="stat-number"><?php echo $theme['word_count']; ?></span>
                                <span class="stat-text">Words</span>
                            </div>
                            <div class="stat-box">
                                <span class="stat-number"><?php echo $theme['quiz_count']; ?></span>
                                <span class="stat-text">Quizzes</span>
                            </div>
                            <div class="stat-box">
                                <span class="stat-number"><?php echo $theme['learner_count']; ?></span>
                                <span class="stat-text">Learners</span>
                            </div>
                        </div>

                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $completion; ?>%"></div>
                        </div>
                        <div class="progress-label"><?php echo $completion; ?>% of total vocabulary</div>

                        <div class="theme-actions">
                            <a href="vocabulary.php?theme=<?php echo $theme['theme_id']; ?>" 
                               class="btn-action btn-view"
                               title="View Vocabulary">
                                View
                            </a>
                            <a href="theme-edit.php?id=<?php echo $theme['theme_id']; ?>" 
                               class="btn-action btn-edit"
                               title="Edit Theme">
                                Edit
                            </a>
                            <a href="themes.php?action=delete&id=<?php echo $theme['theme_id']; ?>" 
                               class="btn-action btn-delete"
                               title="Delete Theme"
                               onclick="return confirm('Are you sure you want to delete this theme? This action cannot be undone!');">
                                Delete
                            </a>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-theme">
                    <div class="empty-theme-icon">🎨</div>
                    <h3>No Themes Found</h3>
                    <p>Start organizing your vocabulary by creating themes!</p>
                    <a href="theme-add.php" class="btn-add">➕ Create Your First Theme</a>
                </div>
            <?php endif; ?>
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

        // Animate progress bars on load
        document.addEventListener('DOMContentLoaded', function() {
            const progressBars = document.querySelectorAll('.progress-fill');
            progressBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0';
                setTimeout(() => {
                    bar.style.width = width;
                }, 100);
            });
        });

        // Add hover effect for theme cards
        document.querySelectorAll('.theme-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });
    </script>
</body>
</html>