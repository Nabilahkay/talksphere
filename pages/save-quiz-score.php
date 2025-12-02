<?php
require_once '../config/session.php';
checkLogin();
require_once '../config/database.php';
require_once '../config/streak_functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Set JSON header
header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $user_id = $_SESSION['user_id'];

    // Get JSON data from request
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);
    
    if (!$data || json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data');
    }

    // Validate required fields
    $required = ['theme_id', 'difficulty', 'score', 'total_questions', 'percentage'];
    foreach ($required as $field) {
        if (!isset($data[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    $theme_id = intval($data['theme_id']);
    $difficulty = $data['difficulty'];
    $score = intval($data['score']);
    $total_questions = intval($data['total_questions']);
    $percentage = floatval($data['percentage']);
    $time_taken = isset($data['time_taken']) ? intval($data['time_taken']) : 0;
    $best_streak = isset($data['best_streak']) ? intval($data['best_streak']) : 0;
    
    // Validate difficulty
    $valid_difficulties = ['beginner', 'intermediate', 'advanced'];
    if (!in_array($difficulty, $valid_difficulties)) {
        throw new Exception("Invalid difficulty level. Must be: beginner, intermediate, or advanced");
    }

    // Validate score ranges
    if ($score < 0 || $score > $total_questions) {
        throw new Exception("Invalid score value");
    }
    
    if ($percentage < 0 || $percentage > 100) {
        throw new Exception("Invalid percentage value");
    }
    
    // Begin transaction
    $conn->begin_transaction();

    // Insert quiz score into database
    $stmt = $conn->prepare("
        INSERT INTO quiz_results 
        (user_id, theme_id, difficulty, score, total_questions, percentage, time_taken, best_streak, completed_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->bind_param(
        "iisiidii",
        $user_id,
        $theme_id,
        $difficulty,
        $score,
        $total_questions,
        $percentage,
        $time_taken,
        $best_streak
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to save quiz score: " . $stmt->error);
    }
    
    $score_id = $conn->insert_id;
    $stmt->close();
    
    // Save individual answers if provided
    if (isset($data['answers']) && is_array($data['answers']) && count($data['answers']) > 0) {
        $stmt_answer = $conn->prepare("
            INSERT INTO quiz_answers 
            (score_id, question_id, user_answer, correct_answer, is_correct) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        foreach ($data['answers'] as $answer) {
            $question_id = intval($answer['question_id']);
            $user_answer = $answer['user_answer'] ?? '';
            $correct_answer = $answer['correct_answer'] ?? '';
            $is_correct = isset($answer['is_correct']) && $answer['is_correct'] ? 1 : 0;
            
            $stmt_answer->bind_param(
                "iissi",
                $score_id,
                $question_id,
                $user_answer,
                $correct_answer,
                $is_correct
            );
            
            if (!$stmt_answer->execute()) {
                throw new Exception("Failed to save answer: " . $stmt_answer->error);
            }
        }
        
        $stmt_answer->close();
    }
    
    // Log user activity for streak tracking
    $points = ($percentage >= 80) ? 20 : (($percentage >= 60) ? 15 : 10);
    logUserActivity($user_id, 'quiz', $points);
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Quiz score saved successfully',
        'score_id' => $score_id,
        'points_earned' => $points
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    if (isset($conn)) {
        $conn->rollback();
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

if (isset($stmt)) $stmt->close();
if (isset($stmt_answer)) $stmt_answer->close();
if (isset($conn)) $conn->close();
?>