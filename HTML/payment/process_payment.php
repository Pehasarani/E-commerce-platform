<?php


include('connection.php');
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', '2048M');
// Debug function
function debug_log($message, $data = null) {
    $log_message = date('Y-m-d H:i:s') . " - " . $message;
    if ($data !== null) {
        $log_message .= " - Data: " . print_r($data, true);
    }
    error_log($log_message);
    $_SESSION['debug_log'][] = $log_message;
}

// Log session data
debug_log("Session data", $_SESSION);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    debug_log("User not logged in - Session data", $_SESSION);
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
debug_log("Starting payment process", [
    'user_id' => $user_id,
    'session_id' => session_id()
]);

// Check DB connection
if ($conn->connect_error) {
    debug_log("Database connection failed", ['error' => $conn->connect_error]);
    die("Connection failed: " . $conn->connect_error);
}

// First, let's check all cart entries for this user
$check_all_cart_query = "SELECT * FROM cart WHERE userid = '$user_id'";
debug_log("Checking all cart entries", [
    'query' => $check_all_cart_query,
    'user_id' => $user_id
]);

$check_all_result = $conn->query($check_all_cart_query);
if (!$check_all_result) {
    debug_log("Error checking cart entries", [
        'error' => $conn->error,
        'query' => $check_all_cart_query
    ]);
    throw new Exception("Error checking cart entries: " . $conn->error);
}

$cart_entries = [];
while ($row = $check_all_result->fetch_assoc()) {
    $cart_entries[] = $row;
}

debug_log("All cart entries found", [
    'count' => count($cart_entries),
    'entries' => $cart_entries
]);

if (empty($cart_entries)) {
    debug_log("No cart entries found for user", [
        'user_id' => $user_id,
        'session_data' => $_SESSION
    ]);
    $_SESSION['error'] = "Your cart is empty";
    header("Location: cart.php");
    exit();
}

// Now check if the products still exist
$product_ids = array_column($cart_entries, 'product_product_id');
$product_ids_str = implode(',', $product_ids);

$check_products_query = "SELECT product_id FROM product WHERE product_id IN ($product_ids_str)";
debug_log("Checking product availability", [
    'query' => $check_products_query,
    'product_ids' => $product_ids
]);

$check_products_result = $conn->query($check_products_query);
if (!$check_products_result) {
    debug_log("Error checking products", [
        'error' => $conn->error,
        'query' => $check_products_query
    ]);
    throw new Exception("Error checking products: " . $conn->error);
}

$available_products = [];
while ($row = $check_products_result->fetch_assoc()) {
    $available_products[] = $row['product_id'];
}

debug_log("Available products", [
    'count' => count($available_products),
    'product_ids' => $available_products
]);

if (empty($available_products)) {
    debug_log("No products available from cart", [
        'cart_entries' => $cart_entries,
        'user_id' => $user_id
    ]);
    $_SESSION['error'] = "Some items in your cart are no longer available";
    header("Location: cart.php");
    exit();
}

// Get cart items with product details
$cart_query = "SELECT c.*, p.price 
               FROM cart c 
               JOIN product p ON c.product_product_id = p.product_id 
               WHERE c.userid = '$user_id' 
               AND p.product_id IN ($product_ids_str)";
debug_log("Executing final cart query", [
    'query' => $cart_query,
    'user_id' => $user_id
]);

$cart_result = $conn->query($cart_query);
if (!$cart_result) {
    debug_log("Error executing cart query", [
        'error' => $conn->error,
        'query' => $cart_query
    ]);
    throw new Exception("Error getting cart items: " . $conn->error);
}

debug_log("Final cart query results", [
    'num_rows' => $cart_result->num_rows,
    'affected_rows' => $conn->affected_rows
]);

if ($cart_result->num_rows === 0) {
    debug_log("No matching products found", [
        'cart_entries' => $cart_entries,
        'available_products' => $available_products
    ]);
    $_SESSION['error'] = "Some items in your cart are no longer available";
    header("Location: cart.php");
    exit();
}

// Process cart items
$cart_items = [];
$total_amount = 0;

while ($item = $cart_result->fetch_assoc()) {
    // Use product_product_id instead of product_id
    $product_id = $item['product_product_id'];
    $qty = $item['qty'];
    $price = $item['price'];
    
    $total_amount += $qty * $price;
    $cart_items[] = [
        'product_id' => $product_id,
        'qty' => $qty,
        'price' => $price
    ];
    
    debug_log("Processing cart item", [
        'item' => $item,
        'subtotal' => $qty * $price
    ]);
}

debug_log("Total amount calculated", [
    'total_amount' => $total_amount,
    'item_count' => count($cart_items)
]);

$order_id = 'ORD-' . strtoupper(uniqid());

// Begin transaction
$conn->begin_transaction();

try {
    // Insert into orders
    $order_query = "INSERT INTO orders (user_id, payment_intent_id, amount, status) 
                    VALUES ('$user_id', '$order_id', '$total_amount', 'Pending')";
    if (!$conn->query($order_query)) {
        throw new Exception("Order creation failed: " . $conn->error);
    }

    $order_insert_id = $conn->insert_id;

    // Insert each item into order_items
    foreach ($cart_items as $item) {
        $product_id = $item['product_id'];
        $qty = $item['qty'];
        $price = $item['price'];
        $order_item_query = "INSERT INTO order_items (order_id, product_id, quantity, price) 
                             VALUES ('$order_insert_id', '$product_id', '$qty', '$price')";
        if (!$conn->query($order_item_query)) {
            throw new Exception("Order item insert failed: " . $conn->error);
        }
    }

    // Clear cart
    $clear_cart_query = "DELETE FROM cart WHERE userid = '$user_id'";
    if (!$conn->query($clear_cart_query)) {
        throw new Exception("Failed to clear cart: " . $conn->error);
    }

    $conn->commit();

    $_SESSION['success'] = "Order placed successfully! Your order ID is: $order_id";

    echo "<script>setTimeout(function() {
        window.location.href = 'payment_success.php?order_id=" . urlencode($order_id) . "';
    }, 3000);</script>";

} catch (Exception $e) {
    $conn->rollback();
    debug_log("Error in payment process", [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    $_SESSION['error'] = "Error processing order: " . $e->getMessage();
    exit();
} finally {
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Processing Payment - CeylonCart</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <style>
        /* ... existing styles ... */
    </style>
</head>
<body class="font-sans m-0 p-0 bg-gray-50">
    <div class="min-h-screen flex items-center justify-center">
        <div class="text-center">
            <div class="animate-spin rounded-full h-16 w-16 border-t-2 border-b-2 border-[#ff6f00] mx-auto"></div>
            <h2 class="mt-4 text-xl font-semibold text-gray-700">Processing your payment...</h2>
            <p class="mt-2 text-gray-500">Please wait while we complete your order</p>
        </div>
    </div>

    <!-- Debug Panel -->
    <div class="debug-toggle" onclick="toggleDebugPanel()">Debug Log</div>
    <div class="debug-panel" id="debugPanel">
        <div class="flex justify-between items-center mb-2">
            <h3 class="text-[#ff6f00]">Debug Log</h3>
            <button onclick="clearDebugLog()" class="text-white text-sm hover:text-[#ff6f00]">Clear</button>
        </div>
        <div id="debugLogs"></div>
    </div>

    <script>
        // ... existing JavaScript code ...
    </script>
</body>
</html>
