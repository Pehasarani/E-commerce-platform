<?php
session_start();
require "dbConnect.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cart_id'])) {
    $cart_id = $_POST['cart_id'];
    $user_id = $_SESSION['user_id'];

    // Delete item from cart
    $delete_query = "DELETE FROM cart WHERE cart_id = ? AND userid = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("ii", $cart_id, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "";
    } else {
        $_SESSION['error'] = "";
    }
}

header("Location: ../cart.php");
exit(); 