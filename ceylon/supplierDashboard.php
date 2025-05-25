<?php
session_start();
include('connection.php');

// Check if supplier is logged in
if (!isset($_SESSION['supplier_id'])) {
    header("Location: supplierLogin.php");
    exit();
}

// Get supplier details
$supplier_id = $_SESSION['supplier_id'];
$business_email = $_SESSION['business_email']; // Make sure this is set during login
$supplier = null;
$orders = null;
$products = null;

try {
    // Get supplier details
    $stmt = $conn->prepare("SELECT business_name FROM suppliers WHERE supplier_id = ?");
    if (!$stmt) {
        throw new Exception("Error preparing supplier query: " . $conn->error);
    }
    $stmt->bind_param("i", $supplier_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $supplier = $result->fetch_assoc();
    $stmt->close();

    // Get all orders for this supplier
    $stmt = $conn->prepare("SELECT o.* 
                           FROM orders o 
                           JOIN order_items oi ON o.id = oi.order_id 
                           JOIN product p ON oi.product_id = p.product_id 
                           WHERE p.top_email = ?
                           GROUP BY o.id");
    if (!$stmt) {
        throw new Exception("Error preparing orders query: " . $conn->error);
    }
    $stmt->bind_param("s", $business_email);
    $stmt->execute();
    $orders = $stmt->get_result();
    $stmt->close();

    // Get all products for this supplier
    $stmt = $conn->prepare("SELECT `product_id`, `name`, `description`, `weight`, `price`, `no_of_products`, `product_images` 
                           FROM `product` 
                           WHERE top_email = ?");
    if (!$stmt) {
        throw new Exception("Error preparing products query: " . $conn->error);
    }
    $stmt->bind_param("s", $business_email);
    $stmt->execute();
    $products = $stmt->get_result();
    $stmt->close();

} catch (Exception $e) {
    error_log("Dashboard Error: " . $e->getMessage());
    $orders = [];
    $products = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        .custom-bg {
            background-color: #333;
        }
        .custom-text {
            color: #fff;
        }
        .custom-border {
            border-color: #FF6F00;
        }
        .custom-hover {
            background-color: #ff4b00;
        }
        .logo-container { display: flex; align-items: center; }
        .logo { width: 120px; height: auto; }
        .navbar { background: #222; color: #fff; display: flex; align-items: center; justify-content: space-between; padding: 12px 32px; }
        .navbar-text { font-size: 1.3rem; font-weight: bold; letter-spacing: 1px; }
        .navbar-profile { display: flex; align-items: center; gap: 10px; font-size: 1.1rem; }
        .profile-icon { font-size: 1.7rem; }
    </style>
    
</head>
<body class="dashboard-body">
    
    <nav class="navbar">
        <div class="logo-container">
            <img src="./images/suplogo22.png" alt="CeylonCart Logo" class="logo">
        </div>
        <span class="navbar-text">Supplier Center</span>
        <div class="navbar-profile">
            <i class="fas fa-user-circle profile-icon"></i>
            <span><?php echo htmlspecialchars($_SESSION['business_name'] ?? 'Supplier'); ?></span>
        </div>
    </nav>

    <div class="dashboard-container">
        <aside class="sidebar">
            <ul class="sidebar-menu">
                <li class="sidebar-item">
                    <a href="supplierDashboard.php" style="text-decoration: none; color: white;">
                        <i class="fas fa-tachometer-alt sidebar-icon"></i>
                        <span class="sidebar-text">Dashboard</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="supplierAddProduct.php" style="text-decoration: none; color: white;">
                        <i class="fas fa-plus-circle sidebar-icon"></i>
                        <span class="sidebar-text">Add Product</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="supplierNotification.php" style="text-decoration: none; color: white;">
                        <i class="fas fa-bell sidebar-icon"></i>
                        <span class="sidebar-text">Order Notification</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="supplierPayment.php" style="text-decoration: none; color: white;">
                        <i class="fas fa-credit-card sidebar-icon"></i>
                        <span class="sidebar-text">Payment</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="supplierProfile.php" style="text-decoration: none; color: white;">
                        <i class="fas fa-user sidebar-icon"></i>
                        <span class="sidebar-text">Profile</span>
                    </a>
                </li>
                <li class="sidebar-item mt-auto">
                    <a href="supplierLogout.php" style="text-decoration: none; color: white;">
                        <i class="fas fa-sign-out-alt sidebar-icon"></i>
                        <span class="sidebar-text">Logout</span>
                    </a>
                </li>
            </ul>
        </aside>

        <main class="main-content p-6">
            <?php if (isset($_GET['message'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($_GET['message']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <div class="header-container">
                <img src="./images/spicy.png" alt="Spice Hut Logo" class="header-logo">
                <h1><?php echo htmlspecialchars($supplier['business_name'] ?? 'Supplier'); ?></h1>
            </div>

            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Dashboard Overview</h1>
                <div class="flex space-x-4">
                    <a href="generate_report.php" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center space-x-2">
                        <i class="fas fa-download"></i>
                        <span>Download Report</span>
                    </a>
                    <a href="generate_orders_report.php" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg flex items-center space-x-2">
                        <i class="fas fa-file-alt"></i>
                        <span>Download Orders</span>
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Total Orders Card -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Total Orders</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $orders ? $orders->num_rows : 0; ?></h3>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-full">
                            <i class="fas fa-shopping-cart text-blue-500 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="supplierNotification.php" class="text-blue-500 hover:text-blue-700 text-sm flex items-center">
                            View Orders <i class="fas fa-arrow-right ml-2"></i>
                        </a>
                    </div>
                </div>

                <!-- Total Products Card -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Total Products</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $products ? $products->num_rows : 0; ?></h3>
                        </div>
                        <div class="bg-green-100 p-3 rounded-full">
                            <i class="fas fa-box text-green-500 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="supplierAddProduct.php" class="text-green-500 hover:text-green-700 text-sm flex items-center">
                            Manage Products <i class="fas fa-arrow-right ml-2"></i>
                        </a>
                    </div>
                </div>

                <!-- Total Users Card -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Total Users</p>
                            <h3 class="text-2xl font-bold text-gray-800">
                                <?php 
                                $users_query = "SELECT COUNT(*) as total FROM customers";
                                $users_result = $conn->query($users_query);
                                $users_count = $users_result->fetch_assoc()['total'];
                                echo $users_count;
                                ?>
                            </h3>
                        </div>
                        <div class="bg-purple-100 p-3 rounded-full">
                            <i class="fas fa-users text-purple-500 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="#" class="text-purple-500 hover:text-purple-700 text-sm flex items-center">
                            View Users <i class="fas fa-arrow-right ml-2"></i>
                        </a>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold">ALL PRODUCTS</h2>
                    <a href="supplierAddProduct.php" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg flex items-center space-x-2">
                        <i class="fas fa-plus"></i>
                        <span>ADD PRODUCTS</span>
                    </a>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php if ($products && $products->num_rows > 0): ?>
                        <?php while ($product = $products->fetch_assoc()): ?>
                            <div class="bg-gray-50 rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                                <img src="<?php echo htmlspecialchars($product['product_images']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="w-full h-48 object-cover">
                                <div class="p-4">
                                    <h3 class="text-lg font-semibold mb-2"><?php echo htmlspecialchars($product['name']); ?></h3>
                                    <p class="text-gray-600 text-sm mb-2"><?php echo htmlspecialchars($product['description']); ?></p>
                                    <div class="grid grid-cols-2 gap-2 text-sm">
                                        <p class="text-gray-600">Weight: <span class="font-medium"><?php echo htmlspecialchars($product['weight']); ?></span></p>
                                        <p class="text-sm text-gray-600">Price: <span class="font-semibold">$<?php echo number_format($product['price'], 2); ?></span></p>
                                        <p class="text-gray-600">Stock: <span class="font-medium"><?php echo htmlspecialchars($product['no_of_products']); ?></span></p>
                                    </div>
                                    <div class="mt-4 flex justify-end space-x-2">
                                        <a href="editProduct.php?id=<?php echo $product['product_id']; ?>" class="text-blue-500 hover:text-blue-700">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="deleteProduct.php?id=<?php echo $product['product_id']; ?>" class="text-red-500 hover:text-red-700" onclick="return confirm('Are you sure you want to delete this product?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-span-full text-center py-8 text-gray-500">
                            <p>No products found</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
