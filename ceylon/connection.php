<?php
// Database configuration
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'ceylon');

// Error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create connection with error handling
try {
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset to ensure proper encoding
    if (!$conn->set_charset("utf8mb4")) {
        throw new Exception("Error loading character set utf8mb4: " . $conn->error);
    }
    
} catch (Exception $e) {
    // Log the error (in a production environment, you should log to a file)
    error_log("Database Connection Error: " . $e->getMessage());
    
    // Display user-friendly message
    die("Sorry, there was a problem connecting to the database. Please try again later.");
}
?>