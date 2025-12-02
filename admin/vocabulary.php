<?php
session_start();
require_once '../config/database.php';
require_once 'config/admin_auth.php';

checkAdminLogin();
$admin = getCurrentAdmin();

// Handle vocabulary actions
$success_message = '';
$error_message = '';

// Delete vocabulary
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $vocab_id = intval($_GET['id']);
    
    $stmt = $conn->prepare("DELETE FROM vocabulary WHERE vocab_id = ?");
    $stmt->bind_param("i", $vocab_id);
    
    if ($stmt->execute()) {
        $success_message = 'Vocabulary deleted successfully!';
        logAdminActivity('delete', 'vocabulary', $vocab_id, 'Deleted vocabulary ID: ' . $vocab_id);
    } else {
        $error_message = 'Failed to delete vocabulary.';
    }
}

// Pagination
$per_page = 20;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

// Filter by theme
$theme_filter = isset($_GET['theme']) ? intval($_GET['theme']) : 0;
$theme_condition = '';
if ($theme_filter > 0) {
    $theme_condition = "WHERE v.theme_id = $theme_filter";
}

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_condition = '';

if ($search) {
    if ($theme_condition) {
        $search_condition = " AND (v.chinese_character LIKE '%$search%' OR v.pinyin LIKE '%$search%' OR v.english_meaning LIKE '%$search%')";
    } else {
        $search_condition = "WHERE (v.chinese_character LIKE '%$search%' OR v.pinyin LIKE '%$search%' OR v.english_meaning LIKE '%$search%')";
    }
}

// Get total vocabulary count
$count_query = "SELECT COUNT(*) as total FROM vocabulary v $theme_condition $search_condition";
$total_vocab = $conn->query($count_query)->fetch_assoc()['total'];
$total_pages = ceil($total_vocab / $per_page);

// Get vocabulary with pagination
$vocab_query = "SELECT v.*, t.theme_name, t.theme_id
                FROM vocabulary v 
                LEFT JOIN themes t ON v.theme_id = t.theme_id
                $theme_condition $search_condition
                ORDER BY v.vocab_id DESC 
                LIMIT $per_page OFFSET $offset";

$vocabulary = $conn->query($vocab_query);

// Get all themes for filter
$themes = $conn->query("SELECT * FROM themes ORDER BY theme_name");

