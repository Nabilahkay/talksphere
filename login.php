<?php
session_start();
require_once 'config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = 'Email and password are required';
    } else {
        $stmt = $conn->prepare("SELECT user_id, username, password, full_name, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                
                // FIRST: Check current login count BEFORE incrementing
                $check_count = $conn->prepare("SELECT login_count FROM users WHERE user_id = ?");
                $check_count->bind_param("i", $user['user_id']);
                $check_count->execute();
                $count_result = $check_count->get_result()->fetch_assoc();
                $current_login_count = $count_result['login_count'] ?? 0;
                
                // THEN: Update last login and increment login count
                $update_login = $conn->prepare("UPDATE users SET last_login = NOW(), login_count = login_count + 1 WHERE user_id = ?");
                $update_login->bind_param("i", $user['user_id']);
                $update_login->execute();
                
                // Redirect based on login count (0 means first login)
                if ($current_login_count == 0) {
                    // First time login - show welcome page
                    header('Location: index.php');
                } else {
                    // Regular login - go to dashboard
                    header('Location: dashboard.php');
                }
                exit();
            } else {
                $error = 'Invalid email or password';
            }
        } else {
            $error = 'Invalid email or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Mandarin Learning Platform</title>
    <link rel="stylesheet" href="css/auth.css">
</head>
<body>
    <div class="container login-container">
        <div class="login-box">
            <div class="logo-center">
                <img src="img/talksphere_logo.png" alt="'TalkSphere Logo" class="logo-img">
            </div>
            
            <?php if ($error): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Input Your Email</label>
                    <input type="email" name="email" placeholder="Email" required>
                </div>
                
                <div class="form-group">
                    <label>Input Your Password</label>
                    <div class="password-field">
                        <input type="password" name="password" id="password" placeholder="Password" required>
                        <span class="toggle-password" onclick="togglePassword()">👁️</span>
                    </div>
                </div>
                
                <button type="submit" class="btn-primary">Login</button>
            </form>
            
            <button class="btn-google">
                <img src="https://www.google.com/favicon.ico" alt="Google"> Login With Google
            </button>
            
            <p class="footer-text">
                Doesn't have account? <a href="register.php">Register</a>
            </p>
        </div>
    </div>
    
    <script>
        function togglePassword() {
            const pwd = document.getElementById('password');
            pwd.type = pwd.type === 'password' ? 'text' : 'password';
        }
    </script>
</body>
</html>