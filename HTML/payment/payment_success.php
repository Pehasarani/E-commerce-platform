<?php
session_start();
include('connection.php');

// Check if payment was successful
if (!isset($_SESSION['payment_success']) || $_SESSION['payment_success'] !== true) {
    header("Location: product_view.php");
    exit();
}

// Get order ID from session
$order_id = isset($_SESSION['order_id']) ? $_SESSION['order_id'] : '';

// Get order details if order_id exists
$order_details = null;
$order_items = [];
$total_items = 0;
$subtotal = 0;

if (!empty($order_id)) {
    // Get order details
    $order_query = "SELECT o.*, c.username, c.email, c.phone_personal, c.address 
                   FROM orders o 
                   JOIN customers c ON o.user_id = c.id 
                   WHERE o.payment_intent_id = ?";
    
    $stmt = $conn->prepare($order_query);
    $stmt->bind_param("s", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $order_details = $result->fetch_assoc();
        
        // Get order items
        $items_query = "SELECT oi.*, p.name, p.product_images, p.description, p.price 
                       FROM order_items oi 
                       JOIN product p ON oi.product_id = p.product_id 
                       WHERE oi.order_id = ?";
        
        $stmt = $conn->prepare($items_query);
        $stmt->bind_param("i", $order_details['id']);
        $stmt->execute();
        $items_result = $stmt->get_result();
        
        while ($item = $items_result->fetch_assoc()) {
            $order_items[] = $item;
            $total_items += $item['quantity'];
            $subtotal += ($item['price'] * $item['quantity']);
        }
    }
}

