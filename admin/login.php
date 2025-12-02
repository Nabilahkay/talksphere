<?php
session_start();
require_once '../config/database.php';

// Redirect if already logged in
if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        $stmt = $conn->prepare("SELECT * FROM admin_users WHERE username = ? AND is_active = 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $admin = $result->fetch_assoc();
            
            if (password_verify($password, $admin['password'])) {
                // Login successful
                $_SESSION['admin_id'] = $admin['admin_id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_role'] = $admin['role'];
                $_SESSION['admin_name'] = $admin['full_name'];
                
                // Update last login
                $stmt = $conn->prepare("UPDATE admin_users SET last_login = NOW() WHERE admin_id = ?");
                $stmt->bind_param("i", $admin['admin_id']);
                $stmt->execute();
                
                // Log activity
                $ip = $_SERVER['REMOTE_ADDR'];
                $stmt = $conn->prepare("INSERT INTO admin_activity_log (admin_id, action_type, description, ip_address) VALUES (?, 'login', 'Admin logged in', ?)");
                $stmt->bind_param("is", $admin['admin_id'], $ip);
                $stmt->execute();
                
                header("Location: dashboard.php");
                exit();
            } else {
                $error = 'Invalid username or password';
            }
        } else {
            $error = 'Invalid username or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - TalkSphere</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #c92a2a 0%, #8B0000 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 20px;
            padding: 50px;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .logo {
            font-size: 60px;
            margin-bottom: 20px;
        }

        h1 {
            font-size: 28px;
            color: #333;
            margin-bottom: 10px;
        }

        .subtitle {
            font-size: 14px;
            color: #666;
        }

        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #f44336;
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .login-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }

        .back-link {
            text-align: center;
            margin-top: 30px;
        }

        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        .security-note {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            font-size: 13px;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo">🔐</div>
            <h1>Admin Panel</h1>
            <p class="subtitle">TalkSphere Mandarin Learning Platform</p>
        </div>

        <?php if ($error): ?>
            <div class="error-message">
                ⚠️ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="login-btn">
                🔓 Login to Admin Panel
            </button>
        </form>

        <div class="security-note">
            ⚠️ <strong>Default Credentials:</strong><br>
            Username: <code>admin</code><br>
            Password: <code>admin123</code><br>
            <strong>Change these immediately after first login!</strong>
        </div>

        <div class="back-link">
            <a href="../dashboard.php">← Back to Main Site</a>
        </div>
    </div>
</body>
</html>