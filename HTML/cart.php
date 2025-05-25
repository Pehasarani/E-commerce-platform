<?php
require "backend/dbConnect.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user's address - using the correct table name (customers instead of customer)
$address_query = "SELECT address FROM customers WHERE id = ?";
$stmt = $conn->prepare($address_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$address_result = $stmt->get_result();
$user_address = "No address set";
if ($address_result->num_rows > 0) {
    $user_data = $address_result->fetch_assoc();
    $user_address = $user_data['address'];
}

// Fetch cart items with product details
$cart_query = "SELECT c.*, p.name, p.price, p.product_images, p.no_of_products 
               FROM cart c 
               JOIN product p ON c.product_product_id = p.product_id 
               WHERE c.userid = ?";
$stmt = $conn->prepare($cart_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_result = $stmt->get_result();

// Calculate totals
$total_items = 0;
$total_cost = 0;
$cart_items = [];

while ($item = $cart_result->fetch_assoc()) {
    $cart_items[] = $item;
    $total_items += $item['qty'];
    $total_cost += $item['total_cost'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - CeylonCart</title>
    <link href="https://fonts.googleapis.com/css2?family=Koulen&family=Abel&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#ff6600',
                    }
                }
            }
        }
    </script>
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

        /* Search Bar in the Middle */
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

        .custom-checkbox {
            position: relative;
            width: 24px;
            height: 24px;
            display: inline-block;
        }
        .custom-checkbox input[type="checkbox"] {
            opacity: 0;
            width: 24px;
            height: 24px;
            margin: 0;
            position: absolute;
            left: 0;
            top: 0;
            cursor: pointer;
            z-index: 2;
        }
        .checkmark {
            position: absolute;
            top: 0;
            left: 0;
            height: 24px;
            width: 24px;
            background-color: #fff;
            border: 2px solid #ff6600;
            border-radius: 8px;
            transition: background 0.2s, border-color 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .custom-checkbox input[type="checkbox"]:checked ~ .checkmark {
            background-color: #ff6600;
            border-color: #ff6600;
        }
        .checkmark svg {
            display: none;
        }
        .custom-checkbox input[type="checkbox"]:checked ~ .checkmark svg {
            display: block;
        }
        .delete-btn:focus {
            outline: 2px solid #ff6600;
            outline-offset: 2px;
        }
    </style>
</head>

<body>
    <header class="header">
        <a href="product_view.php">
            <div class="logo-container">
                <img src="Images/LogoVertical.png" alt="CeylonCart Logo" class="logo">
            </div>
        </a>
            
        <!-- Buttons on the Right -->
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

    <main class="bg-gray-100 min-h-screen py-10 px-4">
        <div class="max-w-7xl mx-auto flex flex-col lg:flex-row gap-8">
            <!-- Cart Items Section -->
            <section class="flex-1 flex flex-col gap-6">

                <a href="product_view.php">
                    <div class="flex items-center gap-2 mb-4">
                        <img src="Images/arrow.png" alt="Back" class="w-6 h-6">
                        <h2 class="text-2xl font-bold" style="font-family: 'Koulen', sans-serif;">Continue Shopping</h2>
                    </div>
                </a>
                <?php if (empty($cart_items)): ?>
                    <div class="bg-white rounded-2xl shadow p-8 text-center">
                        <p class="text-gray-600">Your cart is empty</p>
                        <a href="product_view.php" class="mt-4 inline-block text-primary font-bold">Continue Shopping</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($cart_items as $item): ?>
                        <div class="bg-white rounded-2xl shadow flex items-center px-6 py-4 gap-6 relative">
                            <label class="custom-checkbox mr-2">
                                <input type="checkbox" class="cart-checkbox" value="<?php echo $item['cart_id']; ?>" data-total="<?php echo $item['total_cost']; ?>" data-qty="<?php echo $item['qty']; ?>" checked>
                                <span class="checkmark">
                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M4 8.5L7 11.5L12 5.5" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                            </label>
                            <img src="<?php echo htmlspecialchars($item['product_images']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="w-24 h-24 object-contain rounded-lg bg-gray-50">
                            <div class="flex-1">
                                <div class="font-bold text-lg mb-1" style="font-family: 'Koulen', sans-serif;">
                                    <?php echo htmlspecialchars($item['name']); ?>
                                </div>
                                <div class="text-primary text-xl font-bold mb-2">
                                    $ <?php echo number_format($item['price'], 2); ?>
                                </div>
                            </div>
                            <div class="flex flex-col items-center gap-2">
                                <span class="text-xs text-gray-500">Quantity</span>
                                <div class="flex items-center gap-1">
                                    <button type="button" class="qty-btn px-2 py-1 rounded bg-gray-200 text-lg font-bold" onclick="changeQty(this, -1)">−</button>
                                    <input type="number" name="quantity" value="<?php echo $item['qty']; ?>" min="1" max="<?php echo $item['no_of_products']; ?>" data-stock="<?php echo $item['no_of_products']; ?>" class="w-12 text-center rounded bg-gray-200 py-1 px-2 cart-qty-input" data-cart-id="<?php echo $item['cart_id']; ?>">
                                    <button type="button" class="qty-btn px-2 py-1 rounded bg-gray-200 text-lg font-bold" onclick="changeQty(this, 1)">+</button>
                                </div>
                                <form action="backend/updateCart.php" method="POST" class="flex items-center cart-qty-form" style="display:none;">
                                    <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                    <input type="hidden" name="quantity" value="<?php echo $item['qty']; ?>">
                                </form>
                            </div>
                            <form action="backend/removeFromCart.php" method="POST" class="ml-4 remove-form">
                                <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                <button type="button" class="delete-btn w-9 h-9 flex items-center justify-center rounded-full bg-red-100 hover:bg-red-500 transition text-red-500 hover:text-white text-xl shadow">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                    <div class="flex justify-end mt-4">
                        <button id="selectAllBtn" type="button" class="border-2 border-primary text-primary rounded-full px-8 py-2 font-bold flex items-center gap-2 hover:bg-primary hover:text-white transition">
                            UNSELECT ALL <i class="fas fa-times"></i>
                        </button>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Sidebar Section -->
            <aside class="w-full lg:w-96 flex-shrink-0">
                <div class="bg-white rounded-2xl shadow p-8 flex flex-col gap-6">
                    <div>
                        <h3 class="font-bold text-lg mb-2" style="font-family: 'Koulen', sans-serif;">LOCATION</h3>
                        <div class="flex items-center gap-2 text-gray-600 mb-2">
                            <img src="Images/pin.png" alt="Pin" class="w-5 h-5">
                            <span><?php echo htmlspecialchars($user_address); ?></span>
                            <a href="user_profile.php" class="ml-auto text-primary text-sm hover:underline">Change</a>
                        </div>
                    </div>
                    <div>
                        <h3 class="font-bold text-lg mb-2" style="font-family: 'Koulen', sans-serif;">ORDER SUMMARY</h3>
                        <div class="flex justify-between text-gray-700 mb-1">
                            <span>Number of Items</span>
                            <span id="orderItems"><?php echo $total_items; ?></span>
                        </div>
                        <div class="flex justify-between text-gray-700 mb-4">
                            <span>Total</span>
                            <span id="orderTotal">$ <?php echo number_format($total_cost, 2); ?></span>
                        </div>
                    </div>
                    <?php if (!empty($cart_items)): ?>
                        <form id="checkoutForm" action="customer/customerPayment.php" method="POST">
                            <input type="hidden" name="total_amount" value="<?php echo $total_cost; ?>">
                            <input type="hidden" name="cart_items" value='<?php echo json_encode($cart_items, JSON_HEX_APOS | JSON_HEX_QUOT); ?>'>
                            <button type="submit" class="w-full bg-primary text-white py-4 rounded-lg text-lg font-bold hover:bg-orange-600 transition">PROCEED TO CHECKOUT</button>
                        </form>
                    <?php endif; ?>
                </div>
            </aside>
            </div>
    </main>

    <footer class="bg-white py-4 px-24 flex justify-between items-center">
        <div class="flex gap-4">
            <img src="../Images/facebook.png" alt="Facebook" class="w-7 h-7 hover:scale-110 transition-transform duration-300">
            <img src="../Images/instagram.png" alt="Instagram" class="w-7 h-7 hover:scale-110 transition-transform duration-300">
            <img src="../Images/twitter.png" alt="Twitter" class="w-7 h-7 hover:scale-110 transition-transform duration-300">
        </div>
        <p class="text-gray-600">© CeylonCart 2025</p>
    </footer>

    <div id="loadingOverlay" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;z-index:9999;background:rgba(255,255,255,0.8);align-items:center;justify-content:center;">
        <div style="text-align:center;">
            <div class="loader" style="border:8px solid #f3f3f3;border-top:8px solid #F35821;border-radius:50%;width:60px;height:60px;animation:spin 1s linear infinite;margin:auto;"></div>
            <p style="margin-top:20px;font-size:20px;color:#F35821;">Processing your order...</p>
        </div>
    </div>
    <style>
    @keyframes spin {
      0% { transform: rotate(0deg);}
      100% { transform: rotate(360deg);}
    }
    </style>

    <script>
        // Select All functionality
        const selectAllBtn = document.getElementById('selectAllBtn');
        const checkboxes = document.querySelectorAll('.cart-checkbox');
        let allSelected = true;

        selectAllBtn.addEventListener('click', function() {
            allSelected = !allSelected;
            checkboxes.forEach(cb => cb.checked = allSelected);
            selectAllBtn.innerHTML = allSelected ? 'UNSELECT ALL <i class="fas fa-times"></i>' : 'SELECT ALL <i class="fas fa-check"></i>';
            updateOrderTotal();
        });

        checkboxes.forEach(cb => {
            cb.addEventListener('change', updateOrderTotal);
        });

        function updateOrderTotal() {
            let total = 0;
            let items = 0;
            checkboxes.forEach(cb => {
                if (cb.checked) {
                    total += parseFloat(cb.getAttribute('data-total'));
                    items += parseInt(cb.getAttribute('data-qty'));
                }
            });
            document.getElementById('orderTotal').textContent = '$ ' + total.toFixed(2);
            document.getElementById('orderItems').textContent = items;
        }

        // Quantity increment/decrement logic
        function changeQty(btn, delta) {
            const input = btn.parentElement.querySelector('.cart-qty-input');
            let qty = parseInt(input.value);
            const max = parseInt(input.getAttribute('data-stock'));
            const min = parseInt(input.getAttribute('min'));
            if (delta === 1 && qty >= max) {
                Swal.fire({
                    icon: 'error',
                    title: 'Stock Limit',
                    text: 'You cannot add more than the available stock!',
                    confirmButtonColor: '#F35821'
                });
                return;
            }
            if (delta === -1 && qty <= min) {
                return;
            }
            qty += delta;
            input.value = qty;
            // Optionally, auto-submit the form or trigger an update
            // Find the hidden form and submit
            const form = btn.parentElement.parentElement.querySelector('.cart-qty-form');
            form.querySelector('input[name="quantity"]').value = qty;
            form.submit();
        }

        // Delete confirmation
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const form = btn.closest('form');
                Swal.fire({
                    title: 'Remove Item?',
                    text: 'Are you sure you want to remove this item from your cart?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#F35821',
                    cancelButtonColor: '#aaa',
                    confirmButtonText: 'Yes, remove it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });

        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            document.getElementById('loadingOverlay').style.display = 'flex';
        });
    </script>
</body>

</html>