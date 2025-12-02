<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$url = isset($_GET['url']) ? trim($_GET['url']) : '';

if (empty($url)) {
    echo json_encode(['error' => 'URL parameter is required']);
    exit;
}

// Search for vocabulary with this MyWebAR URL
$stmt = $conn->prepare("
    SELECT v.*, t.theme_name 
    FROM vocabulary v
    LEFT JOIN themes t ON v.theme_id = t.theme_id
    WHERE v.mywebar_url = ?
");

$stmt->bind_param("s", $url);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $vocab = $result->fetch_assoc();
    
    // Add audio URL if not exists
    if (empty($vocab['audio_url'])) {
        $vocab['audio_url'] = null;
    }
    
    echo json_encode($vocab);
} else {
    echo json_encode([
        'error' => 'No vocabulary found for this MyWebAR URL',
        'searched_url' => $url
    ]);
}
?>