// Get statistics
$total_words = $conn->query("SELECT COUNT(*) as count FROM vocabulary")->fetch_assoc()['count'];
$themes_count = $conn->query("SELECT COUNT(*) as count FROM themes")->fetch_assoc()['count'];
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vocabulary Management - Admin Panel</title>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .vocab-filters {
            background: white;
            padding: 20px 40px;
            display: flex;
            gap: 15px;
            align-items: center;
            border-bottom: 1px solid #e0e0e0;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-label {
            font-size: 14px;
            font-weight: 600;
            color: #333;
        }

        .filter-select {
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            transition: border-color 0.3s ease;
        }

        .filter-select:focus {
            outline: none;
            border-color: #8B0000;
        }

        .search-input {
            flex: 1;
            padding: 10px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
            min-width: 300px;
        }

        .search-input:focus {
            outline: none;
            border-color: #8B0000;
        }

        .btn-add {
            padding: 12px 25px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-add:hover {
            background: #45a049;
            transform: translateY(-2px);
        }

        .vocab-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 20px;
            align-items: center;
            transition: all 0.3s ease;
            border: 2px solid #f0f0f0;
        }

        .vocab-card:hover {
            border-color: #8B0000;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .vocab-character {
            font-size: 48px;
            font-weight: 700;
            color: #8B0000;
            width: 80px;
            text-align: center;
        }

        .vocab-details {
            flex: 1;
        }

        .vocab-pinyin {
            font-size: 16px;
            color: #666;
            margin-bottom: 5px;
            font-style: italic;
        }

        .vocab-meaning {
            font-size: 18px;
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .vocab-meta {
            display: flex;
            gap: 15px;
            font-size: 13px;
            color: #999;
        }

        .meta-badge {
            padding: 4px 12px;
            background: #f0f0f0;
            border-radius: 20px;
            font-weight: 600;
        }

        .vocab-actions {
            display: flex;
            gap: 8px;
        }

        .vocab-grid {
            display: grid;
            gap: 15px;
            margin-top: 20px;
        }

        .audio-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #2196F3;
            color: white;
            border: none;
            font-size: 18px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .audio-btn:hover {
            background: #1976D2;
            transform: scale(1.1);
        }

        .difficulty-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .difficulty-beginner {
            background: #d4edda;
            color: #155724;
        }

        .difficulty-intermediate {
            background: #fff3cd;
            color: #856404;
        }

        .difficulty-advanced {
            background: #f8d7da;
            color: #721c24;
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
            <h1>Vocabulary Management</h1>
            <div class="header-actions">
                <a href="vocabulary-add.php" class="btn-add">➕ Add New Word</a>
                <a href="dashboard.php" class="btn-view-site">← Dashboard</a>
            </div>
        </div>

        <!-- Filters -->
        <div class="vocab-filters">
            <form method="GET" style="display: flex; gap: 15px; width: 100%; flex-wrap: wrap;">
                <div class="filter-group">
                    <label class="filter-label">Theme:</label>
                    <select name="theme" class="filter-select" onchange="this.form.submit()">
                        <option value="0">All Themes</option>
                        <?php while($theme = $themes->fetch_assoc()): ?>
                            <option value="<?php echo $theme['theme_id']; ?>" 
                                    <?php echo $theme_filter == $theme['theme_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($theme['theme_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <input 
                    type="text" 
                    name="search" 
                    class="search-input" 
                    placeholder="🔍 Search by character, pinyin, or meaning..."
                    value="<?php echo htmlspecialchars($search); ?>"
                >
                
                <button type="submit" class="btn-search">Search</button>
                
                <?php if ($search || $theme_filter): ?>
                    <a href="vocabulary.php" class="btn-clear">Clear Filters</a>
                <?php endif; ?>
            </form>
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
                    <div class="stat-icon">📚</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $total_words; ?></div>
                        <div class="stat-label">Total Words</div>
                    </div>
                </div>

                <div class="stat-card green">
                    <div class="stat-icon">🎨</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $themes_count; ?></div>
                        <div class="stat-label">Themes</div>
                    </div>
                </div>

                <div class="stat-card purple">
                    <div class="stat-icon">👥</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $total_users; ?></div>
                        <div class="stat-label">Active Learners</div>
                    </div>
                </div>
            </div>

            <!-- Vocabulary List -->
            <?php if ($vocabulary->num_rows > 0): ?>
                <div class="vocab-grid">
                    <?php while($word = $vocabulary->fetch_assoc()): ?>
                    <div class="vocab-card">
                        <div class="vocab-character">
                            <?php echo htmlspecialchars($word['chinese_character']); ?>
                        </div>
                        
                        <div class="vocab-details">
                            <div class="vocab-pinyin">
                                <?php echo htmlspecialchars($word['pinyin']); ?>
                            </div>
                            <div class="vocab-meaning">
                                <?php echo htmlspecialchars($word['english_meaning']); ?>
                            </div>
                            <div class="vocab-meta">
                                <span class="meta-badge">
                                    📁 <?php echo htmlspecialchars($word['theme_name'] ?? 'No Theme'); ?>
                                </span>
                                <?php if (isset($word['difficulty_level'])): ?>
                                    <span class="difficulty-badge difficulty-<?php echo $word['difficulty_level']; ?>">
                                        <?php echo ucfirst($word['difficulty_level']); ?>
                                    </span>
                                <?php endif; ?>
                                <span style="color: #999;">ID: #<?php echo $word['vocab_id']; ?></span>
                            </div>
                        </div>

                        <div class="vocab-actions">
                            <button class="audio-btn" onclick="speak('<?php echo htmlspecialchars($word['chinese_character']); ?>')" title="Listen to pronunciation">
                                🔊
                            </button>
                            <a href="vocabulary-edit.php?id=<?php echo $word['vocab_id']; ?>" 
                               class="btn-action btn-edit">
                                Edit 
                            </a>
                            <a href="vocabulary.php?action=delete&id=<?php echo $word['vocab_id']; ?>" 
                               class="btn-action btn-delete"
                               onclick="return confirm('Are you sure you want to delete this word?');">
                                Delete 
                            </a>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?><?php echo $theme_filter ? '&theme=' . $theme_filter : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="page-link">← Previous</a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                            <a href="?page=<?php echo $i; ?><?php echo $theme_filter ? '&theme=' . $theme_filter : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                               class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                            <span class="page-link disabled">...</span>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?><?php echo $theme_filter ? '&theme=' . $theme_filter : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="page-link">Next →</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">📚</div>
                    <h3>No Vocabulary Found</h3>
                    <?php if ($search || $theme_filter): ?>
                        <p>No words match your filters.</p>
                        <a href="vocabulary.php" class="btn-clear" style="margin-top: 20px;">Clear Filters</a>
                    <?php else: ?>
                        <p>Start by adding your first vocabulary word!</p>
                        <a href="vocabulary-add.php" class="btn-add" style="margin-top: 20px;">➕ Add New Word</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Text-to-speech function
        function speak(text) {
            if ('speechSynthesis' in window) {
                // Cancel any ongoing speech
                window.speechSynthesis.cancel();
                
                const utterance = new SpeechSynthesisUtterance(text);
                
                // Try to find a Chinese voice
                const voices = window.speechSynthesis.getVoices();
                const chineseVoice = voices.find(voice => voice.lang.startsWith('zh'));
                
                if (chineseVoice) {
                    utterance.voice = chineseVoice;
                    utterance.lang = chineseVoice.lang;
                } else {
                    utterance.lang = 'zh-CN';
                }
                
                utterance.rate = 0.8;
                window.speechSynthesis.speak(utterance);
            } else {
                alert('Sorry, your browser does not support text-to-speech.');
            }
        }

        // Load voices when they're ready
        if ('speechSynthesis' in window) {
            window.speechSynthesis.onvoiceschanged = function() {
                window.speechSynthesis.getVoices();
            };
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

        // Enhanced delete confirmation
function confirmDelete(button, word) {
    const card = button.closest('.vocab-card');
    
    // Add shake animation
    card.classList.add('confirm-delete');
    
    setTimeout(() => {
        card.classList.remove('confirm-delete');
    }, 500);
    
    // Show custom confirmation
    const confirmMsg = `Are you sure you want to delete the word "${word}"?\n\nThis action cannot be undone!`;
    
    return confirm(confirmMsg);
}

// Add loading state to buttons
document.querySelectorAll('.btn-action').forEach(button => {
    button.addEventListener('click', function(e) {
        if (this.classList.contains('btn-delete') && !confirm) {
            return;
        }
        
        // Add loading state
        this.classList.add('loading');
        
        // If it's not a delete action or user confirmed
        if (!this.classList.contains('btn-delete')) {
            setTimeout(() => {
                this.classList.remove('loading');
            }, 2000);
        }
    });
});

// Highlight buttons on card hover
document.querySelectorAll('.vocab-card').forEach(card => {
    card.addEventListener('mouseenter', function() {
        this.querySelectorAll('.btn-action').forEach(btn => {
            btn.style.opacity = '1';
        });
    });
    
    card.addEventListener('mouseleave', function() {
        this.querySelectorAll('.btn-action').forEach(btn => {
            btn.style.opacity = '0.9';
        });
    });
});
    </script>
</body>
</html>