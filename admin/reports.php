<?php
session_start();
require_once '../config/database.php';
require_once 'config/admin_auth.php';

checkAdminLogin();
$admin = getCurrentAdmin();

// Date range filters
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01'); // First day of current month
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d'); // Today
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'overview';

// Build date filter
$date_filter = "";
if ($date_from && $date_to) {
    $date_filter = "WHERE DATE(created_at) BETWEEN '$date_from' AND '$date_to'";
}

// OVERVIEW REPORT
if ($report_type === 'overview') {
    // User statistics
    $total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
    $new_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE DATE(created_at) BETWEEN '$date_from' AND '$date_to'")->fetch_assoc()['count'];
    $active_users = $conn->query("SELECT COUNT(DISTINCT user_id) as count FROM user_activity WHERE DATE(activity_date) BETWEEN '$date_from' AND '$date_to'")->fetch_assoc()['count'];
    
    // Vocabulary statistics
    $total_vocab = $conn->query("SELECT COUNT(*) as count FROM vocabulary")->fetch_assoc()['count'];
    $words_learned = $conn->query("SELECT COUNT(*) as count FROM user_progress WHERE DATE(created_at) BETWEEN '$date_from' AND '$date_to'")->fetch_assoc()['count'];
    
    // Quiz statistics
    $total_quizzes = $conn->query("SELECT COUNT(*) as count FROM quiz_results WHERE DATE(completed_at) BETWEEN '$date_from' AND '$date_to'")->fetch_assoc()['count'];
    $avg_score_result = $conn->query("SELECT AVG(percentage) as avg FROM quiz_results WHERE DATE(completed_at) BETWEEN '$date_from' AND '$date_to'")->fetch_assoc();
    $avg_score = isset($avg_score_result['avg']) && $avg_score_result['avg'] !== null ? round($avg_score_result['avg'], 2) : 0;
    
    // Engagement metrics
    $total_points = $conn->query("SELECT SUM(points_earned) as total FROM user_activity WHERE DATE(activity_date) BETWEEN '$date_from' AND '$date_to'")->fetch_assoc()['total'] ?? 0;
    $avg_streak = $conn->query("SELECT AVG(current_streak) as avg FROM user_streaks")->fetch_assoc()['avg'] ?? 0;
}

