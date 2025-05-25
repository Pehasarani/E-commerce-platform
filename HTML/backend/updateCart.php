<?php
session_start();
require "dbConnect.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cart_id']) && isset($_POST['quantity'])) {
    $cart_id = $_POST['cart_id'];
    $quantity = (int)$_POST['quantity'];
    $user_id = $_SESSION['user_id'];

    // Get product details to check stock
    $check_query = "SELECT p.no_of_products, p.price, c.product_product_id 
                   FROM cart c 
                   JOIN product p ON c.product_product_id = p.product_id 
                   WHERE c.cart_id = ? AND c.userid = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("ii", $cart_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $item = $result->fetch_assoc();
        
        // Check if quantity is valid
        if ($quantity > 0 && $quantity <= $item['no_of_products']) {
            $total_cost = $quantity * $item['price'];
            
            // Update cart
            $update_query = "UPDATE cart SET qty = ?, total_cost = ? WHERE cart_id = ? AND userid = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("idii", $quantity, $total_cost, $cart_id, $user_id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Cart updated successfully";
            } else {
                $_SESSION['error'] = "Failed to update cart";
            }
        } else {
            $_SESSION['error'] = "Invalid quantity";
        }
    } else {
        $_SESSION['error'] = "Item not found in cart";
    }
}

header("Location: ../cart.php");
exit(); 