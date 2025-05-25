<?php
// Include the database connection file
include('connection.php');
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please login to add items to cart";
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle Add to Cart form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    try {
        // Get product details from form
        $product_id = $_POST['product_id'];
        $quantity = (int)$_POST['quantity'];
        $product_price = (float)$_POST['product_price'];
        
        // Validate inputs
        if (empty($product_id) || $quantity <= 0 || $product_price <= 0) {
            throw new Exception("Invalid product information");
        }
        
        // Calculate total cost
        $total_cost = $quantity * $product_price;
        
        // Check if product exists and has enough stock
        $check_product = "SELECT product_id, no_of_products, name FROM product WHERE product_id = '$product_id'";
        $product_result = $conn->query($check_product);
        
        if (!$product_result) {
            throw new Exception("Database error: " . $conn->error);
        }
        
        if ($product_result->num_rows == 0) {
            throw new Exception("Product not found");
        }
        
        $product = $product_result->fetch_assoc();
        
        // Check if product already exists in cart
        $check_cart = "SELECT * FROM cart WHERE product_product_id = '$product_id' AND userid = '$user_id'";
        $cart_result = $conn->query($check_cart);
        
        if (!$cart_result) {
            throw new Exception("Database error checking cart: " . $conn->error);
        }
        
        if ($cart_result->num_rows > 0) {
            // Product already in cart, update quantity
            $cart_item = $cart_result->fetch_assoc();
            $new_quantity = $cart_item['qty'] + $quantity;
            
            $new_total = $new_quantity * $product_price;
            
            $update_cart = "UPDATE cart 
                           SET qty = '$new_quantity', total_cost = '$new_total' 
                           WHERE product_product_id = '$product_id' AND userid = '$user_id'";
            
            if ($conn->query($update_cart) === TRUE) {
                $_SESSION['alert'] = [
                    'type' => 'success',
                    'title' => 'Success!',
                    'message' => 'Cart updated successfully! ' . $product['name'] . ' is added to the cart'
                ];
            } else {
                throw new Exception("Error updating cart: " . $conn->error);
            }
        } else {
            // Product not in cart, insert new record
            $insert_cart = "INSERT INTO cart (userid, qty, total_cost, product_product_id) 
                           VALUES ('$user_id', '$quantity', '$total_cost', '$product_id')";

            if ($conn->query($insert_cart) === TRUE) {
                $_SESSION['alert'] = [
                    'type' => 'success',
                    'title' => 'Success!',
                    'message' => 'Cart updated successfully! ' . $product['name'] . ' is added to the cart'
                ];
            } else {
                throw new Exception("Error adding product to cart: " . $conn->error);
            }
        }
        
    } catch (Exception $e) {
        $_SESSION['alert'] = [
            'type' => 'error',
            'title' => 'Error!',
            'message' => $e->getMessage()
        ];
    }
}

// Query to fetch all products
$sql = "SELECT product_id, name, description, weight, price, no_of_products, product_images FROM product";
$result = $conn->query($sql);

// Get unique categories for filter
$categories = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row['name']; // Using product name as category for now
    }
}
$categories = array_unique($categories);
$result->data_seek(0); // Reset result pointer
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product View</title>
    <link href="https://fonts.googleapis.com/css2?family=Koulen&family=Abel&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <!-- Add SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .swal2-confirm {
            border-radius: 20px !important;
            padding: 12px 30px !important;
            font-size: 16px !important;
            font-weight: 500 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
            transition: all 0.3s ease !important;
        }
        
        .swal2-confirm:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1) !important;
        }
        
        .swal2-popup {
            border-radius: 16px !important;
        }
    </style>
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

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
        top: 0;
        z-index: 100;
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

        .page-title {
        font-size: 22px;
        color: #2B2B2B; 
        margin-bottom: 24px;
        font-family: 'Koulen', sans-serif;
        text-align: left; 
        padding-left: 16px; 
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

        /* Toast Notification Styles */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }

        .toast {
            background: #333;
            color: white;
            padding: 12px 24px;
            border-radius: 4px;
            margin-bottom: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-width: 300px;
            transform: translateX(120%);
            transition: transform 0.3s ease-in-out;
        }

        .toast.show {
            transform: translateX(0);
        }

        .toast.success {
            background: #4CAF50;
        }

        .toast.error {
            background: #f44336;
        }

        .toast-message {
            margin-right: 20px;
        }

        .toast-close {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            font-size: 20px;
            padding: 0;
            line-height: 1;
        }

    </style>
