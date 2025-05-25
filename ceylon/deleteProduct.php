<?php
session_start();
include('connection.php');

// Check if supplier is logged in
if (!isset($_SESSION['supplier_id'])) {
    header("Location: supplierLogin.php");
    exit();
}

// Check if product ID is provided
if (!isset($_GET['id'])) {
    header("Location: supplierDashboard.php");
    exit();
}

$product_id = $_GET['id'];
$message = '';
$error = '';

try {
    // Direct SQL delete query without bind parameters
    $sql = "DELETE FROM `product` WHERE `product_id` = '$product_id'";
    
    if ($conn->query($sql) === TRUE) {
        $message = "Product deleted successfully!";
    } else {
        $error = "Error deleting product: " . $conn->error;
    }
} catch (Exception $e) {
    $error = "Error: " . $e->getMessage();
}

// Redirect back to dashboard with message
$redirect_url = "supplierDashboard.php";
if ($message) {
    $redirect_url .= "?message=" . urlencode($message);
} elseif ($error) {
    $redirect_url .= "?error=" . urlencode($error);
}

header("Location: " . $redirect_url);
exit();
?> 