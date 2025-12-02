<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../login.php");
        exit();
    }
}

// Check if user is admin
function checkAdmin() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        header("Location: ../dashboard.php");
        exit();
    }
}

// Get current user info
function getCurrentUser() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    global $conn;
    
    // ✅ FIXED: Include email in the SELECT query
    $stmt = $conn->prepare("
        SELECT user_id, username, full_name, email, created_at 
        FROM users 
        WHERE user_id = ?
    ");
    
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return null;
    }
    
    return $result->fetch_assoc();
}
?>