// Clear session variables
unset($_SESSION['payment_success']);
unset($_SESSION['order_id']);
unset($_SESSION['total_amount']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Success - Ceylon Cart</title>
    <link href="https://fonts.googleapis.com/css2?family=Koulen&family=Abel&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        /* General Styles */
        body {
            margin: 0;
            font-family: 'Abel', sans-serif;
            background-color: #f0f0f0;
            color: #333;
        }

        /* Header */
        .header {
            background-color: #F35821;
            position: sticky;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        /* Logo on the Left */
        .logo-container {
            display: flex;
            align-items: center;
        }

        .logo {
            width: 200px;
            height: auto;
        }

        /* Buttons on the Right */
        .header-buttons {
            display: flex;
            gap: 16px;
            align-items: center;
            margin-right: 24px;
        }

        /* Profile Button */
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

        /* Cart Button */
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

        /* Dropdown Menu */
        .profile-dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: #f9f9f9;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1;
            border-radius: 8px;
            overflow: hidden;
        }

        .dropdown-content a {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            font-size: 14px;
            font-weight: bold;
        }

        .dropdown-content a:hover {
            background-color: #f1f1f1;
        }

        .profile-dropdown:hover .dropdown-content {
            display: block;
        }

        .success-icon {
            color: #10B981;
            font-size: 4rem;
        }
        .order-item {
            border-bottom: 1px solid #E5E7EB;
            padding: 1.5rem 0;
        }
        .order-item:last-child {
            border-bottom: none;
        }
        .product-image {
            width: 100px;
            height: 100px;
            object-fit: contain;
            background-color: #f9fafb;
            border-radius: 8px;
            padding: 8px;
        }
    </style>
</head>
<body class="bg-gray-50">
    <header class="header">
        <a href="product_view.php">
            <div class="logo-container">
                <img src="Images/LogoVertical.png" alt="CeylonCart Logo" class="logo">
            </div>
        </a>
        <div class="header-buttons">
            <a href="cart.php">
                <button class="cart-button">
                    <img src="Images/ViewCart.png" alt="View Cart" class="cart-icon">
                </button>
            </a>
            <div class="profile-dropdown">
                <button class="profile-button">
                    <img src="Images/profile-picture.png" alt="Profile" class="profile-icon">
                </button>
                <div class="dropdown-content">
                    <a href="user_profile.php">VIEW PROFILE</a>
                    <a href="orderhistory.php">ORDER HISTORY</a>
                    <a href="login.php">LOG OUT</a>
                </div>
            </div>
        </div>
    </header>

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-lg p-8">
            <div class="text-center mb-8">
                <i class="fas fa-check-circle success-icon mb-4"></i>
                <h1 class="text-3xl font-bold mb-2" style="font-family: 'Koulen', sans-serif;">Payment Successful!</h1>
                <p class="text-gray-600">Thank you for your purchase. Your order has been confirmed.</p>
            </div>

            <?php if ($order_details): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Order Details -->
                <div>
                    <h2 class="text-xl font-bold mb-4" style="font-family: 'Koulen', sans-serif;">Order Details</h2>
                    <div class="bg-gray-50 rounded-lg p-4 space-y-3">
                        <p><strong>Order ID:</strong> <?php echo htmlspecialchars($order_details['payment_intent_id']); ?></p>
                        <p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($order_details['created_at'])); ?></p>
                        <p><strong>Total Items:</strong> <?php echo $total_items; ?></p>
                        <p><strong>Total Amount:</strong> Rs.<?php echo number_format($order_details['amount'], 2); ?></p>
                        <p><strong>Status:</strong> <span class="text-yellow-600 font-semibold">Pending</span></p>
                    </div>

                    <h2 class="text-xl font-bold mt-8 mb-4" style="font-family: 'Koulen', sans-serif;">Shipping Details</h2>
                    <div class="bg-gray-50 rounded-lg p-4 space-y-3">
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($order_details['username']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($order_details['email']); ?></p>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($order_details['phone_personal']); ?></p>
                        <p><strong>Address:</strong> <?php echo htmlspecialchars($order_details['address']); ?></p>
                    </div>
                </div>

                <!-- Order Items -->
                <div>
                    <h2 class="text-xl font-bold mb-4" style="font-family: 'Koulen', sans-serif;">Order Items</h2>
                    <?php if (!empty($order_items)): ?>
                    <div class="space-y-4">
                        <?php foreach ($order_items as $item): ?>
                        <div class="order-item">
                            <div class="flex gap-4">
                                <?php if (!empty($item['product_images'])): ?>
                                <img src="<?php echo htmlspecialchars($item['product_images']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                     class="product-image">
                                <?php endif; ?>
                                <div class="flex-1">
                                    <h3 class="font-bold text-lg mb-1"><?php echo htmlspecialchars($item['name']); ?></h3>
                                    <?php if (!empty($item['description'])): ?>
                                    <p class="text-gray-600 text-sm mb-2"><?php echo htmlspecialchars(substr($item['description'], 0, 100)) . '...'; ?></p>
                                    <?php endif; ?>
                                    <div class="flex justify-between items-center">
                                        <div class="text-gray-600">
                                            <span class="font-semibold">Quantity:</span> <?php echo $item['quantity']; ?>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-gray-600">
                                                <span class="font-semibold">Price:</span> Rs.<?php echo number_format($item['price'], 2); ?>
                                            </div>
                                            <div class="text-primary font-bold">
                                                Subtotal: Rs.<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                    </div>

                    <!-- Order Summary -->
                    <div class="mt-6 bg-gray-50 rounded-lg p-4">
                        <h3 class="font-bold mb-3" style="font-family: 'Koulen', sans-serif;">Order Summary</h3>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span>Subtotal</span>
                                <span>Rs.<?php echo number_format($subtotal, 2); ?></span>
                            </div>
                            <div class="flex justify-between font-bold text-lg pt-2 border-t">
                                <span>Total</span>
                                <span class="text-primary">Rs.<?php echo number_format($order_details['amount'], 2); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="bg-gray-50 rounded-lg p-4 text-center">
                        <p class="text-gray-600">No items found in this order.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="flex flex-row justify-center gap-4 mt-8">
                <a href="generate_invoice.php?order_id=<?php echo $order_details['id']; ?>" 
                   class="bg-[#F35821] text-white py-4 px-8 rounded-lg text-lg font-bold hover:bg-[#e04d1a] transition text-center flex-1 max-w-xs">
                    <i class="fas fa-download mr-2"></i> Download Invoice
                </a>
                <a href="product_view.php" 
                   class="bg-gray-200 text-gray-800 py-4 px-8 rounded-lg text-lg font-bold hover:bg-gray-300 transition text-center flex-1 max-w-xs">
                    <i class="fas fa-shopping-cart mr-2"></i> Continue Shopping
                </a>
            </div>
            <?php else: ?>
            <div class="text-center text-red-600">
                <p>Unable to retrieve order details. Please contact customer support.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <footer class="bg-white py-4 px-24 flex justify-between items-center">
        <div class="flex gap-4">
            <img src="Images/facebook.png" alt="Facebook" class="w-7 h-7 hover:scale-110 transition-transform duration-300">
            <img src="Images/instagram.png" alt="Instagram" class="w-7 h-7 hover:scale-110 transition-transform duration-300">
            <img src="Images/twitter.png" alt="Twitter" class="w-7 h-7 hover:scale-110 transition-transform duration-300">
        </div>
        <p class="text-gray-600">© CeylonCart 2025</p>
    </footer>
</body>
</html> 
