<?php

session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = new mysqli(
    "localhost",
    "root",
    "",  // Add your MySQL root password here if you have one
    "ceylon",
    "3306"
);

if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die(json_encode([
        'success' => false,
        'message' => 'Database connection failed. Please try again later.'
    ]));
}

// Set charset to ensure proper encoding
$conn->set_charset("utf8mb4");
