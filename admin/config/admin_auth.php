<?php
// Admin authentication helper functions

function checkAdminLogin() {
    if (!isset($_SESSION['admin_id'])) {
        header("Location: login.php");
        exit();
    }
}

function getCurrentAdmin() {
    if (!isset($_SESSION['admin_id'])) {
        return null;
    }
    
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM admin_users WHERE admin_id = ? AND is_active = 1");
    $stmt->bind_param("i", $_SESSION['admin_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

function isAdmin() {
    return isset($_SESSION['admin_id']);
}

function isSuperAdmin() {
    return isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'super_admin';
}

function logAdminActivity($action_type, $target_type = null, $target_id = null, $description = null) {
    if (!isset($_SESSION['admin_id'])) return;
    
    global $conn;
    
    $admin_id = $_SESSION['admin_id'];
    $ip = $_SERVER['REMOTE_ADDR'];
    
    $stmt = $conn->prepare("INSERT INTO admin_activity_log (admin_id, action_type, target_type, target_id, description, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ississ", $admin_id, $action_type, $target_type, $target_id, $description, $ip);
    $stmt->execute();
}

function adminLogout() {
    if (isset($_SESSION['admin_id'])) {
        logAdminActivity('logout', null, null, 'Admin logged out');
    }
    
    session_destroy();
    header("Location: login.php");
    exit();
}
?>