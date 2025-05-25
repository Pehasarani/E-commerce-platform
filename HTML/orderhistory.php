<?php
require "backend/dbConnect.php";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get current user ID from session
$user_id = $_SESSION['user_id'];

// Get filter values from GET
$item_name = isset($_GET['item_name']) ? trim($_GET['item_name']) : '';
$start_date = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';
$min_value = isset($_GET['min_value']) ? trim($_GET['min_value']) : '';
$max_value = isset($_GET['max_value']) ? trim($_GET['max_value']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';

// Build the base query
$query = "SELECT o.*, 
          GROUP_CONCAT(p.name SEPARATOR '||') as product_names,
          GROUP_CONCAT(oi.quantity SEPARATOR '||') as quantities,
          GROUP_CONCAT(oi.price SEPARATOR '||') as prices,
          GROUP_CONCAT(p.product_images SEPARATOR '||') as product_images
          FROM orders o 
          LEFT JOIN order_items oi ON o.id = oi.order_id 
          LEFT JOIN product p ON oi.product_id = p.product_id 
          WHERE o.user_id = ?";

// Add filter conditions
$params = [$user_id];
$types = "i";

if (!empty($status)) {
    $query .= " AND o.status = ?";
    $params[] = $status;
    $types .= "s";
}

if (!empty($item_name)) {
    $query .= " AND p.name LIKE ?";
    $params[] = "%$item_name%";
    $types .= "s";
}

if (!empty($start_date)) {
    $query .= " AND DATE(o.created_at) >= ?";
    $params[] = $start_date;
    $types .= "s";
}

if (!empty($end_date)) {
    $query .= " AND DATE(o.created_at) <= ?";
    $params[] = $end_date;
    $types .= "s";
}

if (!empty($min_value)) {
    $query .= " AND o.amount >= ?";
    $params[] = $min_value;
    $types .= "d";
}

if (!empty($max_value)) {
    $query .= " AND o.amount <= ?";
    $params[] = $max_value;
    $types .= "d";
}

$query .= " GROUP BY o.id ORDER BY o.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History - Ceylon Cart</title>
    <link href="https://fonts.googleapis.com/css2?family=Koulen&family=Abel&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            font-family: 'Abel', Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
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
        .order-history-section {
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            padding: 32px 32px 24px 32px;
            margin-top: 40px;
        }
        .filter-section {
            margin-bottom: 32px;
            background: #faf7f5;
            border-radius: 16px;
            padding: 20px 24px;
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
            align-items: flex-end;
        }
        .filter-section label {
            font-weight: bold;
            margin-right: 6px;
        }
        .filter-section input {
            padding: 6px 10px;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 15px;
        }
        .filter-section button {
            padding: 8px 18px;
            border-radius: 8px;
            font-weight: bold;
            background: #F35821;
            color: #fff;
            border: none;
            cursor: pointer;
        }
        .filter-section .reset-btn {
            background: #eee;
            color: #333;
            margin-left: 8px;
        }
        .back-row {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 22px;
            margin-bottom: 8px;
        }
        .back-row a {
            color: #222;
            text-decoration: none;
            display: flex;
            align-items: center;
        }
        .order-history-title {
            color: #F35821;
            font-family: 'Koulen', Arial, sans-serif;
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 8px;
            letter-spacing: 1px;
        }
        .divider {
            border-bottom: 1px solid #e5e5e5;
            margin-bottom: 24px;
        }
        .order-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
        }
        .order-table th {
            background: #F35821;
            color: #fff;
            font-weight: 600;
            padding: 14px 10px;
            text-align: left;
            font-size: 16px;
        }
        .order-table td {
            padding: 14px 10px;
            font-size: 15px;
            vertical-align: top;
        }
        .order-table tr:nth-child(even) {
            background: #faf7f5;
        }
        .order-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.5rem 0;
        }
        .order-item img {
            width: 50px;
            height: 50px;
            object-fit: contain;
            border-radius: 4px;
        }
        .no-orders {
            text-align: center;
            color: #888;
            padding: 40px 0;
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
        .status-cancelled {
            background-color: #FEE2E2;
            color: #991B1B;
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen">
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

    <main class="max-w-7xl mx-auto py-10 px-4">
        <section class="order-history-section">
            <div class="back-row">
                <a href="product_view.php"><i class="fa fa-arrow-left"></i></a>
                <span>Back</span>
            </div>
            <div class="order-history-title">ORDER HISTORY</div>
            <div class="divider"></div>

            <form class="filter-section" method="get" action="">
                <div>
                    <label for="status">Status:</label>
                    <select id="status" name="status" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="">All Status</option>
                        <option value="Pending" <?php echo $status === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="In Transit" <?php echo $status === 'In Transit' ? 'selected' : ''; ?>>In Transit</option>
                        <option value="Complete" <?php echo $status === 'Complete' ? 'selected' : ''; ?>>Complete</option>
                    </select>
                </div>
                <div>
                    <label for="item_name">Item Name:</label>
                    <input type="text" id="item_name" name="item_name" value="<?php echo htmlspecialchars($item_name); ?>" placeholder="Product name">
                </div>
                <div>
                    <label for="start_date">Start Date:</label>
                    <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                </div>
                <div>
                    <label for="end_date">End Date:</label>
                    <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                </div>
                <div>
                    <label for="min_value">Min Value:</label>
                    <input type="number" step="0.01" id="min_value" name="min_value" value="<?php echo htmlspecialchars($min_value); ?>" placeholder="Min total">
                </div>
                <div>
                    <label for="max_value">Max Value:</label>
                    <input type="number" step="0.01" id="max_value" name="max_value" value="<?php echo htmlspecialchars($max_value); ?>" placeholder="Max total">
                </div>
                <div class="flex gap-2">
                    <button type="submit">Filter</button>
                    <a href="orderhistory.php" class="reset-btn" style="padding:8px 18px; border-radius:8px; font-weight:bold; background:#eee; color:#333; text-decoration:none;">Reset</a>
                    <a href="generate_order_history.php?<?php echo http_build_query($_GET); ?>" 
                       class="bg-[#585858] text-white py-2 px-4 rounded-lg text-sm font-bold hover:bg-[#2b2b2b] transition">
                        <i class="fas fa-download mr-2"></i> Download PDF
                    </a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="order-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Status</th>
                            <th>Items</th>
                            <th>Date</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows === 0): ?>
                            <tr>
                                <td colspan="5" class="no-orders">
                                    No orders found.<br>
                                    <a href="product_view.php" class="back-link" style="color:#F35821;font-weight:bold;">Back to Shopping</a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php while ($order = $result->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $product_names = explode('||', $order['product_names']);
                                        $quantities = explode('||', $order['quantities']);
                                        $prices = explode('||', $order['prices']);
                                        $images = explode('||', $order['product_images']);
                                        
                                        for ($i = 0; $i < count($product_names); $i++) {
                                            if (!empty($product_names[$i])) {
                                                ?>
                                                <div class="order-item">
                                                    <?php if (isset($images[$i])): ?>
                                                        <img src="<?php echo htmlspecialchars($images[$i]); ?>" alt="<?php echo htmlspecialchars($product_names[$i]); ?>">
                                                    <?php endif; ?>
                                                    <div>
                                                        <div class="font-medium"><?php echo htmlspecialchars($product_names[$i]); ?></div>
                                                        <div class="text-sm text-gray-600">
                                                            Quantity: <?php echo $quantities[$i]; ?> × $<?php echo number_format($prices[$i], 2); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php
                                            }
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                    <td>$<?php echo number_format($order['amount'], 2); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <footer class="bg-white py-4 px-24 flex justify-between items-center">
        <div class="flex gap-4">
            <img src="../Images/facebook.png" alt="Facebook" class="w-7 h-7 hover:scale-110 transition-transform duration-300">
            <img src="../Images/instagram.png" alt="Instagram" class="w-7 h-7 hover:scale-110 transition-transform duration-300">
            <img src="../Images/twitter.png" alt="Twitter" class="w-7 h-7 hover:scale-110 transition-transform duration-300">
        </div>
        <p class="text-gray-600">© CeylonCart 2025</p>
    </footer>
</body>

</html>