<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if order ID is provided
if (isset($_GET['id'])) {
    $order_id = $_GET['id'];
    
    try {
        // First verify the order belongs to the user
        $verify_stmt = $pdo->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ?");
        $verify_stmt->execute([$order_id, $_SESSION['user_id']]);
        $result = $verify_stmt->rowCount();
        
        if ($result === 0) {
            header("Location: order_history.php?error=Order not found or unauthorized");
            exit();
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Delete order items first
        $delete_items_stmt = $pdo->prepare("DELETE FROM order_items WHERE order_id = ?");
        $delete_items_stmt->execute([$order_id]);
        
        // Then delete the order
        $delete_order_stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
        $delete_order_stmt->execute([$order_id]);
        
        // Commit transaction
        $pdo->commit();
        
        // Redirect back to order history
        header("Location: order_history.php");
        exit();
    } catch (PDOException $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        header("Location: order_history.php?error=" . urlencode("Error deleting order: " . $e->getMessage()));
        exit();
    }
} else {
    header("Location: order_history.php?error=" . urlencode("No order ID provided"));
    exit();
}
?> 