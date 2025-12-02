<?php
session_start();
require_once '../config/database.php';
require_once 'config/admin_auth.php';

checkAdminLogin();
$admin = getCurrentAdmin();

// Handle user actions
$success_message = '';
$error_message = '';

// Delete user
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    
    // Don't allow deleting yourself
    if ($user_id == $_SESSION['admin_id']) {
        $error_message = 'You cannot delete your own account!';
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            $success_message = 'User deleted successfully!';
            logAdminActivity('delete', 'user', $user_id, 'Deleted user ID: ' . $user_id);
        } else {
            $error_message = 'Failed to delete user.';
        }
    }
}

// Toggle user status (activate/deactivate)
if (isset($_GET['action']) && $_GET['action'] === 'toggle_status' && isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    
    // Check if user has is_active column, if not we'll skip this
    $check_column = $conn->query("SHOW COLUMNS FROM users LIKE 'is_active'");
    
    if ($check_column->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE users SET is_active = NOT is_active WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            $success_message = 'User status updated successfully!';
            logAdminActivity('update', 'user', $user_id, 'Toggled user status');
        }
    }
}

// Pagination
$per_page = 15;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_condition = '';
$search_params = [];

if ($search) {
    $search_condition = "WHERE username LIKE ? OR full_name LIKE ? OR email LIKE ?";
    $search_term = "%$search%";
    $search_params = [$search_term, $search_term, $search_term];
}

// Get total users count
$count_query = "SELECT COUNT(*) as total FROM users $search_condition";
if ($search_params) {
    $stmt = $conn->prepare($count_query);
    $stmt->bind_param("sss", ...$search_params);
    $stmt->execute();
    $total_users = $stmt->get_result()->fetch_assoc()['total'];
} else {
    $total_users = $conn->query($count_query)->fetch_assoc()['total'];
}

$total_pages = ceil($total_users / $per_page);

// Get users with pagination
$users_query = "SELECT u.*, 
                (SELECT COUNT(*) FROM quiz_results WHERE user_id = u.user_id) as quiz_count,
                (SELECT current_streak FROM user_streaks WHERE user_id = u.user_id) as current_streak
                FROM users u 
                $search_condition
                ORDER BY u.created_at DESC 
                LIMIT ? OFFSET ?";

$stmt = $conn->prepare($users_query);
if ($search_params) {
    $stmt->bind_param("ssii", ...[...$search_params, $per_page, $offset]);
} else {
    $stmt->bind_param("ii", $per_page, $offset);
}
$stmt->execute();
$users = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin Panel</title>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .search-bar {
            background: white;
            padding: 20px 40px;
            display: flex;
            gap: 15px;
            align-items: center;
            border-bottom: 1px solid #e0e0e0;
        }

        .search-input {
            flex: 1;
            padding: 12px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: #8B0000;
        }

        .btn-search {
            padding: 12px 30px;
            background: #8B0000;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-search:hover {
            background: #A52A2A;
            transform: translateY(-2px);
        }

        .btn-clear {
            padding: 12px 25px;
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

        .content-wrapper {
            padding: 40px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
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

        .users-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .users-header h2 {
            font-size: 24px;
            color: #333;
        }

        .users-stats {
            display: flex;
            gap: 30px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #8B0000;
        }

        .stat-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            margin-top: 5px;
        }

        .badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-active {
            background: #d4edda;
            color: #155724;
        }

        .badge-inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .actions-cell {
            display: flex;
            gap: 8px;
        }

        .btn-action {
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-view {
            background: #2196F3;
            color: white;
        }

        .btn-view:hover {
            background: #1976D2;
        }

        .btn-edit {
            background: #FF9800;
            color: white;
        }

        .btn-edit:hover {
            background: #F57C00;
        }

        .btn-delete {
            background: #f44336;
            color: white;
        }

        .btn-delete:hover {
            background: #d32f2f;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
        }

        .page-link {
            padding: 8px 15px;
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            color: #333;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .page-link:hover {
            background: #f0f0f0;
        }

        .page-link.active {
            background: #8B0000;
            color: white;
            border-color: #8B0000;
        }

        .page-link.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 15px;
        }

        .empty-icon {
            font-size: 80px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 20px;
            color: #666;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #999;
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
            <a href="users.php" class="active"><span>👥</span> Users</a>
            <a href="vocabulary.php"><span>📚</span> Vocabulary</a>
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
            <h1>User Management</h1>
            <div class="header-actions">
                <a href="dashboard.php" class="btn-view-site">← Back to Dashboard</a>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="search-bar">
            <form method="GET" style="display: flex; gap: 15px; width: 100%;">
                <input 
                    type="text" 
                    name="search" 
                    class="search-input" 
                    placeholder="🔍 Search by username, name, or email..."
                    value="<?php echo htmlspecialchars($search); ?>"
                >
                <button type="submit" class="btn-search">Search</button>
                <?php if ($search): ?>
                    <a href="users.php" class="btn-clear">Clear</a>
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

            <div class="users-header">
                <h2>All Users <?php if ($search): ?>(Search: "<?php echo htmlspecialchars($search); ?>"<?php endif; ?></h2>
                <div class="users-stats">
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $total_users; ?></div>
                        <div class="stat-label">Total Users</div>
                    </div>
                </div>
            </div>

            <?php if ($users->num_rows > 0): ?>
                <div class="admin-card">
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th>Quizzes</th>
                                    <th>Streak</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($user = $users->fetch_assoc()): ?>
                                <tr>
                                    <td><strong>#<?php echo $user['user_id']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo $user['quiz_count'] ?? 0; ?></td>
                                    <td>
                                        <?php if ($user['current_streak'] > 0): ?>
                                            <span style="color: #FF6B6B;">🔥 <?php echo $user['current_streak']; ?></span>
                                        <?php else: ?>
                                            <span style="color: #999;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <div class="actions-cell">
                                            <a href="user-detail.php?id=<?php echo $user['user_id']; ?>" class="btn-action btn-view">View Details</a>
                                            <a href="user-edit.php?id=<?php echo $user['user_id']; ?>" class="btn-action btn-edit">Edit</a>
                                            <a href="users.php?action=delete&id=<?php echo $user['user_id']; ?>" 
                                               class="btn-action btn-delete"
                                               onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone!');">
                                                Delete
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="page-link">← Previous</a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                                   class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                <span class="page-link disabled">...</span>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="page-link">Next →</a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">👥</div>
                    <h3>No Users Found</h3>
                    <?php if ($search): ?>
                        <p>No users match your search criteria.</p>
                        <a href="users.php" class="btn-clear" style="margin-top: 20px;">Clear Search</a>
                    <?php else: ?>
                        <p>There are no users in the system yet.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Auto-hide alerts after 3 seconds
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