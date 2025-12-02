<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);
$vocab_ids = isset($data['vocab_ids']) ? $data['vocab_ids'] : [];
$user_id = $_SESSION['user_id'];

if (empty($vocab_ids)) {
    echo json_encode(['success' => false, 'message' => 'No vocabulary IDs provided']);
    exit();
}

$success_count = 0;
$today = date('Y-m-d');

// Mark each word as learned
foreach ($vocab_ids as $vocab_id) {
    $vocab_id = intval($vocab_id);
    
    // Check if progress already exists
    $check_stmt = $conn->prepare("SELECT progress_id FROM user_progress WHERE user_id = ? AND vocab_id = ?");
    $check_stmt->bind_param("ii", $user_id, $vocab_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing progress
        $update_stmt = $conn->prepare("
            UPDATE user_progress 
            SET is_learned = 1, 
                times_reviewed = times_reviewed + 1, 
                last_reviewed = NOW(),
                mastery_level = LEAST(mastery_level + 10, 100)
            WHERE user_id = ? AND vocab_id = ?
        ");
        $update_stmt->bind_param("ii", $user_id, $vocab_id);
        if ($update_stmt->execute()) {
            $success_count++;
        }
    } else {
        // Insert new progress
        $insert_stmt = $conn->prepare("
            INSERT INTO user_progress (user_id, vocab_id, is_learned, times_reviewed, last_reviewed, mastery_level) 
            VALUES (?, ?, 1, 1, NOW(), 10)
        ");
        $insert_stmt->bind_param("ii", $user_id, $vocab_id);
        if ($insert_stmt->execute()) {
            $success_count++;
        }
    }
}

// Update learning streak
$streak_stmt = $conn->prepare("SELECT streak_id, last_activity_date, current_streak FROM learning_streak WHERE user_id = ?");
$streak_stmt->bind_param("i", $user_id);
$streak_stmt->execute();
$streak_result = $streak_stmt->get_result();

if ($streak_result->num_rows > 0) {
    $streak = $streak_result->fetch_assoc();
    $last_activity = $streak['last_activity_date'];
    
    if ($last_activity == $today) {
        // Already updated today, do nothing
    } elseif ($last_activity == date('Y-m-d', strtotime('-1 day'))) {
        // Consecutive day - increase streak
        $new_streak = $streak['current_streak'] + 1;
        $update_streak = $conn->prepare("
            UPDATE learning_streak 
            SET current_streak = ?, 
                longest_streak = GREATEST(longest_streak, ?), 
                last_activity_date = ? 
            WHERE user_id = ?
        ");
        $update_streak->bind_param("iisi", $new_streak, $new_streak, $today, $user_id);
        $update_streak->execute();
    } else {
        // Streak broken - reset to 1
        $update_streak = $conn->prepare("UPDATE learning_streak SET current_streak = 1, last_activity_date = ? WHERE user_id = ?");
        $update_streak->bind_param("si", $today, $user_id);
        $update_streak->execute();
    }
} else {
    // Create new streak record
    $insert_streak = $conn->prepare("INSERT INTO learning_streak (user_id, current_streak, longest_streak, last_activity_date) VALUES (?, 1, 1, ?)");
    $insert_streak->bind_param("is", $user_id, $today);
    $insert_streak->execute();
}

// Get updated progress stats
$stats_stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_learned,
        SUM(mastery_level) / COUNT(*) as avg_mastery
    FROM user_progress 
    WHERE user_id = ? AND is_learned = 1
");
$stats_stmt->bind_param("i", $user_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

echo json_encode([
    'success' => true,
    'message' => "$success_count words marked as learned",
    'words_learned' => $success_count,
    'total_learned' => $stats['total_learned'],
    'avg_mastery' => round($stats['avg_mastery'], 1)
]);
?>