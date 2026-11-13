<?php
/**
 * CholoGhuri - Database Configuration Template
 * 
 * HOW TO USE:
 * 1. Copy this file:  cp config/database.example.php config/database.php
 * 2. Fill in your real database credentials in config/database.php
 * 3. NEVER commit config/database.php to Git (it is already in .gitignore)
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'your_db_username');      // e.g. 'root' for XAMPP
define('DB_PASS', 'your_db_password');      // e.g. '' for XAMPP default
define('DB_NAME', 'your_database_name');    // e.g. 'chologhuri'
define('DB_PORT', 3306);

// --- DO NOT EDIT BELOW THIS LINE ---
// MySQLi connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
if ($conn->connect_error) {
    error_log('MySQLi connection failed: ' . $conn->connect_error);
    $conn = null;
} else {
    $conn->set_charset('utf8mb4');
}

// PDO connection
try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    error_log('PDO connection failed: ' . $e->getMessage());
    $pdo = null;
}
