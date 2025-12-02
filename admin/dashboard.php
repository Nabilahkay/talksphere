<?php
session_start();
require_once '../config/database.php';
require_once 'config/admin_auth.php';

checkAdminLogin();
$admin = getCurrentAdmin();

// Get statistics
$stats = [];

// Total users
$result = $conn->query("SELECT COUNT(*) as count FROM users");
$stats['total_users'] = $result->fetch_assoc()['count'];

// Total vocabulary words
$result = $conn->query("SELECT COUNT(*) as count FROM vocabulary");
$stats['total_vocabulary'] = $result->fetch_assoc()['count'];

// Total quizzes completed
$result = $conn->query("SELECT COUNT(*) as count FROM quiz_results");
$stats['total_quizzes'] = $result->fetch_assoc()['count'];

// Active users (logged in last 7 days)
$result = $conn->query("SELECT COUNT(DISTINCT user_id) as count FROM user_activity WHERE activity_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
$stats['active_users'] = $result->fetch_assoc()['count'];

// Recent users (last 10)
$recent_users = $conn->query("SELECT user_id, username, full_name, email, created_at FROM users ORDER BY created_at DESC LIMIT 10");

// Recent activity
$recent_activity = $conn->query("SELECT * FROM admin_activity_log ORDER BY created_at DESC LIMIT 10");

// User growth (last 7 days)
$user_growth = $conn->query("SELECT DATE(created_at) as date, COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY date ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - TalkSphere</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="admin-sidebar">
        <div class="admin-logo">
            <span class="logo-icon">🔧</span>
            <span class="logo-text">Admin Panel</span>
        </div>
        <nav class="admin-nav">
            <a href="dashboard.php" class="active"><span>📊</span> Dashboard</a>
            <a href="users.php"><span>👥</span> Users</a>
            <a href="vocabulary.php"><span>📚</span> Vocabulary</a>
            <a href="themes.php"><span>🎨</span> Themes</a>
            <a href="quizzes.php"><span>📝</span> Quizzes</a>
            <a href="activity.php"><span>📈</span> Activity</a>
            <a href="manage-mywebar.php"><span>🎯</span> AR Marker </a>
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
            <h1>Dashboard Overview</h1>
            <div class="header-actions">
                <button class="btn-refresh" onclick="location.reload()">🔄 Refresh</button>
                <a href="../dashboard.php" class="btn-view-site" target="_blank">🌐 View Site</a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card blue">
                <div class="stat-icon">👥</div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $stats['total_users']; ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
            </div>

            <div class="stat-card green">
                <div class="stat-icon">📚</div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $stats['total_vocabulary']; ?></div>
                    <div class="stat-label">Vocabulary Words</div>
                </div>
            </div>

            <div class="stat-card purple">
                <div class="stat-icon">📝</div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $stats['total_quizzes']; ?></div>
                    <div class="stat-label">Quizzes Completed</div>
                </div>
            </div>

            <div class="stat-card orange">
                <div class="stat-icon">⚡</div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $stats['active_users']; ?></div>
                    <div class="stat-label">Active Users (7d)</div>
                </div>
            </div>
        </div>

        <!-- Main Grid -->
        <div class="dashboard-grid">
            <!-- Recent Users -->
            <div class="admin-card">
                <div class="card-header">
                    <h2>Recent Users</h2>
                    <a href="users.php" class="view-all">View All →</a>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($user = $recent_users->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $user['user_id']; ?></td>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <a href="users.php?id=<?php echo $user['user_id']; ?>" class="btn-small">View</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="admin-card">
                <div class="card-header">
                    <h2>Recent Admin Activity</h2>
                    <a href="activity.php" class="view-all">View All →</a>
                </div>
                <div class="activity-list">
                    <?php while($activity = $recent_activity->fetch_assoc()): ?>
                    <div class="activity-item">
                        <div class="activity-icon">
                            <?php 
                            $icons = [
                                'login' => '🔓',
                                'create' => '➕',
                                'update' => '✏️',
                                'delete' => '🗑️'
                            ];
                            echo $icons[$activity['action_type']] ?? '📌';
                            ?>
                        </div>
                        <div class="activity-content">
                            <div class="activity-text"><?php echo htmlspecialchars($activity['description'] ?? $activity['action_type']); ?></div>
                            <div class="activity-time"><?php echo date('M d, g:i A', strtotime($activity['created_at'])); ?></div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh stats every 30 seconds
        setInterval(() => {
            location.reload();
        }, 30000);
    </script>
</body>
</html>