<?php
// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';  // Default Laragon password is empty
$database = 'mandarin_learning';

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4 for Chinese characters support
$conn->set_charset("utf8mb4");
?>