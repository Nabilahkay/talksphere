<?php
/**
 * Hybrid AR Configuration
 */

define('AR_ENABLED', true);
define('AR_SYSTEM_MODE', 'hybrid');
define('MYWEBAR_ENABLED', false);
define('MYWEBAR_PROJECT_ID', '');
define('MYWEBAR_BASE_URL', 'https://mywebar.com/p/');
define('MYWEBAR_API_KEY', '');
define('AR_MARKER_SIZE', 400);
define('AR_MARKER_TYPE', 'qr');
define('AR_QUALITY', 'high');
define('AR_MARKER_PATH', __DIR__ . '/../uploads/ar_markers/');
define('AR_MARKER_URL', '/mandarin-learning/uploads/ar_markers/');

if (!file_exists(AR_MARKER_PATH)) {
    mkdir(AR_MARKER_PATH, 0755, true);
}

function generateQRCode($data, $filename) {
    $size = AR_MARKER_SIZE;
    $qr_url = 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&data=' . urlencode($data) . '&format=png';
    
    $context = stream_context_create([
        'http' => ['timeout' => 10, 'user_agent' => 'Mozilla/5.0']
    ]);
    
    $qr_image = @file_get_contents($qr_url, false, $context);
    
    if ($qr_image && strlen($qr_image) > 100) {
        file_put_contents(AR_MARKER_PATH . $filename, $qr_image);
        return true;
    }
    
    $qr_url = 'https://chart.googleapis.com/chart?chs=' . $size . 'x' . $size . '&cht=qr&chl=' . urlencode($data) . '&choe=UTF-8';
    $qr_image = @file_get_contents($qr_url, false, $context);
    
    if ($qr_image && strlen($qr_image) > 100) {
        file_put_contents(AR_MARKER_PATH . $filename, $qr_image);
        return true;
    }
    
    return false;
}

function getARViewURL($object_id, $type = 'local') {
    $base_url = 'http://' . $_SERVER['HTTP_HOST'] . '/mandarin-learning';
    
    if ($type === 'mywebar' && MYWEBAR_ENABLED && MYWEBAR_PROJECT_ID) {
        return MYWEBAR_BASE_URL . MYWEBAR_PROJECT_ID . '/' . $object_id;
    }
    
    return $base_url . '/ar-view.php?object=' . $object_id;
}
?>