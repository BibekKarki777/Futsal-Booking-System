<?php
/**
 * Database Configuration
 * Futsal Booking System
 */

// Base URL Configuration (adjust if needed for your server setup)
// This dynamically detects the base URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptPath = dirname($_SERVER['SCRIPT_NAME']);
$basePath = '';

// Find the project root folder
if (strpos($scriptPath, '/admin') !== false) {
    $basePath = str_replace('/admin', '', $scriptPath);
} elseif (strpos($scriptPath, '/user') !== false) {
    $basePath = str_replace('/user', '', $scriptPath);
} elseif (strpos($scriptPath, '/auth') !== false) {
    $basePath = str_replace('/auth', '', $scriptPath);
} else {
    $basePath = $scriptPath;
}

define('BASE_URL', rtrim($basePath, '/'));

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', '25123788'); 

// Create connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset to utf8mb4
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}

// PDO Connection (Alternative - for prepared statements)
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("PDO Connection error: " . $e->getMessage());
}
?>
