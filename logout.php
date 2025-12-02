<?php
session_start();

// Store username before destroying session (for personalized goodbye message)
$username = $_SESSION['username'] ?? 'User';

// Destroy all session data
session_unset();
session_destroy();

// Start a new session for the logout message
session_start();
$_SESSION['logout_message'] = "You have been successfully logged out.";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout - Mandarin Learning Platform</title>
    <link rel="stylesheet" href="css/auth.css">
    <style>
        .logout-message {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logout-message h2 {
            color: #333;
            margin-bottom: 10px;
            font-size: 24px;
        }
        
        .logout-message p {
            color: #666;
            font-size: 16px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container login-container">
        <div class="login-box">
            <div class="logo-center">
                <img src="img/talksphere_logo.png" alt="TalkSphere Logo" class="logo-img">
            </div>
            
            <div class="logout-message">
                <h2>Goodbye, <?php echo htmlspecialchars($username); ?>!</h2>
                <p>You have been successfully logged out.</p>
            </div>
            
            <div class="alert" style="background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;">
                Your session has ended. Thank you for using our platform!
            </div>
            
            <a href="login.php" style="text-decoration: none;">
                <button class="btn-primary">Login Again</button>
            </a>
            
            <p class="footer-text">
                Want to create a new account? <a href="register.php">Register</a>
            </p>
        </div>
    </div>
    
    <script>
        // Auto-redirect to login after 5 seconds (optional)
        // Uncomment the lines below if you want auto-redirect
        /*
        setTimeout(function() {
            window.location.href = 'login.php';
        }, 5000);
        */
    </script>
</body>
</html>