<?php
session_start();
require_once '../connection.php';

// Initialize variables
$error_message = '';
$success_message = '';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Fetch NIC from customers table
$nic = '';
$nic_query = "SELECT nic FROM customers WHERE id = '$user_id' LIMIT 1";
$nic_result = $conn->query($nic_query);
if ($nic_result && $nic_row = $nic_result->fetch_assoc()) {
    $nic = $nic_row['nic'];
}

// Decryption function for card number
function decrypt_card_number($encrypted_card, $encryption_key) {
    return openssl_decrypt(
        $encrypted_card,
        'AES-256-CBC',
        $encryption_key,
        0,
        substr($encryption_key, 0, 16)
    );
}

// Get total amount from POST or session
$total_amount = isset($_POST['total_amount']) ? $_POST['total_amount'] : (isset($_SESSION['total_amount']) ? $_SESSION['total_amount'] : 0);

// Store total amount in session for persistence
$_SESSION['total_amount'] = $total_amount;

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_payment'])) {
    try {
        // Start transaction
        $conn->begin_transaction();

        // Get form data with validation
        $card_name = trim($_POST['card_name']);
        $card_number = preg_replace('/\s+/', '', $_POST['card_number']);
        $expiry_date = $_POST['expiry_date'];
        $cvv = $_POST['cvv'];
        $save_card = isset($_POST['save_card']) ? 1 : 0;
        $card_type = $_POST['card_type'];
        
        // Get cart items from POST data
        $cart_items = isset($_POST['cart_items']) ? json_decode($_POST['cart_items'], true) : [];
        
        if (empty($cart_items)) {
            throw new Exception("No items in cart. Please add items to your cart before proceeding to payment.");
        }

        // Generate a unique order ID
        $order_id = 'ORD-' . strtoupper(uniqid());
        
        // Insert into orders table
        $order_sql = "INSERT INTO orders (user_id, payment_intent_id, amount, status, created_at) 
                     VALUES (?, ?, ?, 'Pending', NOW())";
        
        $stmt = $conn->prepare($order_sql);
        $stmt->bind_param("isd", $user_id, $order_id, $total_amount);
        
        if (!$stmt->execute()) {
            throw new Exception("Error creating order: " . $stmt->error);
        }
        
        // Get the inserted order ID
        $order_insert_id = $conn->insert_id;
        
        // Insert order items and update product quantities
        foreach ($cart_items as $item) {
            if (!isset($item['product_product_id']) || !isset($item['qty']) || !isset($item['price'])) {
                throw new Exception("Invalid cart item data");
            }
            
            $product_id = $item['product_product_id'];
            $quantity = $item['qty'];
            $price = $item['price'];
            
            // Check if product exists and has enough stock
            $check_stock_sql = "SELECT no_of_products, name FROM product WHERE product_id = ?";
            $stmt = $conn->prepare($check_stock_sql);
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $stock_result = $stmt->get_result();
            
            if ($stock_result->num_rows === 0) {
                throw new Exception("Product not found: " . $item['name']);
            }
            
            $stock_data = $stock_result->fetch_assoc();
            $current_stock = $stock_data['no_of_products'];
            $product_name = $stock_data['name'];
            
            if ($current_stock < $quantity) {
                throw new Exception("Not enough stock for product: " . $product_name . ". Available: " . $current_stock);
            }
            
            // Update product quantity
            $new_stock = $current_stock - $quantity;
            $update_product_sql = "UPDATE product SET no_of_products = ? WHERE product_id = ?";
            $stmt = $conn->prepare($update_product_sql);
            $stmt->bind_param("ii", $new_stock, $product_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Error updating product quantity: " . $stmt->error);
            }
            
            // Insert order item
            $order_item_sql = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($order_item_sql);
            $stmt->bind_param("iiid", $order_insert_id, $product_id, $quantity, $price);
            
            if (!$stmt->execute()) {
                throw new Exception("Error inserting order item: " . $stmt->error);
            }
        }

        // Clear cart after successful payment
        $clear_cart_sql = "DELETE FROM cart WHERE userid = ?";
        $stmt = $conn->prepare($clear_cart_sql);
        $stmt->bind_param("i", $user_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Error clearing cart: " . $stmt->error);
        }

        // If user chose to save card, store it in saved_card table
        if ($save_card) {
            $encryption_key = "your_encryption_key_here_32_chars_long"; // Use a secure key in production
            $encrypted_card = openssl_encrypt($card_number, 'AES-256-CBC', $encryption_key, 0, substr($encryption_key, 0, 16));
            
            $save_card_sql = "INSERT INTO saved_card (user_id, card_holder_name, card_number, card_type, expiry_date) 
                             VALUES (?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($save_card_sql);
            $stmt->bind_param("issss", $user_id, $card_name, $encrypted_card, $card_type, $expiry_date);
            
            if (!$stmt->execute()) {
                throw new Exception("Error saving card: " . $stmt->error);
            }
        }

        // If everything is successful, commit the transaction
        $conn->commit();
        
        // Set success session variables
        $_SESSION['payment_success'] = true;
        $_SESSION['order_id'] = $order_id;
        $_SESSION['total_amount'] = $total_amount;
        
        // Return success response for AJAX
        echo json_encode(['success' => true, 'redirect' => '../payment_success.php']);
        exit();
        
    } catch (Exception $e) {
        // Rollback the transaction on error
        $conn->rollback();
        $error_message = "Error processing payment: " . $e->getMessage();
        error_log("Payment Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $error_message]);
        exit();
    }
}

// Check if user has saved cards
$saved_cards = [];
$saved_cards_query = "SELECT * FROM saved_card WHERE user_id = '$user_id' ORDER BY created_at DESC";
$saved_cards_result = $conn->query($saved_cards_query);
$encryption_key = "your_encryption_key_here_32_chars_long"; // Use the same key as for encryption
if ($saved_cards_result && $saved_cards_result->num_rows > 0) {
    while ($card = $saved_cards_result->fetch_assoc()) {
        // Decrypt card number for display
        $card['decrypted_card_number'] = decrypt_card_number($card['card_number'], $encryption_key);
        $saved_cards[] = $card;
    }
}

// Get cart items
$cart_query = "SELECT c.*, 
               p.product_id, p.name as product_name, p.description, 
               p.weight, p.price as product_price, p.no_of_products, 
               p.product_images
               FROM cart c 
               JOIN product p ON c.product_product_id = p.product_id 
               WHERE c.userid = ?";
$stmt = $conn->prepare($cart_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_result = $stmt->get_result();

// Calculate total amount
$total_amount = 0;
while ($item = $cart_result->fetch_assoc()) {
    $total_amount += $item['total_cost'];
}
// Reset the result pointer
$cart_result->data_seek(0);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ceylon Cart - Payment</title>
    <link href="https://fonts.googleapis.com/css2?family=Koulen&family=Abel&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            margin: 0;
            font-family: 'Abel', sans-serif;
            background-color: #f0f0f0;
            color: #333;
        }
        .header {
            background-color: #F35821;
            position: sticky;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .logo-container {
            display: flex;
            align-items: center;
        }
        .logo {
            width: 200px;
            height: auto;
        }
        .search-bar {
            flex: 1;
            margin: 0 24px;
            max-width: 500px;
        }
        .search-input {
            width: 100%;
            padding: 8px 16px;
            border: none;
            border-radius: 16px;
            font-family: 'Abel', sans-serif;
            font-size: 16px;
            outline: none;
        }
        .header-buttons {
            display: flex;
            gap: 16px;
            align-items: center;
            margin-right: 24px;
        }
        .profile-button {
            background-color: #2B2B2B;
            border: none;
            border-radius: 16px;
            padding: 8px 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .profile-icon {
            width: 24px;
            height: 24px;
        }
        .cart-button {
            background-color: #FFFFFF;
            border: none;
            border-radius: 16px;
            padding: 8px 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .cart-icon {
            width: 24px;
            height: 24px;
        }
        .profile-dropdown {
            position: relative;
            display: inline-block;
            margin-right: 10px;
        }
        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: #2B2B2B;
            min-width: 160px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            z-index: 1;
            border-radius: 16px;
            overflow: hidden;
        }
        .dropdown-content a {
            color: #ffffff;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            font-family: 'Koulen', sans-serif;
            font-size: 16px;
        }
        .dropdown-content a:hover {
            background-color: #F35821;
        }
        .profile-dropdown:hover .dropdown-content {
            display: block;
        }
        .error-message {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
            display: block;
        }

        .general-error {
            background-color: #fff3f3;
            border: 1px solid #dc3545;
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }

        .form-group input.error {
            border-color: #dc3545;
        }

        .submit-button {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 20px;
        }

        .submit-button:hover {
            background-color: #0056b3;
        }

        .saved-cards {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            background-color: #f9f9f9;
        }

        .saved-cards-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .manage-cards-link {
            font-size: 14px;
            color: #F35821;
            text-decoration: none;
        }
        
        .manage-cards-link:hover {
            text-decoration: underline;
        }

        .saved-card-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .saved-card-item:hover {
            background-color: #f0f0f0;
        }

        .saved-card-item.selected {
            background-color: #e8f4ff;
            border-color: #007bff;
        }

        .card-icon {
            margin-right: 10px;
            font-size: 24px;
        }

        .card-details {
            flex-grow: 1;
        }

        .card-name {
            font-weight: bold;
            margin-bottom: 3px;
        }

        .card-number, .card-expiry {
            font-size: 14px;
            color: #666;
        }

        .card-radio {
            margin-left: 10px;
        }

        .save-card-option {
            margin-top: 15px;
            display: flex;
            align-items: center;
        }

        .save-card-option input {
            margin-right: 8px;
        }

        .save-card-option label {
            font-size: 14px;
            color: #333;
        }

        .divider {
            margin: 20px 0;
            text-align: center;
            position: relative;
        }

        .divider::before {
            content: "";
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background-color: #e0e0e0;
            z-index: 1;
        }

        .divider span {
            background-color: #fff;
            padding: 0 15px;
            position: relative;
            z-index: 2;
            color: #666;
            font-size: 14px;
        }

        .confirmation-popup {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .confirmation-content {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            text-align: center;
            max-width: 400px;
            width: 90%;
        }
        .loading-spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #F35821;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .cart-item {
            border-bottom: 1px solid #eee;
            padding: 15px 0;
        }
        .cart-item:last-child {
            border-bottom: none;
        }
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
        }
        .payment-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <!-- Confirmation Popup -->
    <div id="confirmationPopup" class="confirmation-popup">
        <div class="confirmation-content">
            <div class="loading-spinner"></div>
            <h2 class="text-xl font-bold mb-4">Processing Payment</h2>
            <p>Please wait while we process your payment...</p>
        </div>
    </div>

    <header class="header">
        <a href="../product_view.php">
            <div class="logo-container">
                <img src="../Images/LogoVertical.png" alt="CeylonCart Logo" class="logo">
            </div>
        </a>
        <div class="header-buttons">
            <a href="../cart.php">
                <button class="cart-button">
                    <img src="../Images/ViewCart.png" alt="View Cart" class="cart-icon">
                </button>
            </a>
            <div class="profile-dropdown">
                <button class="profile-button">
                    <img src="../Images/profile-picture.png" alt="Profile" class="profile-icon">
                </button>
                <div class="dropdown-content">
                    <a href="../user_profile.php">VIEW PROFILE</a>
                    <a href="../orderhistory.php">ORDER HISTORY</a>
                    <a href="../login.php">LOG OUT</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Dashboard Container -->
    <div class="dashboard-container">
        <main class="main-content">
            <!-- Payment Section -->
            <div class="flex justify-center items-center min-h-[60vh] my-12">
                <div class="bg-white rounded-2xl shadow-lg p-8 w-full max-w-3xl flex flex-col md:flex-row gap-8">
                    <!-- Left: Payment Details -->
                    <div class="flex-1">
                        <a href="../cart.php"" class="back-link flex items-center mb-4 font-bold text-primary hover:underline" style="font-family: 'Koulen', sans-serif;">
                            <i class="fas fa-arrow-left mr-2"></i> Back to Cart
                        </a>
                        <h1 class="text-3xl font-bold mb-6" style="font-family: 'Koulen', sans-serif;">Payment Details</h1>
                        <form id="paymentForm" method="POST">
                            <input type="hidden" name="submit_payment" value="1">
                            <?php
                            // Store cart items as JSON in a hidden input
                            $cart_items_data = array();
                            $cart_result->data_seek(0); // Reset the result pointer
                            while ($item = $cart_result->fetch_assoc()) {
                                $cart_items_data[] = array(
                                    'product_product_id' => $item['product_product_id'],
                                    'qty' => $item['qty'],
                                    'price' => $item['product_price'],
                                    'name' => $item['product_name']
                                );
                            }
                            ?>
                            <input type="hidden" name="cart_items" value="<?php echo htmlspecialchars(json_encode($cart_items_data)); ?>">
                            <div class="space-y-4">
                                <div class="form-group">
                                    <label for="card-name" class="block font-bold mb-1">Cardholder Name</label>
                                    <input type="text" id="card-name" name="card_name" required class="w-full border rounded px-3 py-2" placeholder="Enter cardholder name" value="<?php echo isset($_POST['card_name']) ? htmlspecialchars($_POST['card_name']) : ''; ?>">
                                    <span class="error-message" id="card-name-error"><?php echo isset($errors['card_name']) ? $errors['card_name'] : ''; ?></span>
                                </div>
                                <div class="form-group">
                                    <label for="nic" class="block font-bold mb-1">NIC Number</label>
                                    <input type="text" id="nic" name="nic" required class="w-full border rounded px-3 py-2" placeholder="Enter NIC number" value="<?php echo htmlspecialchars($nic); ?>" readonly>
                                    <span class="error-message" id="nic-error"><?php echo isset($errors['nic']) ? $errors['nic'] : ''; ?></span>
                                </div>
                                <div class="form-group">
                                    <label class="block font-bold mb-1">Card Type</label>
                                    <div class="flex gap-4 mt-2">
                                        <input type="radio" id="visa" name="card_type" value="Visa" class="hidden" <?php echo (isset($_POST['card_type']) && $_POST['card_type'] == 'Visa') ? 'checked' : ''; ?>>
                                        <label for="visa" class="card-type-option cursor-pointer p-3 border rounded-lg flex items-center justify-center transition-all duration-200 hover:bg-gray-50 <?php echo (isset($_POST['card_type']) && $_POST['card_type'] == 'Visa') ? 'border-[#F35821] bg-orange-50' : 'border-gray-200'; ?>">
                                            <i class="fab fa-cc-visa text-4xl" style="color: #1a1f71;"></i>
                                        </label>
                                        <input type="radio" id="mastercard" name="card_type" value="MasterCard" class="hidden" <?php echo (isset($_POST['card_type']) && $_POST['card_type'] == 'MasterCard') ? 'checked' : ''; ?>>
                                        <label for="mastercard" class="card-type-option cursor-pointer p-3 border rounded-lg flex items-center justify-center transition-all duration-200 hover:bg-gray-50 <?php echo (isset($_POST['card_type']) && $_POST['card_type'] == 'MasterCard') ? 'border-[#F35821] bg-orange-50' : 'border-gray-200'; ?>">
                                            <i class="fab fa-cc-mastercard text-4xl" style="color: #eb001b;"></i>
                                        </label>
                                    </div>
                                    <span class="error-message" id="card-type-error"><?php echo isset($errors['card_type']) ? $errors['card_type'] : ''; ?></span>
                                </div>
                                <div class="form-group">
                                    <label for="card-number" class="block font-bold mb-1">Card Number</label>
                                    <input type="text" id="card-number" name="card_number" required class="w-full border rounded px-3 py-2" placeholder="XXXX XXXX XXXX XXXX" maxlength="19" value="<?php echo isset($_POST['card_number']) ? htmlspecialchars($_POST['card_number']) : ''; ?>">
                                    <span class="error-message" id="card-number-error"><?php echo isset($errors['card_number']) ? $errors['card_number'] : ''; ?></span>
                                </div>
                                <div class="flex gap-4">
                                    <div class="form-group flex-1">
                                        <label for="expiry-date" class="block font-bold mb-1">Expiry Date</label>
                                        <input type="text" id="expiry-date" name="expiry_date" required class="w-full border rounded px-3 py-2" placeholder="MM/YY" maxlength="5" value="<?php echo isset($_POST['expiry_date']) ? htmlspecialchars($_POST['expiry_date']) : ''; ?>">
                                        <span class="error-message" id="expiry-date-error"><?php echo isset($errors['expiry_date']) ? $errors['expiry_date'] : ''; ?></span>
                                    </div>
                                    <div class="form-group flex-1">
                                        <label for="cvv" class="block font-bold mb-1">CVV</label>
                                        <input type="text" id="cvv" name="cvv" required class="w-full border rounded px-3 py-2" placeholder="XXX" maxlength="3" value="<?php echo isset($_POST['cvv']) ? htmlspecialchars($_POST['cvv']) : ''; ?>">
                                        <span class="error-message" id="cvv-error"><?php echo isset($errors['cvv']) ? $errors['cvv'] : ''; ?></span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 mt-2">
                                    <input type="checkbox" id="save-card" name="save_card" value="1" class="rounded">
                                    <label for="save-card" class="text-sm">Save this card for future purchases</label>
                                </div>
                                <?php if ($error_message): ?>
                                    <div class="error-message general-error bg-red-100 border border-red-300 text-red-800 rounded px-4 py-2 mt-2">
                                        <?php echo htmlspecialchars($error_message); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <!-- Only show the button on mobile, on desktop it's in the summary -->
                            <div class="block md:hidden mt-6">
                                <button type="submit" class="w-full bg-[#F35821] text-white py-4 rounded-lg text-lg font-bold hover:bg-[#e04d1a] transition">PAY $<?php echo number_format($total_amount, 2); ?></button>
                            </div>
                        </form>
                    </div>
                    <!-- Right: Order Summary & Payment -->
                    <div class="w-full md:w-80 flex-shrink-0 bg-gray-50 rounded-2xl shadow p-6 flex flex-col gap-6">
                        <h2 class="font-bold text-lg mb-2" style="font-family: 'Koulen', sans-serif;">ORDER SUMMARY</h2>
                        
                        <!-- Cart Items Display -->
                        <div class="space-y-4 mb-4">
                            <?php if ($cart_result && $cart_result->num_rows > 0): ?>
                                <?php while ($item = $cart_result->fetch_assoc()): ?>
                                    <div class="flex items-center gap-3 bg-white p-3 rounded-lg">
                                        <?php 
                                        // Handle product images - get the first image if it's a JSON array
                                        $product_image = $item['product_images'];
                                        if (!empty($product_image)) {
                                            if (strpos($product_image, '[') === 0) {
                                                $images = json_decode($product_image, true);
                                                $product_image = !empty($images) ? $images[0] : '';
                                            }
                                            // Remove any quotes if present
                                            $product_image = trim($product_image, '"\'');
                                        }
                                        ?>
                                        <img src="../<?php echo htmlspecialchars($product_image); ?>" 
                                             alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                             class="w-16 h-16 object-cover rounded"
                                             onerror="this.src='../Images/no-image.jpg'">
                                        <div class="flex-1">
                                            <h3 class="font-bold text-sm"><?php echo htmlspecialchars($item['product_name']); ?></h3>
                                            <p class="text-sm text-gray-600">Qty: <?php echo $item['qty']; ?></p>
                                            <p class="text-sm font-bold">$ <?php echo number_format($item['total_cost'], 2); ?></p>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p class="text-center text-gray-500">Your cart is empty</p>
                            <?php endif; ?>
                        </div>

                        <div class="flex justify-between text-gray-700 mb-1">
                            <span>Total Amount</span>
                            <span class="font-bold text-primary text-xl">$ <?php echo number_format($total_amount, 2); ?></span>
                        </div>
                        <div class="flex flex-col gap-2 mt-4">
                            <form class="hidden md:block" id="desktopPaymentForm">
                                <button type="submit" form="paymentForm" class="w-full bg-[#F35821] text-white py-4 rounded-lg text-lg font-bold hover:bg-[#e04d1a] transition">PAY $ <?php echo number_format($total_amount, 2); ?></button>
                            </form>
                        </div>
                        <?php if (!empty($saved_cards)): ?>
                        <div class="mt-6">
                            <h3 class="font-bold mb-2" style="font-family: 'Koulen', sans-serif;">Your Saved Cards</h3>
                            <div class="flex flex-col gap-2">
                                <?php foreach ($saved_cards as $index => $card): ?>
                                <div class="flex items-center gap-3 bg-white border rounded-lg px-3 py-2 saved-card-item"
                                     data-card-name="<?php echo htmlspecialchars($card['card_holder_name']); ?>"
                                     data-card-number="<?php echo htmlspecialchars($card['decrypted_card_number']); ?>"
                                     data-expiry="<?php echo htmlspecialchars($card['expiry_date']); ?>"
                                     data-card-type="<?php echo htmlspecialchars($card['card_type']); ?>"
                                     data-nic="<?php echo htmlspecialchars($nic); ?>">
                                    <div class="text-xl">
                                        <?php if ($card['card_type'] == 'Visa'): ?>
                                            <i class="fab fa-cc-visa" style="color: #1a1f71;"></i>
                                        <?php elseif ($card['card_type'] == 'MasterCard'): ?>
                                            <i class="fab fa-cc-mastercard" style="color: #eb001b;"></i>
                                        <?php else: ?>
                                            <i class="far fa-credit-card"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-1">
                                        <div class="font-bold text-sm"><?php echo htmlspecialchars($card['card_holder_name']); ?></div>
                                        <div class="text-xs text-gray-500">XXXX XXXX XXXX <?php echo htmlspecialchars(substr($card['decrypted_card_number'], -4)); ?></div>
                                    </div>
                                    <div class="text-xs text-gray-500">Exp: <?php echo htmlspecialchars($card['expiry_date']); ?></div>
                                </div>
                                <?php endforeach; ?>
                                <a href="manage_cards.php" class="manage-cards-link flex items-center gap-1 mt-2 text-primary font-bold hover:underline"><i class="fas fa-cog"></i> Manage Cards</a>
                            </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <footer class="bg-white py-4 px-24 flex justify-between items-center">
        <div class="flex gap-4">
            <img src="../Images/facebook.png" alt="Facebook" class="w-7 h-7 hover:scale-110 transition-transform duration-300">
            <img src="../Images/instagram.png" alt="Instagram" class="w-7 h-7 hover:scale-110 transition-transform duration-300">
            <img src="../Images/twitter.png" alt="Twitter" class="w-7 h-7 hover:scale-110 transition-transform duration-300">
        </div>
        <p class="text-gray-600">© CeylonCart 2025</p>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('paymentForm');
            const cardNumber = document.getElementById('card-number');
            const expiryDate = document.getElementById('expiry-date');
            const cvv = document.getElementById('cvv');
            const cardName = document.getElementById('card-name');
            const confirmationPopup = document.getElementById('confirmationPopup');

            // Format card number
            cardNumber.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                value = value.replace(/(\d{4})(?=\d)/g, '$1 ');
                e.target.value = value;
                validateCardNumber(value);
            });

            // Format expiry date
            expiryDate.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length >= 2) {
                    value = value.slice(0,2) + '/' + value.slice(2);
                }
                e.target.value = value;
                validateExpiryDate(value);
            });

            // Format CVV
            cvv.addEventListener('input', function(e) {
                e.target.value = e.target.value.replace(/\D/g, '');
                validateCVV(e.target.value);
            });

            function validateCardNumber(number) {
                const cleanNumber = number.replace(/\s/g, '');
                if (cleanNumber.length === 16) {
                    cardNumber.classList.remove('error');
                    cardNumber.classList.add('valid');
                    document.getElementById('card-number-error').textContent = '';
                } else {
                    cardNumber.classList.remove('valid');
                    cardNumber.classList.add('error');
                    document.getElementById('card-number-error').textContent = 'Card number must be 16 digits';
                }
            }

            function validateExpiryDate(date) {
                const [month, year] = date.split('/');
                const currentDate = new Date();
                const currentYear = currentDate.getFullYear() % 100;
                const currentMonth = currentDate.getMonth() + 1;

                if (month && year && 
                    month >= 1 && month <= 12 && 
                    (year > currentYear || (year == currentYear && month >= currentMonth))) {
                    expiryDate.classList.remove('error');
                    expiryDate.classList.add('valid');
                    document.getElementById('expiry-date-error').textContent = '';
                } else {
                    expiryDate.classList.remove('valid');
                    expiryDate.classList.add('error');
                    document.getElementById('expiry-date-error').textContent = 'Invalid or expired date';
                }
            }

            function validateCVV(cvvValue) {
                if (cvvValue.length === 3) {
                    cvv.classList.remove('error');
                    cvv.classList.add('valid');
                    document.getElementById('cvv-error').textContent = '';
                } else {
                    cvv.classList.remove('valid');
                    cvv.classList.add('error');
                    document.getElementById('cvv-error').textContent = 'CVV must be exactly 3 digits';
                }
            }

            // Cardholder name validation (letters and spaces, 3-50 chars)
            cardName.addEventListener('input', function(e) {
                let value = e.target.value.replace(/[^A-Za-z\s]/g, '');
                e.target.value = value;
                if (!value.match(/^[A-Za-z\s]{3,50}$/)) {
                    cardName.classList.add('error');
                    cardName.classList.remove('valid');
                    document.getElementById('card-name-error').textContent = 'Name must contain only letters and spaces (3-50 characters)';
                } else {
                    cardName.classList.remove('error');
                    cardName.classList.add('valid');
                    document.getElementById('card-name-error').textContent = '';
                }
            });

            // Form validation before submission
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Show confirmation popup
                confirmationPopup.style.display = 'flex';
                
                // Collect form data
                const formData = new FormData(form);
                
                // Send AJAX request
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = data.redirect;
                    } else {
                        // Hide popup and show error
                        confirmationPopup.style.display = 'none';
                        alert(data.error || 'Payment failed. Please try again.');
                    }
                })
                .catch(error => {
                    // Hide popup and show error
                    confirmationPopup.style.display = 'none';
                    alert('An error occurred. Check if your card details inserted correctly.');
                    console.error('Error:', error);
                });
            });

            // Add card type selection handling
            const cardTypeOptions = document.querySelectorAll('.card-type-option');
            const cardTypeInputs = document.querySelectorAll('input[name="card_type"]');

            cardTypeOptions.forEach((option, index) => {
                option.addEventListener('click', () => {
                    // Remove selected styles from all options
                    cardTypeOptions.forEach(opt => {
                        opt.classList.remove('border-[#F35821]', 'bg-orange-50');
                        opt.classList.add('border-gray-200');
                    });

                    // Add selected styles to clicked option
                    option.classList.remove('border-gray-200');
                    option.classList.add('border-[#F35821]', 'bg-orange-50');

                    // Check the corresponding radio input
                    cardTypeInputs[index].checked = true;
                });
            });

            // Autofill form when a saved card tile is clicked
            document.querySelectorAll('.saved-card-item').forEach(function(tile) {
                tile.addEventListener('click', function() {
                    document.getElementById('card-name').value = tile.getAttribute('data-card-name');
                    document.getElementById('card-number').value = tile.getAttribute('data-card-number').replace(/(\d{4})(?=\d)/g, '$1 ');
                    document.getElementById('expiry-date').value = tile.getAttribute('data-expiry');
                    // Set card type radio
                    var cardType = tile.getAttribute('data-card-type');
                    if (cardType === 'Visa') {
                        document.getElementById('visa').checked = true;
                        document.querySelector('label[for="visa"]').classList.add('border-[#F35821]', 'bg-orange-50');
                        document.querySelector('label[for="mastercard"]').classList.remove('border-[#F35821]', 'bg-orange-50');
                    } else if (cardType === 'MasterCard') {
                        document.getElementById('mastercard').checked = true;
                        document.querySelector('label[for="mastercard"]').classList.add('border-[#F35821]', 'bg-orange-50');
                        document.querySelector('label[for="visa"]').classList.remove('border-[#F35821]', 'bg-orange-50');
                    }
                    document.getElementById('nic').value = tile.getAttribute('data-nic');
                });
            });
        });
    </script>
</body>
</html>