<?php
include('connection.php');

// Fetch updated cart data
$cart_query = "SELECT c.cart_id, c.qty, c.total_cost, p.price 
               FROM cart c 
               JOIN product p ON c.product_product_id = p.product_id";
$cart_result = $conn->query($cart_query);

$cart_data = array();
$total_items = 0;
$total_cost = 0;

if ($cart_result->num_rows > 0) {
    while ($row = $cart_result->fetch_assoc()) {
        $cart_data[] = array(
            'cart_id' => $row['cart_id'],
            'qty' => $row['qty'],
            'total_cost' => $row['total_cost'],
            'price' => $row['price']
        );
        $total_items += $row['qty'];
        $total_cost += $row['total_cost'];
    }
}

// Return JSON response
$response = array(
    'success' => true,
    'cart_items' => $cart_data,
    'summary' => array(
        'total_items' => $total_items,
        'total_cost' => $total_cost
    )
);

header('Content-Type: application/json');
echo json_encode($response);

$conn->close();
?> 