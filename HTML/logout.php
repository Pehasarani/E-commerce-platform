<?php
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Set success message
$_SESSION['toast'] = [
    'type' => 'success',
    'message' => "Logged out successfully"
];

// Redirect to login page
header("Location: login.php");
exit();
?> 