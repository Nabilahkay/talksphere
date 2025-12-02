<?php
require_once '../config/session.php';
checkLogin();
require_once '../config/database.php';
require_once '../config/streak_functions.php';

$user = getCurrentUser();
$user_id = $user['user_id'];

// Get streak information
$streak = getUserStreak($user_id);
$badges = getUserBadges($user_id);
$total_points = getUserTotalPoints($user_id);

// Get recent activity (last 30 days)
$stmt = $conn->prepare("
    SELECT activity_date, COUNT(*) as activity_count, SUM(points_earned) as daily_points
    FROM user_activity
    WHERE user_id = ? AND activity_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY activity_date
    ORDER BY activity_date DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_activity = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get quiz statistics
$quiz_stats = ['total_quizzes' => 0, 'avg_score' => 0, 'total_score' => 0];

$check_table = $conn->query("SHOW TABLES LIKE 'quiz_results'");
if ($check_table && $check_table->num_rows > 0) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total_quizzes, 
               AVG(percentage) as avg_score,
               SUM(score) as total_score
        FROM quiz_results
        WHERE user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $temp = $result->fetch_assoc();
        if ($temp) {
            $quiz_stats = $temp;
        }
    }
}

// Get vocabulary progress
$vocab_progress = ['words_learned' => 0];

try {
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT vocab_id) as words_learned
        FROM user_progress
        WHERE user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $temp = $result->fetch_assoc();
        if ($temp) {
            $vocab_progress = $temp;
        }
    }
} catch (Exception $e) {
    error_log("Vocab progress error: " . $e->getMessage());
}

