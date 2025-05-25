<?php
session_start();

// Include the database connection file
include('dbConnect.php');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set header to return JSON response
header('Content-Type: application/json');

// Initialize response array
$response = array(
    'success' => false,
    'message' => '',
    'data' => null
);

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("Please login to add items to cart");
    }

    // Get product details from POST data
    $product_id = isset($_POST['product_id']) ? $_POST['product_id'] : null;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    $product_price = isset($_POST['product_price']) ? (float)$_POST['product_price'] : null;
    
    // Log the incoming data for debugging
    error_log("Cart Process - Received data: " . print_r($_POST, true));
    error_log("Cart Process - User ID: " . $_SESSION['user_id']);
    
    // Validate inputs
    if (!$product_id || $quantity <= 0 || !$product_price) {
        throw new Exception("Invalid product information");
    }
    
    // Calculate total cost
    $total_cost = $quantity * $product_price;
    
    // Check if product exists and has enough stock
    $check_product = "SELECT product_id, no_of_products FROM product WHERE product_id = ?";
    $stmt = $conn->prepare($check_product);
    if (!$stmt) {
        error_log("Database error in prepare: " . $conn->error);
        throw new Exception("Database error: " . $conn->error);
    }
    
    $stmt->bind_param("i", $product_id);
    if (!$stmt->execute()) {
        error_log("Database error in execute: " . $stmt->error);
        throw new Exception("Database error: " . $stmt->error);
    }
    
    $product_result = $stmt->get_result();
    
    if ($product_result->num_rows == 0) {
        throw new Exception("Product not found");
    }
    
    $product = $product_result->fetch_assoc();
    
    // Check if product already exists in cart
    $user_id = $_SESSION['user_id'];
    
    $check_cart = "SELECT * FROM cart WHERE product_product_id = ? AND userid = ?";
    $stmt = $conn->prepare($check_cart);
    if (!$stmt) {
        error_log("Database error in prepare cart check: " . $conn->error);
        throw new Exception("Database error: " . $conn->error);
    }
    
    $stmt->bind_param("ii", $product_id, $user_id);
    if (!$stmt->execute()) {
        error_log("Database error in execute cart check: " . $stmt->error);
        throw new Exception("Database error: " . $stmt->error);
    }
    
    $cart_result = $stmt->get_result();
    
    if ($cart_result->num_rows > 0) {
        // Product already in cart, update quantity
        $cart_item = $cart_result->fetch_assoc();
        $new_quantity = $cart_item['qty'] + $quantity;
        
        $new_total = $new_quantity * $product_price;
        
        $update_cart = "UPDATE cart SET qty = ?, total_cost = ? WHERE product_product_id = ? AND userid = ?";
        $stmt = $conn->prepare($update_cart);
        if (!$stmt) {
            error_log("Database error in prepare update: " . $conn->error);
            throw new Exception("Database error: " . $conn->error);
        }
        
        $stmt->bind_param("idii", $new_quantity, $new_total, $product_id, $user_id);
        
        if (!$stmt->execute()) {
            error_log("Database error in execute update: " . $stmt->error);
            throw new Exception("Error updating cart: " . $stmt->error);
        }
        
        $response['success'] = true;
        $response['message'] = "Cart updated successfully";
        $response['data'] = array(
            'quantity' => $new_quantity,
            'total_cost' => $new_total
        );
    } else {
        // Product not in cart, insert new record
        $insert_cart = "INSERT INTO cart (userid, qty, total_cost, product_product_id) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_cart);
        if (!$stmt) {
            error_log("Database error in prepare insert: " . $conn->error);
            throw new Exception("Database error: " . $conn->error);
        }
        
        $stmt->bind_param("iidi", $user_id, $quantity, $total_cost, $product_id);
        
        if (!$stmt->execute()) {
            error_log("Database error in execute insert: " . $stmt->error);
            throw new Exception("Error adding product to cart: " . $stmt->error);
        }
        
        $response['success'] = true;
        $response['message'] = "Product added to cart successfully";
        $response['data'] = array(
            'quantity' => $quantity,
            'total_cost' => $total_cost
        );
    }
    
} catch (Exception $e) {
    error_log("Cart Process Error: " . $e->getMessage());
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

// Return JSON response
echo json_encode($response);
exit();