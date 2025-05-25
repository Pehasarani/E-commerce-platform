<?php
include('connection.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];

    if ($action == "delete") {
        $cart_id = $_POST['cart_id'];

        $delete_query = "DELETE FROM cart WHERE cart_id = '$cart_id'";
        if ($conn->query($delete_query) === TRUE) {
            echo "Item deleted successfully";
        } else {
            echo "Error: " . $conn->error;
        }
    }

    if ($action == "update") {
        $cart_id = $_POST['cart_id'];
        $new_qty = $_POST['qty'];
        $new_total = $_POST['total_cost'];

        $update_query = "UPDATE cart SET qty = '$new_qty', total_cost = '$new_total' WHERE cart_id = '$cart_id'";
        if ($conn->query($update_query) === TRUE) {
            echo "Quantity updated successfully";
        } else {
            echo "Error: " . $conn->error;
        }
    }
}

$conn->close();
?>
