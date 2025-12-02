<?php
session_start();
require_once '../config/database.php';
require_once 'config/admin_auth.php';

checkAdminLogin();
$admin = getCurrentAdmin();

$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

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

// Get user statistics
$stats = [];

// Quiz stats
$stmt = $conn->prepare("SELECT COUNT(*) as count, AVG(percentage) as avg_score FROM quiz_results WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$quiz_stats = $stmt->get_result()->fetch_assoc();
$stats['quiz_count'] = $quiz_stats['count'];
$stats['avg_score'] = round($quiz_stats['avg_score'] ?? 0);

// Streak stats
$stmt = $conn->prepare("SELECT * FROM user_streaks WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$streak = $stmt->get_result()->fetch_assoc();
$stats['current_streak'] = $streak['current_streak'] ?? 0;
$stats['longest_streak'] = $streak['longest_streak'] ?? 0;
$stats['total_days'] = $streak['total_days_active'] ?? 0;

// Total points
$stmt = $conn->prepare("SELECT SUM(points_earned) as total FROM user_activity WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$points = $stmt->get_result()->fetch_assoc();
$stats['total_points'] = $points['total'] ?? 0;

// Vocabulary progress
$stmt = $conn->prepare("SELECT COUNT(DISTINCT vocab_id) as count FROM user_progress WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$vocab = $stmt->get_result()->fetch_assoc();
$stats['words_learned'] = $vocab['count'];

// Recent quizzes
$stmt = $conn->prepare("SELECT qr.*, t.theme_name FROM quiz_results qr LEFT JOIN themes t ON qr.theme_id = t.theme_id WHERE qr.user_id = ? ORDER BY qr.completed_at DESC LIMIT 10");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_quizzes = $stmt->get_result();

// Recent activity
$stmt = $conn->prepare("SELECT * FROM user_activity WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_activity = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Details - <?php echo htmlspecialchars($user['full_name']); ?></title>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .user-profile {
            background: white;
            border-radius: 15px;
            padding: 40px;
            margin-bottom: 30px;
            display: flex;
            gap: 30px;
            align-items: center;
        }

        .user-avatar-large {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: white;
            font-weight: 700;
        }

        .user-details {
            flex: 1;
        }

        .user-name {
            font-size: 32px;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }

        .user-meta {
            display: flex;
            gap: 30px;
            margin-top: 15px;
        }

        .meta-item {
            display: flex;
            flex-direction: column;
        }

        .meta-label {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .meta-value {
            font-size: 14px;
            color: #333;
            font-weight: 600;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
            margin-bottom: 30px;
        }

        .info-box {
            background: white;
            border-radius: 15px;
            padding: 25px;
        }

        .info-box h3 {
            font-size: 18px;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f8f9fa;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #666;
            font-size: 14px;
        }

        .info-value {
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="admin-main">
        <div class="admin-header">
            <h1>User Details</h1>
            <div class="header-actions">
                <a href="user-edit.php?id=<?php echo $user_id; ?>" class="btn-view-site">✏️ Edit User</a>
                <a href="users.php" class="btn-view-site">← Back to Users</a>
            </div>
        </div>

        <div class="content-wrapper">
            <!-- User Profile -->
            <div class="user-profile">
                <div class="user-avatar-large">
                    <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                </div>
                <div class="user-details">
                    <div class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                    <div class="user-meta">
                        <div class="meta-item">
                            <span class="meta-label">Username</span>
                            <span class="meta-value"><?php echo htmlspecialchars($user['username']); ?></span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Email</span>
                            <span class="meta-value"><?php echo htmlspecialchars($user['email']); ?></span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Joined</span>
                            <span class="meta-value"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card blue">
                    <div class="stat-icon">📝</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $stats['quiz_count']; ?></div>
                        <div class="stat-label">Quizzes Completed</div>
                    </div>
                </div>

                <div class="stat-card green">
                    <div class="stat-icon">📚</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $stats['words_learned']; ?></div>
                        <div class="stat-label">Words Learned</div>
                    </div>
                </div>

                <div class="stat-card orange">
                    <div class="stat-icon">🔥</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $stats['current_streak']; ?></div>
                        <div class="stat-label">Current Streak</div>
                    </div>
                </div>

                <div class="stat-card purple">
                    <div class="stat-icon">⭐</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $stats['total_points']; ?></div>
                        <div class="stat-label">Total Points</div>
                    </div>
                </div>
            </div>

            <!-- Details Grid -->
            <div class="detail-grid">
                <!-- Learning Stats -->
                <div class="info-box">
                    <h3>📊 Learning Statistics</h3>
                    <div class="info-item">
                        <span class="info-label">Average Quiz Score</span>
                        <span class="info-value"><?php echo $stats['avg_score']; ?>%</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Longest Streak</span>
                        <span class="info-value"><?php echo $stats['longest_streak']; ?> days</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Total Active Days</span>
                        <span class="info-value"><?php echo $stats['total_days']; ?> days</span>
                    </div>
                </div>

                <!-- Account Info -->
                <div class="info-box">
                    <h3>👤 Account Information</h3>
                    <div class="info-item">
                        <span class="info-label">User ID</span>
                        <span class="info-value">#<?php echo $user['user_id']; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Registration Date</span>
                        <span class="info-value"><?php echo date('F d, Y g:i A', strtotime($user['created_at'])); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Last Updated</span>
                        <span class="info-value"><?php echo isset($user['updated_at']) ? date('F d, Y', strtotime($user['updated_at'])) : 'N/A'; ?></span>
                    </div>
                </div>
            </div>

            <!-- Recent Quizzes -->
            <div class="admin-card">
                <div class="card-header">
                    <h2>📝 Recent Quiz Results</h2>
                </div>
                <?php if ($recent_quizzes->num_rows > 0): ?>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Theme</th>
                                    <th>Difficulty</th>
                                    <th>Score</th>
                                    <th>Percentage</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($quiz = $recent_quizzes->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($quiz['theme_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo ucfirst($quiz['difficulty']); ?></td>
                                    <td><?php echo $quiz['score']; ?>/<?php echo $quiz['total_questions']; ?></td>
                                    <td>
                                        <strong style="color: <?php echo $quiz['percentage'] >= 80 ? '#4CAF50' : ($quiz['percentage'] >= 60 ? '#FF9800' : '#f44336'); ?>">
                                            <?php echo round($quiz['percentage']); ?>%
                                        </strong>
                                    </td>
                                    <td><?php echo date('M d, Y g:i A', strtotime($quiz['completed_at'])); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">📝</div>
                        <p>No quiz results yet</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recent Activity -->
            <div class="admin-card" style="margin-top: 25px;">
                <div class="card-header">
                    <h2>🕒 Recent Activity</h2>
                </div>
                <?php if ($recent_activity->num_rows > 0): ?>
                    <div class="activity-list">
                        <?php 
                        $activity_icons = [
                            'quiz' => '📝',
                            'vocab_learned' => '📚',
                            'vocab_page_visit' => '👀',
                            'general' => '✨'
                        ];
                        
                        $activity_labels = [
                            'quiz' => 'Completed Quiz',
                            'vocab_learned' => 'Learned Vocabulary',
                            'vocab_page_visit' => 'Visited Vocab Page',
                            'general' => 'Activity'
                        ];
                        
                        while($activity = $recent_activity->fetch_assoc()): 
                            $icon = $activity_icons[$activity['activity_type']] ?? '•';
                            $label = $activity_labels[$activity['activity_type']] ?? 'Activity';
                        ?>
                        <div class="activity-item">
                            <div class="activity-icon"><?php echo $icon; ?></div>
                            <div class="activity-content">
                                <div class="activity-text"><?php echo $label; ?></div>
                                <div class="activity-time"><?php echo date('M d, Y g:i A', strtotime($activity['created_at'])); ?></div>
                            </div>
                            <div class="activity-points">+<?php echo $activity['points_earned']; ?> pts</div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">🕒</div>
                        <p>No recent activity</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>