// USER ACTIVITY REPORT
if ($report_type === 'users') {
    $user_activity = $conn->query("
        SELECT 
            u.user_id,
            u.full_name,
            u.username,
            u.email,
            COUNT(DISTINCT ua.activity_date) as active_days,
            SUM(ua.points_earned) as total_points,
            (SELECT current_streak FROM user_streaks WHERE user_id = u.user_id) as current_streak,
            (SELECT COUNT(*) FROM quiz_results WHERE user_id = u.user_id) as quiz_count,
            (SELECT COUNT(*) FROM user_progress WHERE user_id = u.user_id) as words_learned,
            u.created_at
        FROM users u
        LEFT JOIN user_activity ua ON u.user_id = ua.user_id AND DATE(ua.activity_date) BETWEEN '$date_from' AND '$date_to'
        GROUP BY u.user_id
        ORDER BY total_points DESC
        LIMIT 50
    ");
}

// VOCABULARY REPORT
if ($report_type === 'vocabulary') {
    $vocab_stats = $conn->query("
        SELECT 
            t.theme_name,
            COUNT(v.vocab_id) as word_count,
            (SELECT COUNT(*) FROM user_progress up WHERE up.vocab_id = v.vocab_id) as times_learned,
            (SELECT COUNT(*) FROM quiz_results qr WHERE qr.theme_id = t.theme_id) as quiz_attempts,
            (SELECT AVG(percentage) FROM quiz_results qr WHERE qr.theme_id = t.theme_id) as avg_score
        FROM themes t
        LEFT JOIN vocabulary v ON t.theme_id = v.theme_id
        GROUP BY t.theme_id
        ORDER BY word_count DESC
    ");
    
    // Most learned words
    $most_learned = $conn->query("
        SELECT 
            v.chinese_character,
            v.pinyin,
            v.english_meaning,
            t.theme_name,
            COUNT(up.progress_id) as learn_count
        FROM vocabulary v
        LEFT JOIN themes t ON v.theme_id = t.theme_id
        LEFT JOIN user_progress up ON v.vocab_id = up.vocab_id AND DATE(up.created_at) BETWEEN '$date_from' AND '$date_to'
        GROUP BY v.vocab_id
        HAVING learn_count > 0
        ORDER BY learn_count DESC
        LIMIT 10
    ");
}

// QUIZ PERFORMANCE REPORT
if ($report_type === 'quizzes') {
    $quiz_performance = $conn->query("
        SELECT 
            t.theme_name,
            qr.difficulty,
            COUNT(*) as attempts,
            AVG(qr.percentage) as avg_score,
            MAX(qr.percentage) as highest_score,
            MIN(qr.percentage) as lowest_score,
            SUM(CASE WHEN qr.percentage >= 70 THEN 1 ELSE 0 END) as passed,
            AVG(qr.time_taken) as avg_time
        FROM quiz_results qr
        LEFT JOIN themes t ON qr.theme_id = t.theme_id
        WHERE DATE(qr.completed_at) BETWEEN '$date_from' AND '$date_to'
        GROUP BY qr.theme_id, qr.difficulty
        ORDER BY attempts DESC
    ");
    
    // Top performers
    $top_performers = $conn->query("
        SELECT 
            u.full_name,
            COUNT(*) as quiz_count,
            AVG(qr.percentage) as avg_score,
            MAX(qr.percentage) as best_score,
            SUM(CASE WHEN qr.percentage >= 90 THEN 1 ELSE 0 END) as excellent_count
        FROM quiz_results qr
        JOIN users u ON qr.user_id = u.user_id
        WHERE DATE(qr.completed_at) BETWEEN '$date_from' AND '$date_to'
        GROUP BY qr.user_id
        ORDER BY avg_score DESC
        LIMIT 10
    ");
}

// ENGAGEMENT REPORT
if ($report_type === 'engagement') {
    // Daily activity trend
    $daily_activity = $conn->query("
        SELECT 
            DATE(activity_date) as date,
            COUNT(DISTINCT user_id) as active_users,
            SUM(points_earned) as total_points,
            COUNT(*) as total_activities
        FROM user_activity
        WHERE DATE(activity_date) BETWEEN '$date_from' AND '$date_to'
        GROUP BY DATE(activity_date)
        ORDER BY date ASC
    ");
    
    // Streak distribution
    $streak_distribution = $conn->query("
        SELECT 
            CASE 
                WHEN current_streak = 0 THEN '0 days'
                WHEN current_streak BETWEEN 1 AND 3 THEN '1-3 days'
                WHEN current_streak BETWEEN 4 AND 7 THEN '4-7 days'
                WHEN current_streak BETWEEN 8 AND 14 THEN '8-14 days'
                WHEN current_streak BETWEEN 15 AND 30 THEN '15-30 days'
                ELSE '30+ days'
            END as streak_range,
            COUNT(*) as user_count
        FROM user_streaks
        GROUP BY streak_range
        ORDER BY 
            CASE streak_range
                WHEN '0 days' THEN 1
                WHEN '1-3 days' THEN 2
                WHEN '4-7 days' THEN 3
                WHEN '8-14 days' THEN 4
                WHEN '15-30 days' THEN 5
                ELSE 6
            END
    ");
}

// GROWTH REPORT
if ($report_type === 'growth') {
    // User growth over time
    $user_growth = $conn->query("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as new_users
        FROM users
        WHERE DATE(created_at) BETWEEN '$date_from' AND '$date_to'
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    
    // Content growth
    $content_growth = $conn->query("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as new_words
        FROM vocabulary
        WHERE DATE(created_at) BETWEEN '$date_from' AND '$date_to'
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin Panel</title>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .report-header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .report-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .tab-btn {
            padding: 12px 25px;
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            color: #666;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .tab-btn:hover {
            background: #e9ecef;
            border-color: #8B0000;
            color: #8B0000;
        }

        .tab-btn.active {
            background: linear-gradient(135deg, #8B0000 0%, #A52A2A 100%);
            border-color: #8B0000;
            color: white;
        }

        .date-filters {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .filter-label {
            font-size: 13px;
            font-weight: 600;
            color: #333;
        }

        .filter-input {
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
        }

        .filter-input:focus {
            outline: none;
            border-color: #8B0000;
        }

        .report-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0