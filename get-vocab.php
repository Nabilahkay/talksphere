<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

$vocab_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($vocab_id > 0) {
    $stmt = $conn->prepare("
        SELECT v.*, t.theme_name 
        FROM vocabulary v 
        LEFT JOIN themes t ON v.theme_id = t.theme_id 
        WHERE v.vocab_id = ?
    ");
    $stmt->bind_param("i", $vocab_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $vocab = $result->fetch_assoc();
        echo json_encode([
            'success' => true,
            'vocab_id' => $vocab['vocab_id'],
            'chinese_character' => $vocab['chinese_character'],
            'pinyin' => $vocab['pinyin'],
            'english_meaning' => $vocab['english_meaning'],
            'theme_name' => $vocab['theme_name'],
            'audio_file' => $vocab['audio_file']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Vocabulary not found'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid vocabulary ID'
    ]);
}
?>