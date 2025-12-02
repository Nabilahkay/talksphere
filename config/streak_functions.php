<?php
require_once 'database.php';

/**
 * Log user activity and update streak
 */
function logUserActivity($user_id, $activity_type = 'general', $points = 10) {
    global $conn;
    
    $today = date('Y-m-d');
    
    // Insert or update today's activity
    $stmt = $conn->prepare("
        INSERT INTO user_activity (user_id, activity_date, activity_type, points_earned)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE points_earned = points_earned + ?
    ");
    $stmt->bind_param("issii", $user_id, $today, $activity_type, $points, $points);
    $stmt->execute();
    
    // Update streak
    updateUserStreak($user_id);
}

/**
 * Calculate and update user's learning streak
 */
function updateUserStreak($user_id) {
    global $conn;
    
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    
    // Get current streak data
    $stmt = $conn->prepare("SELECT current_streak, longest_streak, last_activity_date, total_days_active FROM user_streaks WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $streak_data = $result->fetch_assoc();
    
    if (!$streak_data) {
        // First time - create streak record
        $stmt = $conn->prepare("INSERT INTO user_streaks (user_id, current_streak, longest_streak, last_activity_date, total_days_active) VALUES (?, 1, 1, ?, 1)");
        $stmt->bind_param("is", $user_id, $today);
        $stmt->execute();
        return;
    }
    
    $last_date = $streak_data['last_activity_date'];
    $current_streak = $streak_data['current_streak'];
    $longest_streak = $streak_data['longest_streak'];
    $total_days = $streak_data['total_days_active'];
    
    // Check if today's activity already logged
    if ($last_date === $today) {
        return; // Already counted today
    }
    
    // Calculate new streak
    if ($last_date === $yesterday) {
        // Consecutive day - increase streak
        $current_streak++;
    } elseif ($last_date < $yesterday) {
        // Streak broken - reset to 1
        $current_streak = 1;
    }
    
    // Update longest streak if current is higher
    if ($current_streak > $longest_streak) {
        $longest_streak = $current_streak;
    }
    
    $total_days++;
    
    // Update database
    $stmt = $conn->prepare("
        UPDATE user_streaks 
        SET current_streak = ?, longest_streak = ?, last_activity_date = ?, total_days_active = ?
        WHERE user_id = ?
    ");
    $stmt->bind_param("iisii", $current_streak, $longest_streak, $today, $total_days, $user_id);
    $stmt->execute();
}

/**
 * Get user's streak information
 */
function getUserStreak($user_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM user_streaks WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $streak = $result->fetch_assoc();
    
    if (!$streak) {
        return [
            'current_streak' => 0,
            'longest_streak' => 0,
            'total_days_active' => 0,
            'last_activity_date' => null
        ];
    }
    
    // Check if streak is broken (no activity yesterday or today)
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    
    if ($streak['last_activity_date'] < $yesterday) {
        // Streak is broken - return 0 for current streak
        $streak['current_streak'] = 0;
    }
    
    return $streak;
}

/**
 * Get user's earned badges
 */
function getUserBadges($user_id) {
    global $conn;
    
    // Check if table exists
    $check = $conn->query("SHOW TABLES LIKE 'user_badges'");
    if ($check->num_rows == 0) {
        return []; // Table doesn't exist, return empty array
    }
    
    $stmt = $conn->prepare("SELECT * FROM user_badges WHERE user_id = ? ORDER BY earned_date DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $badges = [];
    while ($row = $result->fetch_assoc()) {
        $badges[] = $row;
    }
    
    return $badges;
}

/**
 * Get user's total points
 */
function getUserTotalPoints($user_id) {
    global $conn;
    
    // Check if table exists
    $check = $conn->query("SHOW TABLES LIKE 'user_activity'");
    if ($check->num_rows == 0) {
        return 0; // Table doesn't exist
    }
    
    $stmt = $conn->prepare("SELECT SUM(points_earned) as total_points FROM user_activity WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    return $data['total_points'] ?? 0;
}

/**
 * Check and award badges based on achievements
 */
function checkAndAwardBadges($user_id) {
    global $conn;
    
    // Check if table exists
    $check = $conn->query("SHOW TABLES LIKE 'user_badges'");
    if ($check->num_rows == 0) {
        return; // Table doesn't exist, skip
    }
    
    $streak = getUserStreak($user_id);
    $current_streak = $streak['current_streak'];
    
    $badges = [];
    
    // Define badge thresholds
    if ($current_streak >= 3) $badges[] = ['3_day_streak', '🔥 3 Day Streak'];
    if ($current_streak >= 7) $badges[] = ['7_day_streak', '⚡ 7 Day Streak'];
    if ($current_streak >= 14) $badges[] = ['14_day_streak', '💪 14 Day Streak'];
    if ($current_streak >= 30) $badges[] = ['30_day_streak', '🏆 30 Day Streak'];
    if ($current_streak >= 100) $badges[] = ['100_day_streak', '👑 100 Day Streak'];
    
    // Award badges
    foreach ($badges as $badge) {
        $stmt = $conn->prepare("
            INSERT IGNORE INTO user_badges (user_id, badge_type, badge_name, earned_date)
            VALUES (?, ?, ?, CURDATE())
        ");
        $stmt->bind_param("iss", $user_id, $badge[0], $badge[1]);
        $stmt->execute();
    }
}
?>