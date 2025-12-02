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
$vocab_id = isset($data['vocab_id']) ? intval($data['vocab_id']) : 0;
$user_id = $_SESSION['user_id'];

if ($vocab_id == 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid vocabulary ID']);
    exit();
}

// Check if progress exists
$check_stmt = $conn->prepare("SELECT progress_id, times_reviewed FROM user_progress WHERE user_id = ? AND vocab_id = ?");
$check_stmt->bind_param("ii", $user_id, $vocab_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows > 0) {
    // Update review count
    $update_stmt = $conn->prepare("
        UPDATE user_progress 
        SET times_reviewed = times_reviewed + 1, 
            last_reviewed = NOW() 
        WHERE user_id = ? AND vocab_id = ?
    ");
    $update_stmt->bind_param("ii", $user_id, $vocab_id);
    $success = $update_stmt->execute();
} else {
    // Insert new tracking entry
    $insert_stmt = $conn->prepare("
        INSERT INTO user_progress (user_id, vocab_id, is_learned, times_reviewed, last_reviewed, mastery_level) 
        VALUES (?, ?, 0, 1, NOW(), 0)
    ");
    $insert_stmt->bind_param("ii", $user_id, $vocab_id);
    $success = $insert_stmt->execute();
}

echo json_encode([
    'success' => $success,
    'message' => $success ? 'Card tracked successfully' : 'Failed to track card'
]);
?>