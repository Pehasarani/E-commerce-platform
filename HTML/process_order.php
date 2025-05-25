<?php
session_start();
include('connection.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'process_order') {
    try {
        // Get the submitted data
        $items = json_decode($_POST['items'], true);
        $address = $_POST['address'];
        
        if (!$items) {
            throw new Exception("No items selected for order");
        }
        
        // Calculate total amount
        $total_amount = 0;
        foreach ($items as $item) {
            $total_amount += $item['price'];
        }

        // Generate a unique payment intent ID
        $payment_intent_id = 'pi_' . uniqid();

        // Get user ID from session
        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

        // Start transaction
        $conn->begin_transaction();

        try {
            // Insert into orders table
            $sql = "INSERT INTO `orders` (`user_id`, `payment_intent_id`, `amount`, `status`, `created_at`) 
                    VALUES (?, ?, ?, 'pending', NOW())";
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Error preparing order statement: " . $conn->error);
            }
            
            $stmt->bind_param("isd", $user_id, $payment_intent_id, $total_amount);
            
            if (!$stmt->execute()) {
                throw new Exception("Error executing order statement: " . $stmt->error);
            }
            
            $order_id = $conn->insert_id;
            
            // Insert order items
            foreach ($items as $item) {
                $item_sql = "INSERT INTO `order_items` (`order_id`, `product_id`, `quantity`, `price`) 
                            VALUES (?, ?, ?, ?)";
                $item_stmt = $conn->prepare($item_sql);
                if (!$item_stmt) {
                    throw new Exception("Error preparing order items statement: " . $conn->error);
                }
                
                $item_stmt->bind_param("iiid", $order_id, $item['cart_id'], $item['quantity'], $item['price']);
                
                if (!$item_stmt->execute()) {
                    throw new Exception("Error executing order items statement: " . $item_stmt->error);
                }
                
                // Remove from cart
                $delete_sql = "DELETE FROM `cart` WHERE `cart_id` = ?";
                $delete_stmt = $conn->prepare($delete_sql);
                if (!$delete_stmt) {
                    throw new Exception("Error preparing delete statement: " . $conn->error);
                }
                
                $delete_stmt->bind_param("i", $item['cart_id']);
                
                if (!$delete_stmt->execute()) {
                    throw new Exception("Error executing delete statement: " . $delete_stmt->error);
                }
            }

            // Commit transaction
            $conn->commit();

            // Set success message
            $_SESSION['toast'] = [
                'type' => 'success',
                'message' => "Order placed successfully! Order ID: " . $order_id
            ];

        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            throw $e;
        }

    } catch (Exception $e) {
        // Set error message
        $_SESSION['toast'] = [
            'type' => 'error',
            'message' => "Error processing order: " . $e->getMessage()
        ];
    }

} else {
    $_SESSION['toast'] = [
        'type' => 'error',
        'message' => "Invalid request"
    ];
}

// Redirect back to cart
header("Location: cart.php");
exit();
?> 