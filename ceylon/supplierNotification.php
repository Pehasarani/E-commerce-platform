<?php
session_start();
include('connection.php');

// Check if supplier is logged in
if (!isset($_SESSION['supplier_id'])) {
    header("Location: supplierLogin.php");
    exit();
}

$supplier_id = $_SESSION['supplier_id'];
$supplier = null;

// Handle status update form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['order_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = $_POST['order_status'];
    // Only allow valid statuses
    $allowed_statuses = ['Pending', 'In Transit', 'Complete'];
    if (in_array($new_status, $allowed_statuses)) {
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $order_id);
        $stmt->execute();
        $stmt->close();
    }
    // Refresh to show updated status
    header("Location: supplierNotification.php");
    exit();
}

// Fetch supplier details
try {
    $stmt = $conn->prepare("SELECT `supplier_id`, `business_name`, `business_email`, `password`, `business_address`, `contact_number`, `created_at`, `updated_at` FROM `suppliers` WHERE supplier_id = ?");
    if (!$stmt) {
        throw new Exception("Error preparing supplier query: " . $conn->error);
    }
    $stmt->bind_param("i", $supplier_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $supplier = $result->fetch_assoc();
    $stmt->close();
} catch (Exception $e) {
    error_log("Notification Error: " . $e->getMessage());
}

// Fetch all orders with customer details
try {
    $orders_query = "SELECT o.id as order_id, 
                            o.user_id, 
                            o.amount, 
                            o.status, 
                            o.created_at, 
                            c.username as customer_name, 
                            c.email, 
                            c.phone_personal as phone,
                            c.address,
                            c.postal_code,
                            c.country,
                            GROUP_CONCAT(p.name) as products,
                            GROUP_CONCAT(oi.quantity) as quantities
                     FROM orders o
                     JOIN customers c ON o.user_id = c.id
                     JOIN order_items oi ON o.id = oi.order_id
                     JOIN product p ON oi.product_id = p.product_id
                     WHERE p.top_email = ?
                     GROUP BY o.id
                     ORDER BY o.created_at DESC";
    
    $stmt = $conn->prepare($orders_query);
    $stmt->bind_param("s", $_SESSION['business_email']);
    $stmt->execute();
    $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Exception $e) {
    error_log("Notification Error: " . $e->getMessage());
    $orders = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Notifications - CeylonCart</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .order-card {
            transition: transform 0.2s;
            border: 1px solid #e5e7eb;
        }
        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .status-pending {
            background-color: #FEF3C7;
            color: #92400E;
        }
        .status-in-transit {
            background-color: #DBEAFE;
            color: #1E40AF;
        }
        .status-complete {
            background-color: #D1FAE5;
            color: #065F46;
        }
        .border-b {
            border-bottom: 1px solid #e5e7eb;
        }
        .border-t {
            border-top: 1px solid #e5e7eb;
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
            </ul>
        </aside>

        <main class="main-content p-6">
            <div class="header-container">
                <img src="./images/spicy.png" alt="Spice Hut Logo" class="header-logo">
                <h1>Order Notifications</h1>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-6">
                <?php foreach ($orders as $order): ?>
                    <div class="bg-white rounded-lg shadow-md p-6 order-card">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800">Order #<?php echo htmlspecialchars($order['order_id']); ?></h3>
                                <p class="text-sm text-gray-500"><?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></p>
                            </div>
                            <form method="post" action="" id="status-form-<?php echo $order['order_id']; ?>" style="display:inline;">
                                <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                <select name="order_status" class="status-badge <?php echo 'status-' . str_replace(' ', '-', strtolower($order['status'])); ?>" style="margin-right:8px;">
                                    <option value="Pending" <?php if ($order['status'] === 'Pending') echo 'selected'; ?>>Pending</option>
                                    <option value="In Transit" <?php if ($order['status'] === 'In Transit') echo 'selected'; ?>>In Transit</option>
                                    <option value="Complete" <?php if ($order['status'] === 'Complete') echo 'selected'; ?>>Complete</option>
                                </select>
                            </form>
                        </div>
                        <div class="space-y-3">
                            <div class="border-b pb-3">
                                <h4 class="text-sm font-semibold text-gray-700 mb-2">Customer Details</h4>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($order['customer_name']); ?></p>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($order['email']); ?></p>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($order['phone']); ?></p>
                            </div>

                            <div class="border-b pb-3">
                                <h4 class="text-sm font-semibold text-gray-700 mb-2">Delivery Address</h4>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($order['address']); ?></p>
                                <p class="text-sm text-gray-600">
                                    <?php echo htmlspecialchars($order['postal_code']); ?>, 
                                    <?php echo htmlspecialchars($order['country']); ?>
                                </p>
                            </div>

                            <div>
                                <h4 class="text-sm font-semibold text-gray-700 mb-2">Order Details</h4>
                                <?php
                                $products_array = explode(',', $order['products']);
                                $quantities_array = explode(',', $order['quantities']);
                                for ($i = 0; $i < count($products_array); $i++): ?>
                                    <p class="text-sm text-gray-600">
                                        <?php echo htmlspecialchars($products_array[$i]); ?> 
                                        <span class="font-medium">×<?php echo htmlspecialchars($quantities_array[$i]); ?></span>
                                    </p>
                                <?php endfor; ?>
                            </div>

                            <div class="mt-4 pt-3 border-t">
                                <p class="text-right font-semibold text-gray-800">
                                    Total: $ <?php echo number_format($order['amount'], 2); ?>
                                </p>
                            </div>

                            <div style="margin-top: 18px; text-align: right;">
                                <button type="submit" form="status-form-<?php echo $order['order_id']; ?>" class="px-3 py-1 rounded bg-orange-500 text-white font-semibold">Update</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
</body>
</html>

