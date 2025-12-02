<?php
session_start();
require_once '../config/database.php';
require_once 'config/admin_auth.php';

checkAdminLogin();
$admin = getCurrentAdmin();

// Pagination
$per_page = 20;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

// Filters
$theme_filter = isset($_GET['theme']) ? intval($_GET['theme']) : 0;
$difficulty_filter = isset($_GET['difficulty']) ? $_GET['difficulty'] : '';
$user_filter = isset($_GET['user']) ? intval($_GET['user']) : 0;
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build WHERE clause
$where_conditions = [];
$where_params = [];
$param_types = '';

if ($theme_filter > 0) {
    $where_conditions[] = "qr.theme_id = ?";
    $where_params[] = $theme_filter;
    $param_types .= 'i';
}

if ($difficulty_filter) {
    $where_conditions[] = "qr.difficulty = ?";
    $where_params[] = $difficulty_filter;
    $param_types .= 's';
}

if ($user_filter > 0) {
    $where_conditions[] = "qr.user_id = ?";
    $where_params[] = $user_filter;
    $param_types .= 'i';
}

if ($date_from) {
    $where_conditions[] = "DATE(qr.completed_at) >= ?";
    $where_params[] = $date_from;
    $param_types .= 's';
}

if ($date_to) {
    $where_conditions[] = "DATE(qr.completed_at) <= ?";
    $where_params[] = $date_to;
    $param_types .= 's';
}

$where_clause = '';
if (count($where_conditions) > 0) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Get total count
$count_query = "SELECT COUNT(*) as total FROM quiz_results qr $where_clause";
if ($param_types) {
    $stmt = $conn->prepare($count_query);
    $stmt->bind_param($param_types, ...$where_params);
    $stmt->execute();
    $total_quizzes = $stmt->get_result()->fetch_assoc()['total'];
} else {
    $total_quizzes = $conn->query($count_query)->fetch_assoc()['total'];
}

$total_pages = ceil($total_quizzes / $per_page);

// Get quiz results with pagination
$quiz_query = "SELECT qr.*, u.username, u.full_name, t.theme_name
               FROM quiz_results qr
               LEFT JOIN users u ON qr.user_id = u.user_id
               LEFT JOIN themes t ON qr.theme_id = t.theme_id
               $where_clause
               ORDER BY qr.completed_at DESC
               LIMIT ? OFFSET ?";

$where_params[] = $per_page;
$where_params[] = $offset;
$param_types .= 'ii';

$stmt = $conn->prepare($quiz_query);
$stmt->bind_param($param_types, ...$where_params);
$stmt->execute();
$quiz_results = $stmt->get_result();

// Get overall statistics (with null safety)
$total_attempts = $conn->query("SELECT COUNT(*) as count FROM quiz_results")->fetch_assoc()['count'];

$avg_result = $conn->query("SELECT AVG(percentage) as avg FROM quiz_results")->fetch_assoc();
$avg_score = isset($avg_result['avg']) && $avg_result['avg'] !== null ? round($avg_result['avg']) : 0;

$total_users_taken = $conn->query("SELECT COUNT(DISTINCT user_id) as count FROM quiz_results")->fetch_assoc()['count'];

$pass_rate = $conn->query("SELECT COUNT(*) as count FROM quiz_results WHERE percentage >= 70")->fetch_assoc()['count'];
$pass_percentage = $total_attempts > 0 ? round(($pass_rate / $total_attempts) * 100) : 0;

// Get themes for filter
$themes = $conn->query("SELECT * FROM themes ORDER BY theme_name");

// Get users for filter
$users = $conn->query("SELECT DISTINCT u.user_id, u.username, u.full_name FROM users u INNER JOIN quiz_results qr ON u.user_id = qr.user_id ORDER BY u.full_name");

