<?php
require_once '../config/database.php';

// Set your desired password
$username = 'admin';
$email = 'admin@talksphere.com';
$password = 'admin123'; // Change this to your desired password
$full_name = 'System Administrator';
$role = 'super_admin';

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Check if admin table exists
$check_table = $conn->query("SHOW TABLES LIKE 'admin_users'");
if ($check_table->num_rows == 0) {
    echo "❌ Error: admin_users table doesn't exist. Run the SQL to create it first.<br>";
    exit();
}

// Delete existing admin if exists
$conn->query("DELETE FROM admin_users WHERE username = 'admin'");

// Insert new admin
$stmt = $conn->prepare("INSERT INTO admin_users (username, email, password, full_name, role, is_active) VALUES (?, ?, ?, ?, ?, 1)");
$stmt->bind_param("sssss", $username, $email, $hashed_password, $full_name, $role);

if ($stmt->execute()) {
    echo "✅ Admin user created successfully!<br><br>";
    echo "<strong>Login Credentials:</strong><br>";
    echo "Username: <code>admin</code><br>";
    echo "Password: <code>admin123</code><br><br>";
    echo "Password Hash (for verification): <code>$hashed_password</code><br><br>";
    echo "<a href='login.php'>Go to Admin Login →</a><br><br>";
    echo "<strong>⚠️ IMPORTANT: Delete this file (create_admin.php) after creating the admin user!</strong>";
} else {
    echo "❌ Error creating admin user: " . $stmt->error;
}
?>