</head>
<body class="bg-gray-50">
    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- Alert for successful cart operation -->
    <?php if(isset($_SESSION['show_alert']) && $_SESSION['show_alert']): ?>
    <script>
        alert("<?php echo $_SESSION['alert_message']; ?>");
    </script>
    <?php 
        // Clear the flag and message
        unset($_SESSION['show_alert']);
        unset($_SESSION['alert_message']);
    ?>
    <?php endif; ?>
    
    <header class="header">

        <a href = "product_view.php">
            <div class="logo-container">
                <img src="Images/LogoVertical.png" alt="CeylonCart Logo" class="logo">
            </div>
        </a>

        <div class="search-bar">
                <input type="text" id="searchFilter" placeholder="Search products..." class="search-input">
        </div>
            
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

    <main class="container mx-auto px-4 py-8">
    <section class="bg-white rounded-2xl shadow-lg p-8">
            <h2 class="page-title">JUST FOR YOU</h2>

            <!-- Filters Section -->
            <div class="mb-8 grid grid-cols-1 md:grid-cols-4 gap-4">
                
                
                <!-- Category Filter -->
                <div>
                    <select id="categoryFilter" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Price Range Filter --> 
                <div>
                    <select id="priceFilter" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="">Price Range</option>
                        <option value="0-10">0 $ - 10 $</option>
                        <option value="10-50">10 $ - 50 $</option>
                        <option value="50-100">50 $ - 100 $</option>
                        <option value="100-10000">100 $ +</option>
                    </select>
                </div>
                
                <!-- Stock Status Filter -->
                <div>
                    <select id="stockFilter" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="">Stock Status</option>
                        <option value="in_stock">In Stock</option>
                        <option value="low_stock">Low Stock</option>
                        <option value="out_of_stock">Out of Stock</option>
                    </select>
                </div>
            </div>

            <div id="productGrid" class="grid grid-cols-4 gap-6">
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $stockStatus = $row['no_of_products'] > 10 ? 'in_stock' : ($row['no_of_products'] > 0 ? 'low_stock' : 'out_of_stock');
                        ?>
                        <div class="product-card bg-white rounded-xl shadow-md hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2 relative overflow-hidden group"
                             data-name="<?php echo htmlspecialchars($row['name']); ?>"
                             data-category="<?php echo htmlspecialchars($row['name']); ?>"
                             data-price="<?php echo $row['price']; ?>"
                             data-stock="<?php echo $stockStatus; ?>">
                            <div class="absolute inset-0 bg-gradient-to-br from-primary/10 to-primary/5 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                            
                            <div class="h-56 overflow-hidden rounded-t-xl">
                                <img src="<?php echo $row['product_images']; ?>" 
                                     alt="<?php echo $row['name']; ?>" 
                                     class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
                            </div>
                            
                            <div class="p-4">
                                <h2 class="text-lg font-semibold text-gray-800 mb-2"><?php echo $row['name']; ?></h2>
                                <p class="text-sm text-gray-600 mb-2"><?php echo $row['description']; ?></p>
                                <p class="text-sm text-gray-600"><strong>Weight:</strong> <?php echo $row['weight']; ?>g</p>
                                <p class="text-primary text-lg font-bold my-3">$ <?php echo $row['price']; ?></p>
                                <p class="text-sm text-gray-600"><strong>Available Stock:</strong> <?php echo $row['no_of_products']; ?></p>

                                <?php if ($row['no_of_products'] > 0): ?>
                                    <form method="POST" action="">
                                        <input type="hidden" name="product_id" value="<?php echo $row['product_id']; ?>">
                                        <input type="hidden" name="product_price" value="<?php echo $row['price']; ?>">
                                        <input type="hidden" name="quantity" value="1">
                                        <input type="hidden" name="add_to_cart" value="1">
                                        
                                        <button type="submit" 
                                                class="w-full bg-primary text-white py-2 px-4 rounded-lg flex items-center justify-center gap-2 hover:bg-primary/90 transition-colors duration-300 relative overflow-hidden group mt-4">
                                            <span>ADD TO CART</span>
                                            <i class="fas fa-shopping-cart"></i>
                                            <div class="absolute inset-0 bg-white/20 -translate-x-full group-hover:translate-x-full transition-transform duration-500"></div>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <div class="mt-4">
                                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg flex items-center gap-2">
                                            <i class="fas fa-times-circle"></i>
                                            <span>Out of Stock</span>
                                        </div>
                                        <button disabled 
                                                class="w-full bg-gray-300 text-gray-500 py-2 px-4 rounded-lg flex items-center justify-center gap-2 mt-4 cursor-not-allowed">
                                            <span>OUT OF STOCK</span>
                                            <i class="fas fa-shopping-cart"></i>
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    echo "<p class='col-span-4 text-center text-gray-600'>No products available</p>";
                }
                ?>
            </div>
        </section>
    </main>

    <footer class="bg-white py-4 px-24 flex justify-between items-center">
        <div class="flex gap-4">
            <img src="Images/facebook.png" alt="Facebook" class="w-7 h-7 hover:scale-110 transition-transform duration-300">
            <img src="Images/instagram.png" alt="Instagram" class="w-7 h-7 hover:scale-110 transition-transform duration-300">
            <img src="Images/twitter.png" alt="Twitter" class="w-7 h-7 hover:scale-110 transition-transform duration-300">
        </div>
        <p class="text-gray-600">© CeylonCart 2025</p>
    </footer>

    <!-- Add jQuery library -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchFilter = document.getElementById('searchFilter');
            const categoryFilter = document.getElementById('categoryFilter');
            const priceFilter = document.getElementById('priceFilter');
            const stockFilter = document.getElementById('stockFilter');
            const productGrid = document.getElementById('productGrid');
            const products = document.querySelectorAll('.product-card');

            function filterProducts() {
                const searchTerm = searchFilter.value.toLowerCase();
                const selectedCategory = categoryFilter.value;
                const selectedPrice = priceFilter.value;
                const selectedStock = stockFilter.value;

                products.forEach(product => {
                    const name = product.dataset.name.toLowerCase();
                    const category = product.dataset.category;
                    const price = parseFloat(product.dataset.price);
                    const stock = product.dataset.stock;

                    let matchesSearch = name.includes(searchTerm);
                    let matchesCategory = !selectedCategory || category === selectedCategory;
                    let matchesPrice = true;
                    let matchesStock = !selectedStock || stock === selectedStock;

                    if (selectedPrice) {
                        const [min, max] = selectedPrice.split('-');
                        if (max === '+') {
                            matchesPrice = price >= parseFloat(min);
                        } else {
                            matchesPrice = price >= parseFloat(min) && price <= parseFloat(max);
                        }
                    }

                    if (matchesSearch && matchesCategory && matchesPrice && matchesStock) {
                        product.style.display = 'block';
                    } else {
                        product.style.display = 'none';
                    }
                });

                // Show message if no products match the filters
                const visibleProducts = document.querySelectorAll('.product-card[style="display: block"]');
                const noResultsMessage = document.querySelector('.no-results-message');
                
                if (visibleProducts.length === 0) {
                    if (!noResultsMessage) {
                        const message = document.createElement('p');
                        message.className = 'col-span-4 text-center text-gray-600 no-results-message';
                        message.textContent = 'No products match your filters';
                        productGrid.appendChild(message);
                    }
                } else if (noResultsMessage) {
                    noResultsMessage.remove();
                }
            }

            // Add event listeners for all filters
            searchFilter.addEventListener('input', filterProducts);
            categoryFilter.addEventListener('change', filterProducts);
            priceFilter.addEventListener('change', filterProducts);
            stockFilter.addEventListener('change', filterProducts);
        });

        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.innerHTML = `
                <span class="toast-message">${message}</span>
                <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
            `;
            
            document.getElementById('toastContainer').appendChild(toast);
            
            // Trigger animation
            setTimeout(() => toast.classList.add('show'), 100);
            
            // Remove toast after 3 seconds
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        function addToCart(event, form) {
            event.preventDefault();
            
            $.ajax({
                url: 'backend/cartProcess.php',
                type: 'POST',
                data: $(form).serialize(),
                dataType: 'json',
                success: function(response) {
                    Swal.fire({
                        title: response.success ? 'Success!' : 'Error!',
                        text: response.message,
                        icon: response.success ? 'success' : 'error',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#F35821'
                    });
                },
                error: function() {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Failed to add product to cart. Please try again.',
                        icon: 'error',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#F35821'
                    });
                }
            });
            
            return false;
        }
    </script>

    <!-- Add this right after the header section -->
    <?php if (isset($_SESSION['alert'])): ?>
    <script>
        Swal.fire({
            title: '<?php echo $_SESSION['alert']['title']; ?>',
            text: '<?php echo $_SESSION['alert']['message']; ?>',
            icon: '<?php echo $_SESSION['alert']['type']; ?>',
            confirmButtonText: 'OK',
            confirmButtonColor: '#F35821'
        });
    </script>
    <?php 
        // Clear the alert after showing it
        unset($_SESSION['alert']);
    endif; 
    ?>

    <!-- Add this just before the closing </body> tag -->
    <div id="loadingOverlay" style="display:none; position:fixed; z-index:9999; top:0; left:0; width:100vw; height:100vh; background:rgba(255,255,255,0.8); align-items:center; justify-content:center;">
      <div>
        <div class="animate-spin rounded-full h-16 w-16 border-t-4 border-b-4 border-primary mx-auto"></div>
        <p class="mt-4 text-xl font-semibold text-primary text-center">Loading...</p>
      </div>
    </div>
</body>
</html>

<?php
// Close the database connection
$conn->close();
?>