// Get performance by theme
$theme_performance = $conn->query("
    SELECT 
        COALESCE(t.theme_name, 'Unknown') as theme_name, 
        COUNT(*) as attempts,
        ROUND(AVG(qr.percentage), 0) as avg_score,
        ROUND(MAX(qr.percentage), 0) as highest_score,
        ROUND(MIN(qr.percentage), 0) as lowest_score
    FROM quiz_results qr
    LEFT JOIN themes t ON qr.theme_id = t.theme_id
    GROUP BY qr.theme_id
    ORDER BY attempts DESC
    LIMIT 5
");

// Get performance by difficulty
$difficulty_stats = $conn->query("
    SELECT 
        difficulty,
        COUNT(*) as attempts,
        ROUND(AVG(percentage), 0) as avg_score
    FROM quiz_results
    GROUP BY difficulty
    ORDER BY FIELD(difficulty, 'beginner', 'intermediate', 'advanced')
");

// Get recent high scores
$high_scores = $conn->query("
    SELECT qr.*, u.full_name, t.theme_name
    FROM quiz_results qr
    LEFT JOIN users u ON qr.user_id = u.user_id
    LEFT JOIN themes t ON qr.theme_id = t.theme_id
    WHERE qr.percentage >= 90
    ORDER BY qr.completed_at DESC
    LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Analytics - Admin Panel</title>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .filters-panel {
            background: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .filter-item {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-label {
            font-size: 13px;
            font-weight: 600;
            color: #333;
        }

        .filter-input,
        .filter-select {
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .filter-input:focus,
        .filter-select:focus {
            outline: none;
            border-color: #8B0000;
        }

        .filter-actions {
            display: flex;
            gap: 10px;
        }

        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
            margin-bottom: 30px;
        }

        .analytics-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }

        .performance-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f8f9fa;
        }

        .performance-item:last-child {
            border-bottom: none;
        }

        .performance-label {
            font-size: 14px;
            color: #666;
        }

        .performance-value {
            font-size: 16px;
            font-weight: 700;
            color: #8B0000;
        }

        .score-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .score-excellent {
            background: #d4edda;
            color: #155724;
        }

        .score-good {
            background: #fff3cd;
            color: #856404;
        }

        .score-fair {
            background: #fff3e0;
            color: #e65100;
        }

        .score-poor {
            background: #f8d7da;
            color: #721c24;
        }

        .difficulty-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
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

        .chart-bar {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .bar-label {
            font-size: 13px;
            color: #666;
            min-width: 120px;
        }

        .bar-container {
            flex: 1;
            height: 30px;
            background: #f0f0f0;
            border-radius: 15px;
            overflow: hidden;
            position: relative;
        }

        .bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #8B0000 0%, #A52A2A 100%);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding-right: 10px;
            color: white;
            font-size: 12px;
            font-weight: 600;
            transition: width 1s ease;
        }

        .high-score-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }

        .high-score-item:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }

        .high-score-user {
            font-size: 14px;
            font-weight: 600;
            color: #333;
        }

        .high-score-theme {
            font-size: 12px;
            color: #999;
        }

        .high-score-percentage {
            font-size: 20px;
            font-weight: 700;
            color: #4CAF50;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-icon {
            font-size: 80px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        @media (max-width: 1200px) {
            .analytics-grid {
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
            <a href="quizzes.php" class="active"><span>📝</span> Quizzes</a>
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
            <h1>Quiz Analytics</h1>
            <div class="header-actions">
                <button class="btn-view-site" onclick="window.print()">🖨️ Print Report</button>
                <a href="dashboard.php" class="btn-view-site">← Dashboard</a>
            </div>
        </div>

        <div class="content-wrapper">
            <!-- Overall Stats -->
            <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 30px;">
                <div class="stat-card blue">
                    <div class="stat-icon">📝</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $total_attempts; ?></div>
                        <div class="stat-label">Total Attempts</div>
                    </div>
                </div>

                <div class="stat-card green">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo round($avg_score); ?>%</div>
                        <div class="stat-label">Average Score</div>
                    </div>
                </div>

                <div class="stat-card purple">
                    <div class="stat-icon">👥</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $total_users_taken; ?></div>
                        <div class="stat-label">Active Learners</div>
                    </div>
                </div>

                <div class="stat-card orange">
                    <div class="stat-icon">✅</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $pass_percentage; ?>%</div>
                        <div class="stat-label">Pass Rate (≥70%)</div>
                    </div>
                </div>
            </div>

            <!-- Analytics Grid -->
            <div class="analytics-grid">
                <!-- Theme Performance -->
                <div class="analytics-card">
                    <h3 class="card-title">📚 Top Themes by Attempts</h3>
                    <?php if ($theme_performance->num_rows > 0): ?>
                        <?php 
                        $max_attempts = 0;
                        $themes_data = [];
                        while($theme = $theme_performance->fetch_assoc()) {
                            $themes_data[] = $theme;
                            if ($theme['attempts'] > $max_attempts) {
                                $max_attempts = $theme['attempts'];
                            }
                        }
                        
                        foreach($themes_data as $theme):
                            $width = $max_attempts > 0 ? ($theme['attempts'] / $max_attempts) * 100 : 0;
                        ?>
                        <div class="chart-bar">
                            <div class="bar-label"><?php echo htmlspecialchars($theme['theme_name'] ?? 'Unknown'); ?></div>
                            <div class="bar-container">
                                <div class="bar-fill" style="width: <?php echo $width; ?>%">
                                    <?php echo $theme['attempts']; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: #999; text-align: center;">No data available</p>
                    <?php endif; ?>
                </div>

                <!-- Difficulty Performance -->
                <div class="analytics-card">
                    <h3 class="card-title">🎯 Performance by Difficulty</h3>
                    <?php if ($difficulty_stats->num_rows > 0): ?>
                        <?php while($diff = $difficulty_stats->fetch_assoc()): ?>
                        <div class="performance-item">
                            <div>
                                <span class="difficulty-badge difficulty-<?php echo $diff['difficulty']; ?>">
                                    <?php echo ucfirst($diff['difficulty']); ?>
                                </span>
                                <span class="performance-label" style="margin-left: 10px;">
                                    (<?php echo $diff['attempts']; ?> attempts)
                                </span>
                            </div>
                            <div class="performance-value">
                                <?php echo round($diff['avg_score']); ?>%
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="color: #999; text-align: center;">No data available</p>
                    <?php endif; ?>
                </div>

                <!-- Recent High Scores -->
                <div class="analytics-card" style="grid-column: 1 / -1;">
                    <h3 class="card-title">🏆 Recent High Scores (≥90%)</h3>
                    <?php if ($high_scores->num_rows > 0): ?>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 15px;">
                            <?php while($score = $high_scores->fetch_assoc()): ?>
                            <div class="high-score-item">
                                <div>
                                    <div class="high-score-user">
                                        <?php echo htmlspecialchars($score['full_name']); ?>
                                    </div>
                                    <div class="high-score-theme">
                                        <?php echo htmlspecialchars($score['theme_name'] ?? 'Unknown Theme'); ?> • 
                                        <span class="difficulty-badge difficulty-<?php echo $score['difficulty']; ?>">
                                            <?php echo ucfirst($score['difficulty']); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="high-score-percentage">
                                    <?php echo round($score['percentage']); ?>%
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p style="color: #999; text-align: center;">No high scores yet</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Filters Panel -->
            <div class="filters-panel">
                <h3 style="margin-bottom: 20px; color: #333;">🔍 Filter Quiz Results</h3>
                <form method="GET">
                    <div class="filters-grid">
                        <div class="filter-item">
                            <label class="filter-label">Theme</label>
                            <select name="theme" class="filter-select">
                                <option value="0">All Themes</option>
                                <?php 
                                $themes->data_seek(0);
                                while($theme = $themes->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $theme['theme_id']; ?>" 
                                            <?php echo $theme_filter == $theme['theme_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($theme['theme_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="filter-item">
                            <label class="filter-label">Difficulty</label>
                            <select name="difficulty" class="filter-select">
                                <option value="">All Difficulties</option>
                                <option value="beginner" <?php echo $difficulty_filter == 'beginner' ? 'selected' : ''; ?>>Beginner</option>
                                <option value="intermediate" <?php echo $difficulty_filter == 'intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                                <option value="advanced" <?php echo $difficulty_filter == 'advanced' ? 'selected' : ''; ?>>Advanced</option>
                            </select>
                        </div>

                        <div class="filter-item">
                            <label class="filter-label">User</label>
                            <select name="user" class="filter-select">
                                <option value="0">All Users</option>
                                <?php 
                                $users->data_seek(0);
                                while($user = $users->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $user['user_id']; ?>" 
                                            <?php echo $user_filter == $user['user_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user['full_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="filter-item">
                            <label class="filter-label">Date From</label>
                            <input type="date" name="date_from" class="filter-input" value="<?php echo $date_from; ?>">
                        </div>

                        <div class="filter-item">
                            <label class="filter-label">Date To</label>
                            <input type="date" name="date_to" class="filter-input" value="<?php echo $date_to; ?>">
                        </div>
                    </div>

                    <div class="filter-actions">
                        <button type="submit" class="btn-search">Apply Filters</button>
                        <a href="quizzes.php" class="btn-clear">Clear All</a>
                    </div>
                </form>
            </div>

            <!-- Quiz Results Table -->
            <?php if ($quiz_results->num_rows > 0): ?>
                <div class="admin-card">
                    <div class="card-header">
                        <h2>Quiz Results (<?php echo $total_quizzes; ?>)</h2>
                    </div>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Theme</th>
                                    <th>Difficulty</th>
                                    <th>Score</th>
                                    <th>Percentage</th>
                                    <th>Time</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($result = $quiz_results->fetch_assoc()): 
                                    $score_class = '';
                                    if ($result['percentage'] >= 90) $score_class = 'score-excellent';
                                    elseif ($result['percentage'] >= 70) $score_class = 'score-good';
                                    elseif ($result['percentage'] >= 50) $score_class = 'score-fair';
                                    else $score_class = 'score-poor';
                                ?>
                                <tr>
                                    <td><strong>#<?php echo $result['result_id']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($result['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($result['theme_name'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="difficulty-badge difficulty-<?php echo $result['difficulty']; ?>">
                                            <?php echo ucfirst($result['difficulty']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $result['score']; ?>/<?php echo $result['total_questions']; ?></td>
                                    <td>
                                        <span class="score-badge <?php echo $score_class; ?>">
                                            <?php echo round($result['percentage']); ?>%
                                        </span>
                                    </td>
                                    <td><?php echo isset($result['time_taken']) ? $result['time_taken'] . 's' : 'N/A'; ?></td>
                                    <td><?php echo date('M d, Y g:i A', strtotime($result['completed_at'])); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?><?php echo $theme_filter ? '&theme=' . $theme_filter : ''; ?><?php echo $difficulty_filter ? '&difficulty=' . $difficulty_filter : ''; ?><?php echo $user_filter ? '&user=' . $user_filter : ''; ?><?php echo $date_from ? '&date_from=' . $date_from : ''; ?><?php echo $date_to ? '&date_to=' . $date_to : ''; ?>" class="page-link">← Previous</a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                <a href="?page=<?php echo $i; ?><?php echo $theme_filter ? '&theme=' . $theme_filter : ''; ?><?php echo $difficulty_filter ? '&difficulty=' . $difficulty_filter : ''; ?><?php echo $user_filter ? '&user=' . $user_filter : ''; ?><?php echo $date_from ? '&date_from=' . $date_from : ''; ?><?php echo $date_to ? '&date_to=' . $date_to : ''; ?>" 
                                   class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                <span class="page-link disabled">...</span>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?><?php echo $theme_filter ? '&theme=' . $theme_filter : ''; ?><?php echo $difficulty_filter ? '&difficulty=' . $difficulty_filter : ''; ?><?php echo $user_filter ? '&user=' . $user_filter : ''; ?><?php echo $date_from ? '&date_from=' . $date_from : ''; ?><?php echo $date_to ? '&date_to=' . $date_to : ''; ?>" class="page-link">Next →</a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">📝</div>
                    <h3>No Quiz Results Found</h3>
                    <p>No quizzes have been completed yet or no results match your filters.</p>
                    <?php if ($theme_filter || $difficulty_filter || $user_filter || $date_from || $date_to): ?>
                        <a href="quizzes.php" class="btn-clear" style="margin-top: 20px;">Clear Filters</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Animate progress bars on load
        document.addEventListener('DOMContentLoaded', function() {
            const progressBars = document.querySelectorAll('.bar-fill');
            progressBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0';
                setTimeout(() => {
                    bar.style.width = width;
                }, 100);
            });
        });

        // Print functionality
        window.onbeforeprint = function() {
            document.querySelector('.admin-sidebar').style.display = 'none';
            document.querySelector('.admin-header').style.display = 'none';
        };

        window.onafterprint = function() {
            document.querySelector('.admin-sidebar').style.display = 'block';
            document.querySelector('.admin-header').style.display = 'flex';
        };
    </script>
</body>
</html>