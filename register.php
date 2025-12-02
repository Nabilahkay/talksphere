<?php
session_start();
require_once 'config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Validation
    if (empty($full_name) || empty($email) || empty($password)) {
        $error = 'All fields are required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } else {
        // Check if email exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Email already registered';
        } else {
            // Create username from email
            $username = explode('@', $email)[0];
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, 'student')");
            $stmt->bind_param("ssss", $username, $email, $hashed_password, $full_name);
            
            if ($stmt->execute()) {
                $user_id = $conn->insert_id;
                
                // Create learning streak entry
                $stmt2 = $conn->prepare("INSERT INTO learning_streak (user_id) VALUES (?)");
                $stmt2->bind_param("i", $user_id);
                $stmt2->execute();
                
                $success = 'Registration successful! Redirecting to login...';
                header("refresh:2;url=login.php");
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Mandarin Learning Platform</title>
    <link rel="stylesheet" href="css/auth.css">
</head>
<body>
    <div class="container">
        <div class="left-section">
            <h1>Get Started Now</h1>
            
            <?php if ($error): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="full_name" placeholder="Enter your name" required>
                </div>
                
                <div class="form-group">
                    <label>Email address</label>
                    <input type="email" name="email" placeholder="Enter your email" required>
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Enter your password" required>
                </div>
                
                <div class="terms">
                    <input type="checkbox" id="terms" required>
                    <label for="terms">I agree to the <a href="#">terms & policy</a></label>
                </div>
                
                <button type="submit" class="btn-primary">Signup</button>
            </form>
            
            <div class="divider">Or</div>
            
            <div class="social-login">
                <button class="btn-google">
                    <img src="https://www.google.com/favicon.ico" alt="Google"> Sign in with Google
                </button>
                <button class="btn-apple">
                    <span>🍎</span> Sign in with Apple
                </button>
            </div>
            
            <p class="footer-text">
                Have an account? <a href="login.php">Sign In</a>
            </p>
        </div>
        
        <div class="right-section">
            <div class="logo">
                <img src="img/talksphere_logo.png" alt="TalkSphere Logo">
            </div>
        </div>
    </div>
</body>
</html>