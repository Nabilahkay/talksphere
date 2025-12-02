<?php
/**
 * Hybrid AR Configuration
 * Supports both Local and MyWebAR modes
 */

// ============================================
// SYSTEM SETTINGS
// ============================================
define('AR_ENABLED', true);
define('AR_SYSTEM_MODE', 'hybrid'); // 'local', 'mywebar', or 'hybrid'

// ============================================
// MYWEBAR SETTINGS (Fill in after creating account)
// ============================================
define('MYWEBAR_ENABLED', false); // Set to true when you have MyWebAR account
define('MYWEBAR_PROJECT_ID', ''); // Get from MyWebAR dashboard (e.g., 'project_abc123')
define('MYWEBAR_BASE_URL', 'https://mywebar.com/p/');
define('MYWEBAR_API_KEY', ''); // Optional: for advanced features

// ============================================
// LOCAL AR SETTINGS
// ============================================
define('AR_MARKER_SIZE', 400); // QR code size in pixels
define('AR_MARKER_TYPE', 'qr'); // QR code markers
define('AR_QUALITY', 'high'); // high, medium, low

// ============================================
// STORAGE PATHS
// ============================================
define('AR_MARKER_PATH', __DIR__ . '/../uploads/ar_markers/');
define('AR_MARKER_URL', '/mandarin-learning/uploads/ar_markers/');

// Create marker directory if it doesn't exist
if (!file_exists(AR_MARKER_PATH)) {
    mkdir(AR_MARKER_PATH, 0755, true);
}

/**
 * Generate QR Code for AR markers
 * Uses free QR code API - no authentication needed
 */
function generateQRCode($data, $filename) {
    $size = AR_MARKER_SIZE;
    
    // Try QR Server API first (most reliable)
    $qr_url = 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&data=' . urlencode($data) . '&format=png';
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'user_agent' => 'Mozilla/5.0'
        ]
    ]);
    
    $qr_image = @file_get_contents($qr_url, false, $context);
    
    if ($qr_image && strlen($qr_image) > 100) {
        file_put_contents(AR_MARKER_PATH . $filename, $qr_image);
        return true;
    }
    
    // Fallback to Google Charts API
    $qr_url = 'https://chart.googleapis.com/chart?chs=' . $size . 'x' . $size . '&cht=qr&chl=' . urlencode($data) . '&choe=UTF-8';
    $qr_image = @file_get_contents($qr_url, false, $context);
    
    if ($qr_image && strlen($qr_image) > 100) {
        file_put_contents(AR_MARKER_PATH . $filename, $qr_image);
        return true;
    }
    
    return false;
}

/**
 * Get AR view URL for an object
 */
function getARViewURL($object_id, $type = 'local') {
    $base_url = 'http://' . $_SERVER['HTTP_HOST'] . '/mandarin-learning';
    
    if ($type === 'mywebar' && MYWEBAR_ENABLED && MYWEBAR_PROJECT_ID) {
        return MYWEBAR_BASE_URL . MYWEBAR_PROJECT_ID . '/' . $object_id;
    }
    
    return $base_url . '/ar-view.php?object=' . $object_id;
}
?>
