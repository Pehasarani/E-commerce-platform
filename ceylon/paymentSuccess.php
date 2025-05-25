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
    error_log("Error: " . $e->getMessage());
    header("Location: supplierDashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Success - Supplier Center</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="dashboard-body">
    <nav class="navbar">
        <div class="navbar-logo">
            <img src="./images/logo.png" alt="Logo" class="logo">
        </div>
        <span class="navbar-text">Supplier Center</span>
        <div class="navbar-profile">
            <i class="fas fa-user-circle profile-icon"></i>
            <span><?php echo htmlspecialchars($supplier['business_name'] ?? 'Supplier'); ?></span>
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
            <div class="flex flex-col items-center justify-center min-h-[80vh]">
                <div class="bg-white rounded-lg shadow-lg p-8 max-w-md w-full text-center">
                    <div class="mb-6">
                        <i class="fas fa-check-circle text-green-500 text-6xl mb-4"></i>
                        <h1 class="text-3xl font-bold text-gray-800 mb-2">Payment Successful!</h1>
                        <p class="text-gray-600 mb-6">Your payment of LKR 10,000 has been processed successfully.</p>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-gray-600">Payment Details:</p>
                            <p class="font-semibold">Amount: LKR 10,000</p>
                            <p class="font-semibold">Date: <?php echo date('Y-m-d H:i:s'); ?></p>
                            <p class="font-semibold">Status: Completed</p>
                        </div>
                        
                        <div class="flex flex-col space-y-3">
                            <a href="supplierDashboard.php" 
                                class="inline-block w-full bg-[#FF6F00] hover:bg-[#ff4b00] text-white font-bold py-3 px-6 rounded-lg transition duration-300">
                                <i class="fas fa-arrow-left mr-2"></i>
                                Return to Dashboard
                            </a>

                            <a href="generate_invoice.php" 
                                class="inline-block w-full bg-green-500 hover:bg-green-600 text-white font-bold py-3 px-6 rounded-lg transition duration-300">
                                <i class="fas fa-download mr-2"></i>
                                Download Invoice
                            </a>
                            
                            <a href="supplierLogout.php" 
                                class="inline-block w-full bg-gray-600 hover:bg-gray-700 text-white font-bold py-3 px-6 rounded-lg transition duration-300">
                                <i class="fas fa-sign-out-alt mr-2"></i>
                                Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html> 