// ✅ FIX: Ensure $user has all required fields with defaults
$user_email = isset($user['email']) ? $user['email'] : 'No email';
$user_name = isset($user['full_name']) ? $user['full_name'] : 'User';
$user_created = isset($user['created_at']) ? $user['created_at'] : date('Y-m-d H:i:s');
$member_since = date('F Y', strtotime($user_created));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Mandarin Learning Platform</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/profile.css">
</head>
<body>
    <!--Sidebar-->
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="../img/talksphere_logo.png" alt="TalkSphere Logo" class="sidebar-logo">
            <h3>TALKSPHERE</h3>
        </div>
         <nav class="sidebar-nav">
            <a href="../dashboard.php"><span>🏠</span> Home</a>
            <a href="learn-vocab.php"><span>📚</span> Learn Vocabulary</a>
            <a href="quiz.php" class="active"><span>📝</span> Quiz</a>
             <a href="laptop-ar-scanner.php"><span>💻</span>AR Marker </a>
            <a href="profile.php"><span>👤</span> Profile</a>
        </nav>
    </div>

    <div class="main-content">
        <header class="header">
            <div class="header-top">
                <div class="hamburger">☰</div>
                <nav class="top-nav">
                    <a href="../dashboard.php">Home</a>
                    <a href="learn-vocab.php">Learn Vocab</a>
                    <a href="quiz.php">Quiz</a>
                    <a href="laptop-ar-scanner.php">AR Marker </a>
                    <a href="profile.php" class="active">Profile</a>
                </nav>
                <div class="user-info">
                    <span><?php echo htmlspecialchars($user_name, ENT_QUOTES, 'UTF-8'); ?></span>
                    <div class="user-avatar" id="userDropdown">
                        <span>👤</span>
                        <div class="dropdown-menu" id="dropdownMenu">
                            <a href="profile.php"><span>👤</span> My Profile</a>
                            <a href="../logout.php"><span>🔓</span> Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div class="profile-container">
            <!-- User Header -->
            <div class="profile-header">
                <div class="profile-avatar-large">
                    <span><?php echo strtoupper(substr($user_name, 0, 1)); ?></span>
                </div>
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($user_name, ENT_QUOTES, 'UTF-8'); ?></h1>
                    <p class="profile-email"><?php echo htmlspecialchars($user_email, ENT_QUOTES, 'UTF-8'); ?></p>
                    <p class="profile-join-date">Member since <?php echo htmlspecialchars($member_since, ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
            </div>

            <!-- Streak Section -->
            <div class="streak-section">
                <div class="streak-card">
                    <div class="streak-icon">🔥</div>
                    <div class="streak-content">
                        <h2>Current Streak</h2>
                        <div class="streak-number"><?php echo max(0, intval($streak['current_streak'])); ?></div>
                        <p class="streak-text">days in a row</p>
                        <?php if ($streak['current_streak'] > 0): ?>
                            <p class="streak-motivation">Keep it up! Don't break the chain! 💪</p>
                        <?php else: ?>
                            <p class="streak-motivation">Start your learning journey today! 🚀</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">🏆</div>
                        <div class="stat-value"><?php echo max(0, intval($streak['longest_streak'])); ?></div>
                        <div class="stat-label">Longest Streak</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">📅</div>
                        <div class="stat-value"><?php echo max(0, intval($streak['total_days_active'])); ?></div>
                        <div class="stat-label">Total Days Active</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">⭐</div>
                        <div class="stat-value"><?php echo max(0, intval($total_points)); ?></div>
                        <div class="stat-label">Total Points</div>
                    </div>
                </div>
            </div>

            <!-- Activity Calendar -->
            <div class="activity-calendar">
                <h2>30-Day Activity</h2>
                <div class="calendar-grid">
                    <?php
                    // Create 30-day calendar
                    $activity_map = [];
                    foreach ($recent_activity as $activity) {
                        $activity_map[$activity['activity_date']] = intval($activity['activity_count']);
                    }
                    
                    for ($i = 29; $i >= 0; $i--) {
                        $date = date('Y-m-d', strtotime("-$i days"));
                        $count = isset($activity_map[$date]) ? $activity_map[$date] : 0;
                        $intensity = $count > 0 ? min(4, ceil($count / 2)) : 0;
                        $day_name = date('D', strtotime($date));
                        
                        echo "<div class='calendar-day intensity-$intensity' title='$date: $count activities'>
                                <span class='day-label'>$day_name</span>
                              </div>";
                    }
                    ?>
                </div>
                <div class="calendar-legend">
                    <span>Less</span>
                    <div class="legend-item intensity-0"></div>
                    <div class="legend-item intensity-1"></div>
                    <div class="legend-item intensity-2"></div>
                    <div class="legend-item intensity-3"></div>
                    <div class="legend-item intensity-4"></div>
                    <span>More</span>
                </div>
            </div>

            <!-- Badges Section -->
            <div class="badges-section">
                <h2>Achievements & Badges</h2>
                <?php if (count($badges) > 0): ?>
                    <div class="badges-grid">
                        <?php foreach ($badges as $badge): ?>
                            <div class="badge-card earned">
                                <div class="badge-icon"><?php echo substr($badge['badge_name'], 0, 2); ?></div>
                                <div class="badge-name"><?php echo htmlspecialchars($badge['badge_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="badge-date">Earned: <?php echo date('M d, Y', strtotime($badge['earned_date'])); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-badges">
                        <p>🎯 No badges yet! Start learning to earn achievements.</p>
                    </div>
                <?php endif; ?>
                
                <!-- Available Badges -->
                <h3 style="margin-top: 40px;">Available Badges</h3>
                <div class="badges-grid">
                    <?php
                    $all_badges = [
                        ['type' => '3_day_streak', 'name' => '🔥 3 Day Streak', 'desc' => 'Learn for 3 days in a row'],
                        ['type' => '7_day_streak', 'name' => '⚡ 7 Day Streak', 'desc' => 'Learn for 7 days in a row'],
                        ['type' => '14_day_streak', 'name' => '💪 14 Day Streak', 'desc' => 'Learn for 14 days in a row'],
                        ['type' => '30_day_streak', 'name' => '🏆 30 Day Streak', 'desc' => 'Learn for 30 days in a row'],
                        ['type' => '100_day_streak', 'name' => '👑 100 Day Streak', 'desc' => 'Learn for 100 days in a row']
                    ];
                    
                    $earned_types = array_column($badges, 'badge_type');
                    
                    foreach ($all_badges as $badge) {
                        $is_earned = in_array($badge['type'], $earned_types);
                        if (!$is_earned) {
                            echo "<div class='badge-card locked'>
                                    <div class='badge-icon'>🔒</div>
                                    <div class='badge-name'>" . htmlspecialchars($badge['name'], ENT_QUOTES, 'UTF-8') . "</div>
                                    <div class='badge-desc'>" . htmlspecialchars($badge['desc'], ENT_QUOTES, 'UTF-8') . "</div>
                                  </div>";
                        }
                    }
                    ?>
                </div>
            </div>

            <!-- Learning Statistics -->
            <div class="stats-section">
                <h2>Learning Statistics</h2>
                <div class="stats-cards">
                    <div class="stat-detail-card">
                        <div class="stat-detail-icon">📝</div>
                        <div class="stat-detail-content">
                            <h3>Quiz Performance</h3>
                            <div class="stat-detail-value"><?php echo intval($quiz_stats['total_quizzes'] ?? 0); ?></div>
                            <p>Quizzes Completed</p>
                            <?php if (isset($quiz_stats['avg_score']) && $quiz_stats['avg_score']): ?>
                                <p class="stat-detail-sub">Average Score: <?php echo round(floatval($quiz_stats['avg_score']), 1); ?>%</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="stat-detail-card">
                        <div class="stat-detail-icon">📚</div>
                        <div class="stat-detail-content">
                            <h3>Vocabulary Mastery</h3>
                            <div class="stat-detail-value"><?php echo intval($vocab_progress['words_learned'] ?? 0); ?></div>
                            <p>Words Learned</p>
                        </div>
                    </div>

                    <div class="stat-detail-card">
                        <div class="stat-detail-icon">🎯</div>
                        <div class="stat-detail-content">
                            <h3>Total Points</h3>
                            <div class="stat-detail-value"><?php echo max(0, intval($total_points)); ?></div>
                            <p>Points Earned</p>
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

        // Animate numbers on page load
        document.addEventListener('DOMContentLoaded', () => {
            const currentStreak = <?php echo max(0, intval($streak['current_streak'])); ?>;
            animateValue('streak-number', 0, currentStreak, 1500);
        });

        function animateValue(className, start, end, duration) {
            const element = document.querySelector('.' + className);
            if (!element) return;
            
            const range = end - start;
            if (range === 0) {
                element.textContent = end;
                return;
            }
            
            const increment = end > start ? 1 : -1;
            const stepTime = Math.abs(Math.floor(duration / range));
            let current = start;
            
            const timer = setInterval(() => {
                current += increment;
                element.textContent = current;
                if (current === end) {
                    clearInterval(timer);
                }
            }, stepTime);
        }
    </script>
</body>
</html>