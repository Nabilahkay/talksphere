<?php
require_once 'config/session.php';
checkLogin();
require_once 'config/database.php';
require_once 'config/streak_functions.php';

$user = getCurrentUser();
$user_id = $user['user_id'];

// Get user stats
$streak = getUserStreak($user_id);
$total_points = getUserTotalPoints($user_id);

// Get quiz statistics
$quiz_stats_query = "
    SELECT 
        COUNT(*) as total_quizzes,
        AVG(percentage) as avg_score,
        MAX(percentage) as best_score,
        SUM(CASE WHEN percentage >= 80 THEN 1 ELSE 0 END) as passed_quizzes
    FROM quiz_results 
    WHERE user_id = ?
";
$stmt = $conn->prepare($quiz_stats_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$quiz_stats = $stmt->get_result()->fetch_assoc();

// Get vocabulary progress by theme
$vocab_progress_query = "
    SELECT 
        t.theme_name,
        t.theme_id,
        COUNT(DISTINCT v.vocab_id) as total_words,
        COUNT(DISTINCT up.vocab_id) as learned_words
    FROM themes t
    LEFT JOIN vocabulary v ON t.theme_id = v.theme_id
    LEFT JOIN user_progress up ON v.vocab_id = up.vocab_id AND up.user_id = ?
    WHERE t.theme_name IN ('Colors', 'Numbers', 'Fruits')
    GROUP BY t.theme_id, t.theme_name
    ORDER BY t.theme_id
";
$stmt = $conn->prepare($vocab_progress_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$vocab_progress = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get recent activity (last 7 days)
$recent_activity_query = "
    SELECT 
        activity_date,
        activity_type,
        points_earned,
        created_at
    FROM user_activity
    WHERE user_id = ? AND activity_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ORDER BY created_at DESC
    LIMIT 10
";
$stmt = $conn->prepare($recent_activity_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_activities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get recent quiz results
$recent_quizzes_query = "
    SELECT 
        qr.*,
        t.theme_name
    FROM quiz_results qr
    LEFT JOIN themes t ON qr.theme_id = t.theme_id
    WHERE qr.user_id = ?
    ORDER BY qr.completed_at DESC
    LIMIT 5
";
$stmt = $conn->prepare($recent_quizzes_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_quizzes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate overall progress
$total_vocab_words = 0;
$total_learned_words = 0;
foreach ($vocab_progress as $theme) {
    $total_vocab_words += $theme['total_words'];
    $total_learned_words += $theme['learned_words'];
}
$overall_vocab_percentage = $total_vocab_words > 0 ? round(($total_learned_words / $total_vocab_words) * 100) : 0;

// Get weekly activity data for chart
$weekly_activity_query = "
    SELECT 
        DATE(activity_date) as date,
        COUNT(*) as activity_count
    FROM user_activity
    WHERE user_id = ? AND activity_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(activity_date)
    ORDER BY date ASC
";
$stmt = $conn->prepare($weekly_activity_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$weekly_activity = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Mandarin Learning Platform</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/progress-dashboard.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="img/talksphere_logo.png" alt="TalkSphere Logo" class="sidebar-logo">
            <h3>TALKSPHERE</h3>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="active"><span>🏠</span> Home</a>
            <a href="pages/learn-vocab.php"><span>📚</span> Learn Vocabulary</a>
            <a href="pages/quiz.php"><span>📝</span> Quiz</a>
            <a href="pages/laptop-ar-scanner.php"><span>💻</span> AR Marker </a>
            <a href="pages/profile.php"><span>👤</span> Profile</a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <header class="header">
            <div class="header-top">
                <div class="hamburger">☰</div>
                <nav class="top-nav">
                    <a href="dashboard.php" class="active">Home</a>
                    <a href="pages/learn-vocab.php">Learn Vocab</a>
                    <a href="pages/quiz.php">Quiz</a>
                    <a href="pages/laptop-ar-scanner.php">AR Marker</a>
                    <a href="pages/profile.php">Profile</a>
                </nav>
                <div class="user-info">
                    <span><?php echo htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <div class="user-avatar" id="userDropdown">
                        <span>👤</span>
                        <div class="dropdown-menu" id="dropdownMenu">
                            <a href="pages/profile.php"><span>👤</span> My Profile</a>
                            <a href="logout.php"><span>🔓</span> Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Dashboard Content -->
        <div class="dashboard-container">
            <!-- Welcome Section -->
            <div class="welcome-section">
                <h1>Welcome back, <?php echo htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8'); ?>! 👋</h1>
                <p class="welcome-subtitle">Here's your learning progress summary</p>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card streak-card">
                    <div class="stat-icon">🔥</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $streak['current_streak']; ?></div>
                        <div class="stat-label">Day Streak</div>
                        <div class="stat-sublabel">Keep it going!</div>
                    </div>
                </div>

                <div class="stat-card points-card">
                    <div class="stat-icon">⭐</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $total_points; ?></div>
                        <div class="stat-label">Total Points</div>
                        <div class="stat-sublabel">Earned so far</div>
                    </div>
                </div>

                <div class="stat-card quiz-card">
                    <div class="stat-icon">📝</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $quiz_stats['total_quizzes'] ?? 0; ?></div>
                        <div class="stat-label">Quizzes Completed</div>
                        <?php if ($quiz_stats['avg_score']): ?>
                            <div class="stat-sublabel">Avg: <?php echo round($quiz_stats['avg_score']); ?>%</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="stat-card vocab-card">
                    <div class="stat-icon">📚</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $total_learned_words; ?></div>
                        <div class="stat-label">Words Learned</div>
                        <div class="stat-sublabel">Out of <?php echo $total_vocab_words; ?></div>
                    </div>
                </div>
            </div>

            <!-- Main Dashboard Grid -->
            <div class="dashboard-grid">
                <!-- Left Column -->
                <div class="dashboard-left">
                    <!-- Vocabulary Progress -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h2>📚 Vocabulary Progress</h2>
                            <span class="overall-progress"><?php echo $overall_vocab_percentage; ?>% Complete</span>
                        </div>
                        <div class="vocab-themes-progress">
                            <?php foreach ($vocab_progress as $theme): 
                                $percentage = $theme['total_words'] > 0 ? round(($theme['learned_words'] / $theme['total_words']) * 100) : 0;
                                $colors = [
                                    'Colors' => '#4caf50',
                                    'Numbers' => '#ff9800',
                                    'Fruits' => '#f44336'
                                ];
                                $color = $colors[$theme['theme_name']] ?? '#2196F3';
                            ?>
                            <div class="theme-progress-item">
                                <div class="theme-progress-header">
                                    <span class="theme-name"><?php echo htmlspecialchars($theme['theme_name']); ?></span>
                                    <span class="theme-stats"><?php echo $theme['learned_words']; ?>/<?php echo $theme['total_words']; ?></span>
                                </div>
                                <div class="progress-bar-wrapper">
                                    <div class="progress-bar-fill" style="width: <?php echo $percentage; ?>%; background: <?php echo $color; ?>;"></div>
                                </div>
                                <div class="theme-progress-footer">
                                    <span class="percentage"><?php echo $percentage; ?>%</span>
                                    <a href="pages/vocabulary-detail.php?theme_id=<?php echo $theme['theme_id']; ?>" class="continue-link">Continue →</a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Recent Quiz Results -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h2>📊 Recent Quiz Results</h2>
                            <a href="pages/quiz.php" class="view-all-link">View All</a>
                        </div>
                        <?php if (count($recent_quizzes) > 0): ?>
                            <div class="quiz-results-list">
                                <?php foreach ($recent_quizzes as $quiz): 
                                    $score_class = $quiz['percentage'] >= 80 ? 'excellent' : ($quiz['percentage'] >= 60 ? 'good' : 'needs-work');
                                ?>
                                <div class="quiz-result-item <?php echo $score_class; ?>">
                                    <div class="quiz-result-icon">
                                        <?php echo $quiz['percentage'] >= 80 ? '🏆' : ($quiz['percentage'] >= 60 ? '👍' : '💪'); ?>
                                    </div>
                                    <div class="quiz-result-info">
                                        <div class="quiz-theme"><?php echo htmlspecialchars($quiz['theme_name'] ?? 'Quiz'); ?></div>
                                        <div class="quiz-details">
                                            <?php echo ucfirst($quiz['difficulty']); ?> • 
                                            <?php echo date('M d', strtotime($quiz['completed_at'])); ?>
                                        </div>
                                    </div>
                                    <div class="quiz-score">
                                        <div class="score-percentage"><?php echo round($quiz['percentage']); ?>%</div>
                                        <div class="score-fraction"><?php echo $quiz['score']; ?>/<?php echo $quiz['total_questions']; ?></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <div class="empty-icon">📝</div>
                                <p>No quizzes completed yet</p>
                                <a href="pages/quiz.php" class="btn-primary">Take Your First Quiz</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="dashboard-right">
                    <!-- Weekly Activity Chart -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h2>📈 Weekly Activity</h2>
                        </div>
                        <div class="activity-chart">
                            <?php 
                            // Create array for last 7 days
                            $activity_map = [];
                            foreach ($weekly_activity as $activity) {
                                $activity_map[$activity['date']] = $activity['activity_count'];
                            }
                            
                            for ($i = 6; $i >= 0; $i--) {
                                $date = date('Y-m-d', strtotime("-$i days"));
                                $count = $activity_map[$date] ?? 0;
                                $day_name = date('D', strtotime($date));
                                $height = $count > 0 ? min(100, ($count / 10) * 100) : 5;
                            ?>
                            <div class="chart-bar">
                                <div class="bar-fill" style="height: <?php echo $height; ?>%;" title="<?php echo $count; ?> activities"></div>
                                <div class="bar-label"><?php echo $day_name; ?></div>
                            </div>
                            <?php } ?>
                        </div>
                    </div>

                    <!-- Recent Activity Timeline -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h2>🕒 Recent Activity</h2>
                        </div>
                        <?php if (count($recent_activities) > 0): ?>
                            <div class="activity-timeline">
                                <?php foreach ($recent_activities as $activity): 
                                    $icons = [
                                        'quiz' => '📝',
                                        'vocab_learned' => '📚',
                                        'vocab_page_visit' => '👀',
                                        'general' => '✨'
                                    ];
                                    $icon = $icons[$activity['activity_type']] ?? '•';
                                    
                                    $labels = [
                                        'quiz' => 'Completed Quiz',
                                        'vocab_learned' => 'Learned Vocabulary',
                                        'vocab_page_visit' => 'Visited Vocab Page',
                                        'general' => 'Activity'
                                    ];
                                    $label = $labels[$activity['activity_type']] ?? 'Activity';
                                ?>
                                <div class="activity-item">
                                    <div class="activity-icon"><?php echo $icon; ?></div>
                                    <div class="activity-content">
                                        <div class="activity-label"><?php echo $label; ?></div>
                                        <div class="activity-time"><?php echo date('M d, g:i A', strtotime($activity['created_at'])); ?></div>
                                    </div>
                                    <div class="activity-points">+<?php echo $activity['points_earned']; ?> pts</div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <div class="empty-icon">🕒</div>
                                <p>No recent activity</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Quick Actions -->
                    <div class="dashboard-card quick-actions-card">
                        <div class="card-header">
                            <h2>🚀 Quick Actions</h2>
                        </div>
                        <div class="quick-actions">
                            <a href="pages/learn-vocab.php" class="action-btn vocab-btn">
                                <span class="action-icon">📚</span>
                                <span class="action-text">Learn Vocabulary</span>
                            </a>
                            <a href="pages/quiz.php" class="action-btn quiz-btn">
                                <span class="action-icon">📝</span>
                                <span class="action-text">Take a Quiz</span>
                            </a>
                            <a href="pages/profile.php" class="action-btn profile-btn">
                                <span class="action-icon">👤</span>
                                <span class="action-text">View Profile</span>
                            </a>
                            <a href="pages/laptop-ar-scanner.php" class="action-btn ar-btn">
                            <span class="action-icon">💻</span>
                            <span class="action-text">Laptop AR Scanner</span>
                            </a>
<div style="margin-top: 30px;">
    <h2 style="color: #333; margin-bottom: 20px; font-size: 24px;">
        📱 AR Object Learning
    </h2>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">

        <!-- Download QR Codes (Optional) -->
        <a href="download-qr-guide.php" style="text-decoration: none;">
            <div style="background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%); color: white; padding: 30px; border-radius: 15px; text-align: center; transition: all 0.3s ease; cursor: pointer; box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);">
                <div style="font-size: 48px; margin-bottom: 15px;">📥</div>
                <h3 style="font-size: 20px; margin-bottom: 10px;">QR Codes Guide</h3>
                <p style="font-size: 14px; opacity: 0.9;">How to use AR learning system</p>
            </div>
        </a>

        <!-- Statistics -->
        <?php
        $ar_scans = $conn->query("SELECT COUNT(*) as count FROM user_activity WHERE user_id = {$_SESSION['user_id']} AND activity_type = 'ar_scan'")->fetch_assoc()['count'] ?? 0;
        ?>
        <div style="background: linear-gradient(135deg, #FF9800 0%, #F57C00 100%); color: white; padding: 30px; border-radius: 15px; text-align: center; box-shadow: 0 4px 15px rgba(255, 152, 0, 0.3);">
            <div style="font-size: 48px; margin-bottom: 15px;">📊</div>
            <h3 style="font-size: 20px; margin-bottom: 10px;">Your AR Scans</h3>
            <p style="font-size: 32px; font-weight: 700; margin: 10px 0;"><?php echo $ar_scans; ?></p>
            <p style="font-size: 14px; opacity: 0.9;">Objects scanned</p>
        </div>
    </div>
</div>
                        </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const hamburger = document.querySelector('.hamburger');
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');
        
        hamburger.addEventListener('click', () => {
            sidebar.classList.toggle('closed');
            mainContent.classList.toggle('expanded');
        });
        
        const userDropdown = document.getElementById('userDropdown');
        const dropdownMenu = document.getElementById('dropdownMenu');
        
        userDropdown.addEventListener('click', (e) => {
            e.stopPropagation();
            dropdownMenu.classList.toggle('show');
        });
        
        document.addEventListener('click', () => {
            dropdownMenu.classList.remove('show');
        });

        // Animate numbers on load
        document.addEventListener('DOMContentLoaded', () => {
            animateStatValues();
        });

        function animateStatValues() {
            const statValues = document.querySelectorAll('.stat-value');
            statValues.forEach(stat => {
                const target = parseInt(stat.textContent);
                animateValue(stat, 0, target, 1000);
            });
        }

        function animateValue(element, start, end, duration) {
            const range = end - start;
            if (range === 0) return;
            
            const increment = range / (duration / 16);
            let current = start;
            
            const timer = setInterval(() => {
                current += increment;
                if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
                    current = end;
                    clearInterval(timer);
                }
                element.textContent = Math.round(current);
            }, 16);
        }
    </script>
</body>
</html>
