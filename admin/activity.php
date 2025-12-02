<?php
session_start();
require_once '../config/database.php';
require_once 'config/admin_auth.php';

checkAdminLogin();
$admin = getCurrentAdmin();

// Pagination
$per_page = 50;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

// Filters
$admin_filter = isset($_GET['admin']) ? intval($_GET['admin']) : 0;
$action_filter = isset($_GET['action']) ? $_GET['action'] : '';
$target_filter = isset($_GET['target']) ? $_GET['target'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build WHERE clause
$where_conditions = [];
$where_params = [];
$param_types = '';

if ($admin_filter > 0) {
    $where_conditions[] = "aal.admin_id = ?";
    $where_params[] = $admin_filter;
    $param_types .= 'i';
}

if ($action_filter) {
    $where_conditions[] = "aal.action_type = ?";
    $where_params[] = $action_filter;
    $param_types .= 's';
}

if ($target_filter) {
    $where_conditions[] = "aal.target_type = ?";
    $where_params[] = $target_filter;
    $param_types .= 's';
}

if ($date_from) {
    $where_conditions[] = "DATE(aal.created_at) >= ?";
    $where_params[] = $date_from;
    $param_types .= 's';
}

if ($date_to) {
    $where_conditions[] = "DATE(aal.created_at) <= ?";
    $where_params[] = $date_to;
    $param_types .= 's';
}

$where_clause = '';
if (count($where_conditions) > 0) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Get total count
$count_query = "SELECT COUNT(*) as total FROM admin_activity_log aal $where_clause";
if ($param_types) {
    $stmt = $conn->prepare($count_query);
    $stmt->bind_param($param_types, ...$where_params);
    $stmt->execute();
    $total_logs = $stmt->get_result()->fetch_assoc()['total'];
} else {
    $total_logs = $conn->query($count_query)->fetch_assoc()['total'];
}

$total_pages = ceil($total_logs / $per_page);

// Get activity logs with pagination
$log_query = "SELECT aal.*, au.username, au.full_name
              FROM admin_activity_log aal
              LEFT JOIN admin_users au ON aal.admin_id = au.admin_id
              $where_clause
              ORDER BY aal.created_at DESC
              LIMIT ? OFFSET ?";

$where_params[] = $per_page;
$where_params[] = $offset;
$param_types .= 'ii';

$stmt = $conn->prepare($log_query);
$stmt->bind_param($param_types, ...$where_params);
$stmt->execute();
$activity_logs = $stmt->get_result();

// Get statistics
$total_activities = $conn->query("SELECT COUNT(*) as count FROM admin_activity_log")->fetch_assoc()['count'];
$total_admins = $conn->query("SELECT COUNT(*) as count FROM admin_users WHERE is_active = 1")->fetch_assoc()['count'];
$today_activities = $conn->query("SELECT COUNT(*) as count FROM admin_activity_log WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['count'];
$this_week = $conn->query("SELECT COUNT(*) as count FROM admin_activity_log WHERE YEARWEEK(created_at) = YEARWEEK(NOW())")->fetch_assoc()['count'];

// Get admins for filter
$admins = $conn->query("SELECT admin_id, username, full_name FROM admin_users ORDER BY full_name");

// Get action types
$action_types = $conn->query("SELECT DISTINCT action_type FROM admin_activity_log ORDER BY action_type");

// Get target types
$target_types = $conn->query("SELECT DISTINCT target_type FROM admin_activity_log WHERE target_type IS NOT NULL ORDER BY target_type");

// Get activity by action type
$activity_by_action = $conn->query("
    SELECT action_type, COUNT(*) as count
    FROM admin_activity_log
    GROUP BY action_type
    ORDER BY count DESC
    LIMIT 5
");

// Get most active admins
$most_active_admins = $conn->query("
    SELECT au.full_name, COUNT(*) as actions
    FROM admin_activity_log aal
    LEFT JOIN admin_users au ON aal.admin_id = au.admin_id
    GROUP BY aal.admin_id
    ORDER BY actions DESC
    LIMIT 5
");

// Get recent logins
$recent_logins = $conn->query("
    SELECT au.full_name, au.username, aal.created_at, aal.ip_address
    FROM admin_activity_log aal
    LEFT JOIN admin_users au ON aal.admin_id = au.admin_id
    WHERE aal.action_type = 'login'
    ORDER BY aal.created_at DESC
    LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Log - Admin Panel</title>
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

        .activity-timeline {
            position: relative;
        }

        .timeline-item {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            position: relative;
            padding-left: 30px;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: 9px;
            top: 30px;
            bottom: -20px;
            width: 2px;
            background: #e0e0e0;
        }

        .timeline-item:last-child::before {
            display: none;
        }

        .timeline-icon {
            position: absolute;
            left: 0;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            z-index: 1;
        }

        .timeline-icon.login {
            background: #4CAF50;
            color: white;
        }

        .timeline-icon.create {
            background: #2196F3;
            color: white;
        }

        .timeline-icon.update {
            background: #FF9800;
            color: white;
        }

        .timeline-icon.delete {
            background: #f44336;
            color: white;
        }

        .timeline-icon.logout {
            background: #9E9E9E;
            color: white;
        }

        .timeline-content {
            flex: 1;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .timeline-content:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }

        .timeline-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 8px;
        }

        .timeline-admin {
            font-size: 14px;
            font-weight: 600;
            color: #333;
        }

        .timeline-time {
            font-size: 11px;
            color: #999;
        }

        .timeline-action {
            font-size: 13px;
            color: #666;
            margin-bottom: 5px;
        }

        .timeline-details {
            font-size: 12px;
            color: #999;
        }

        .action-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .action-login {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .action-logout {
            background: #f5f5f5;
            color: #616161;
        }

        .action-create {
            background: #e3f2fd;
            color: #1565c0;
        }

        .action-update {
            background: #fff3e0;
            color: #e65100;
        }

        .action-delete {
            background: #ffebee;
            color: #c62828;
        }

        .target-badge {
            display: inline-block;
            padding: 3px 8px;
            background: #f0f0f0;
            border-radius: 8px;
            font-size: 11px;
            color: #666;
            font-weight: 600;
        }

        .chart-item {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .chart-label {
            font-size: 13px;
            color: #666;
            min-width: 100px;
        }

        .chart-bar-container {
            flex: 1;
            height: 30px;
            background: #f0f0f0;
            border-radius: 15px;
            overflow: hidden;
            position: relative;
        }

        .chart-bar-fill {
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

        .chart-value {
            font-size: 14px;
            font-weight: 700;
            color: #8B0000;
            min-width: 40px;
            text-align: right;
        }

        .login-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }

        .login-item:hover {
            background: #e9ecef;
            transform: translateX(3px);
        }

        .login-user {
            flex: 1;
        }

        .login-name {
            font-size: 14px;
            font-weight: 600;
            color: #333;
        }

        .login-username {
            font-size: 12px;
            color: #999;
        }

        .login-time {
            font-size: 12px;
            color: #666;
        }

        .login-ip {
            font-size: 11px;
            color: #999;
            font-family: monospace;
        }

        .export-btn {
            padding: 10px 20px;
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

        .export-btn:hover {
            background: #45a049;
            transform: translateY(-2px);
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
            <a href="quizzes.php"><span>📝</span> Quizzes</a>
            <a href="activity.php" class="active"><span>📈</span> Activity</a>
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
            <h1>Activity Log</h1>
            <div class="header-actions">
                <button class="export-btn" onclick="exportToCSV()">📥 Export CSV</button>
                <a href="dashboard.php" class="btn-view-site">← Dashboard</a>
            </div>
        </div>

        <div class="content-wrapper">
            <!-- Overall Stats -->
            <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 30px;">
                <div class="stat-card blue">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $total_activities; ?></div>
                        <div class="stat-label">Total Activities</div>
                    </div>
                </div>

                <div class="stat-card green">
                    <div class="stat-icon">👥</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $total_admins; ?></div>
                        <div class="stat-label">Active Admins</div>
                    </div>
                </div>

                <div class="stat-card purple">
                    <div class="stat-icon">📅</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $today_activities; ?></div>
                        <div class="stat-label">Today's Activities</div>
                    </div>
                </div>

                <div class="stat-card orange">
                    <div class="stat-icon">📈</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $this_week; ?></div>
                        <div class="stat-label">This Week</div>
                    </div>
                </div>
            </div>

            <!-- Analytics Grid -->
            <div class="analytics-grid">
                <!-- Activity by Action Type -->
                <div class="analytics-card">
                    <h3 class="card-title">📊 Activity by Action Type</h3>
                    <?php if ($activity_by_action->num_rows > 0): ?>
                        <?php 
                        $max_count = 0;
                        $actions_data = [];
                        while($action = $activity_by_action->fetch_assoc()) {
                            $actions_data[] = $action;
                            if ($action['count'] > $max_count) {
                                $max_count = $action['count'];
                            }
                        }
                        
                        foreach($actions_data as $action):
                            $width = $max_count > 0 ? ($action['count'] / $max_count) * 100 : 0;
                        ?>
                        <div class="chart-item">
                            <div class="chart-label"><?php echo ucfirst($action['action_type']); ?></div>
                            <div class="chart-bar-container">
                                <div class="chart-bar-fill" style="width: <?php echo $width; ?>%">
                                    <?php echo $action['count']; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: #999; text-align: center;">No data available</p>
                    <?php endif; ?>
                </div>

                <!-- Most Active Admins -->
                <div class="analytics-card">
                    <h3 class="card-title">🏆 Most Active Admins</h3>
                    <?php if ($most_active_admins->num_rows > 0): ?>
                        <?php 
                        $max_actions = 0;
                        $admins_data = [];
                        while($admin_stat = $most_active_admins->fetch_assoc()) {
                            $admins_data[] = $admin_stat;
                            if ($admin_stat['actions'] > $max_actions) {
                                $max_actions = $admin_stat['actions'];
                            }
                        }
                        
                        foreach($admins_data as $admin_stat):
                            $width = $max_actions > 0 ? ($admin_stat['actions'] / $max_actions) * 100 : 0;
                        ?>
                        <div class="chart-item">
                            <div class="chart-label"><?php echo htmlspecialchars($admin_stat['full_name']); ?></div>
                            <div class="chart-bar-container">
                                <div class="chart-bar-fill" style="width: <?php echo $width; ?>%">
                                    <?php echo $admin_stat['actions']; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: #999; text-align: center;">No data available</p>
                    <?php endif; ?>
                </div>

                <!-- Recent Logins -->
                <div class="analytics-card" style="grid-column: 1 / -1;">
                    <h3 class="card-title">🔐 Recent Admin Logins</h3>
                    <?php if ($recent_logins->num_rows > 0): ?>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 10px;">
                            <?php while($login = $recent_logins->fetch_assoc()): ?>
                            <div class="login-item">
                                <div class="login-user">
                                    <div class="login-name"><?php echo htmlspecialchars($login['full_name']); ?></div>
                                    <div class="login-username">@<?php echo htmlspecialchars($login['username']); ?></div>
                                </div>
                                <div style="text-align: right;">
                                    <div class="login-time"><?php echo date('M d, g:i A', strtotime($login['created_at'])); ?></div>
                                    <div class="login-ip"><?php echo htmlspecialchars($login['ip_address']); ?></div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p style="color: #999; text-align: center;">No login data available</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Filters Panel -->
            <div class="filters-panel">
                <h3 style="margin-bottom: 20px; color: #333;">🔍 Filter Activity Logs</h3>
                <form method="GET">
                    <div class="filters-grid">
                        <div class="filter-item">
                            <label class="filter-label">Admin</label>
                            <select name="admin" class="filter-select">
                                <option value="0">All Admins</option>
                                <?php 
                                $admins->data_seek(0);
                                while($adm = $admins->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $adm['admin_id']; ?>" 
                                            <?php echo $admin_filter == $adm['admin_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($adm['full_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="filter-item">
                            <label class="filter-label">Action Type</label>
                            <select name="action" class="filter-select">
                                <option value="">All Actions</option>
                                <?php 
                                $action_types->data_seek(0);
                                while($type = $action_types->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $type['action_type']; ?>" 
                                            <?php echo $action_filter == $type['action_type'] ? 'selected' : ''; ?>>
                                        <?php echo ucfirst($type['action_type']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="filter-item">
                            <label class="filter-label">Target Type</label>
                            <select name="target" class="filter-select">
                                <option value="">All Targets</option>
                                <?php 
                                $target_types->data_seek(0);
                                while($target = $target_types->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $target['target_type']; ?>" 
                                            <?php echo $target_filter == $target['target_type'] ? 'selected' : ''; ?>>
                                        <?php echo ucfirst($target['target_type']); ?>
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
                        <a href="activity.php" class="btn-clear">Clear All</a>
                    </div>
                </form>
            </div>

            <!-- Activity Timeline -->
            <?php if ($activity_logs->num_rows > 0): ?>
                <div class="admin-card">
                    <div class="card-header">
                        <h2>Activity Timeline (<?php echo $total_logs; ?>)</h2>
                    </div>
                    <div class="activity-timeline">
                        <?php while($log = $activity_logs->fetch_assoc()): 
                            $icon_class = $log['action_type'];
                            $icon_emoji = [
                                'login' => '🔓',
                                'logout' => '🚪',
                                'create' => '➕',
                                'update' => '✏️',
                                'delete' => '🗑️'
                            ];
                            $icon = $icon_emoji[$log['action_type']] ?? '📌';
                        ?>
                        <div class="timeline-item">
                            <div class="timeline-icon <?php echo $icon_class; ?>">
                                <?php echo $icon; ?>
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-header">
                                    <div>
                                        <div class="timeline-admin">
                                            <?php echo htmlspecialchars($log['full_name'] ?? 'Unknown Admin'); ?>
                                        </div>
                                        <div class="timeline-action">
                                            <span class="action-badge action-<?php echo $log['action_type']; ?>">
                                                <?php echo ucfirst($log['action_type']); ?>
                                            </span>
                                            <?php if ($log['target_type']): ?>
                                                <span class="target-badge">
                                                    <?php echo ucfirst($log['target_type']); ?>
                                                    <?php if ($log['target_id']): ?>
                                                        #<?php echo $log['target_id']; ?>
                                                    <?php endif; ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="timeline-time">
                                        <?php echo date('M d, Y g:i A', strtotime($log['created_at'])); ?>
                                    </div>
                                </div>
                                <?php if ($log['description']): ?>
                                    <div class="timeline-details">
                                        <?php echo htmlspecialchars($log['description']); ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($log['ip_address']): ?>
                                    <div class="timeline-details" style="margin-top: 5px;">
                                        IP: <?php echo htmlspecialchars($log['ip_address']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?><?php echo $admin_filter ? '&admin=' . $admin_filter : ''; ?><?php echo $action_filter ? '&action=' . $action_filter : ''; ?><?php echo $target_filter ? '&target=' . $target_filter : ''; ?><?php echo $date_from ? '&date_from=' . $date_from : ''; ?><?php echo $date_to ? '&date_to=' . $date_to : ''; ?>" class="page-link">← Previous</a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                <a href="?page=<?php echo $i; ?><?php echo $admin_filter ? '&admin=' . $admin_filter : ''; ?><?php echo $action_filter ? '&action=' . $action_filter : ''; ?><?php echo $target_filter ? '&target=' . $target_filter : ''; ?><?php echo $date_from ? '&date_from=' . $date_from : ''; ?><?php echo $date_to ? '&date_to=' . $date_to : ''; ?>" 
                                   class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                <span class="page-link disabled">...</span>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?><?php echo $admin_filter ? '&admin=' . $admin_filter : ''; ?><?php echo $action_filter ? '&action=' . $action_filter : ''; ?><?php echo $target_filter ? '&target=' . $target_filter : ''; ?><?php echo $date_from ? '&date_from=' . $date_from : ''; ?><?php echo $date_to ? '&date_to=' . $date_to : ''; ?>" class="page-link">Next →</a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">📈</div>
                    <h3>No Activity Logs Found</h3>
                    <p>No activities have been logged yet or no results match your filters.</p>
                    <?php if ($admin_filter || $action_filter || $target_filter || $date_from || $date_to): ?>
                        <a href="activity.php" class="btn-clear" style="margin-top: 20px;">Clear Filters</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Animate progress bars on load
        document.addEventListener('DOMContentLoaded', function() {
            const progressBars = document.querySelectorAll('.chart-bar-fill');
            progressBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0';
                setTimeout(() => {
                    bar.style.width = width;
                }, 100);
            });

            // Animate timeline items
            const timelineItems = document.querySelectorAll('.timeline-item');
            timelineItems.forEach((item, index) => {
                item.style.opacity = '0';
                item.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    item.style.transition = 'all 0.5s ease';
                    item.style.opacity = '1';
                    item.style.transform = 'translateY(0)';
                }, index * 50);
            });
        });

        // Export to CSV
        function exportToCSV() {
            // Get all activity data from the page
            const rows = document.querySelectorAll('.timeline-item');
            let csv = 'Timestamp,Admin,Action,Target,Description,IP Address\n';

            rows.forEach(row => {
                const time = row.querySelector('.timeline-time').textContent.trim();
                const admin = row.querySelector('.timeline-admin').textContent.trim();
                const action = row.querySelector('.action-badge').textContent.trim();
                const target = row.querySelector('.target-badge')?.textContent.trim() || 'N/A';
                const description = row.querySelectorAll('.timeline-details')[0]?.textContent.trim() || 'N/A';
                const ip = row.querySelectorAll('.timeline-details')[1]?.textContent.replace('IP: ', '').trim() || 'N/A';

                csv += `"${time}","${admin}","${action}","${target}","${description}","${ip}"\n`;
            });

            // Create download
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `activity_log_${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);

            // Show success message
            alert('✅ Activity log exported successfully!');
        }

        // Auto-refresh every 30 seconds (optional)
        // Uncomment if you want live updates
        // setInterval(() => {
        //     location.reload();
        // }, 30000);
    </script>
</body>
</html>