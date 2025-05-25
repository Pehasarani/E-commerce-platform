<?php
session_start();
include('connection.php');

// Check if supplier is logged in
if (!isset($_SESSION['supplier_id'])) {
    header("Location: supplierLogin.php");
    exit();
}

$supplier_id = $_SESSION['supplier_id'];
$message = '';
$error = '';
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
    error_log("Payment Error: " . $e->getMessage());
    $error = "Error fetching supplier details";
}

// Fetch payment records for this supplier
$payments = [];
try {
    $stmt = $conn->prepare("SELECT * FROM `supplier_payment` WHERE `supplier_name` = ? ORDER BY `payment_date` DESC");
    $stmt->bind_param("s", $supplier['business_name']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $payments[] = $row;
    }
    $stmt->close();
} catch (Exception $e) {
    $error = "Error fetching payment records: " . $e->getMessage();
}

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $payment_id = $_GET['id'];
    try {
        $stmt = $conn->prepare("DELETE FROM `supplier_payment` WHERE `payment_id` = ? AND `supplier_name` = ?");
        $stmt->bind_param("is", $payment_id, $supplier['business_name']);
        if ($stmt->execute()) {
            $message = "Payment record deleted successfully!";
            // Redirect to refresh the page and prevent resubmission
            header("Location: managePayments.php?message=deleted");
            exit();
        } else {
            $error = "Error deleting payment: " . $stmt->error;
        }
        $stmt->close();
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Handle messages from redirects
if (isset($_GET['message'])) {
    if ($_GET['message'] == 'deleted') {
        $message = "Payment record deleted successfully!";
    } elseif ($_GET['message'] == 'updated') {
        $message = "Payment record updated successfully!";
    }
}

// Function to convert status code to text
function getStatusText($statusCode) {
    switch ($statusCode) {
        case 1:
            return "Completed";
        case 2:
            return "Pending";
        case 3:
            return "Failed";
        default:
            return "Unknown";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Payment Records</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

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
                    <a href="managePayments.php" style="text-decoration: none; color: white; background-color: rgba(255, 255, 255, 0.1);">
                        <i class="fas fa-money-bill-wave sidebar-icon"></i>
                        <span class="sidebar-text">Manage Payments</span>
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
                <h1>Manage Payment Records</h1>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6 mt-6">
                <?php if ($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <?php if ($message): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <div class="mb-4 flex justify-between items-center">
                    <h2 class="text-xl font-bold">Payment History</h2>
                    <a href="supplierPayment.php" class="bg-[#FF6F00] hover:bg-[#ff4b00] text-white font-bold py-2 px-4 rounded">
                        <i class="fas fa-plus mr-2"></i> New Payment
                    </a>
                </div>
                
                <?php if (empty($payments)): ?>
                    <div class="bg-gray-100 p-4 rounded text-center">
                        <p>No payment records found.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="py-2 px-4 border-b text-left">ID</th>
                                    <th class="py-2 px-4 border-b text-left">Amount</th>
                                    <th class="py-2 px-4 border-b text-left">Month</th>
                                    <th class="py-2 px-4 border-b text-left">NIC</th>
                                    <th class="py-2 px-4 border-b text-left">Payment Date</th>
                                    <th class="py-2 px-4 border-b text-left">Status</th>
                                    <th class="py-2 px-4 border-b text-left">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $payment): ?>
                                    <tr>
                                        <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($payment['payment_id']); ?></td>
                                        <td class="py-2 px-4 border-b">$<?php echo number_format($payment['amount'], 2); ?></td>
                                        <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($payment['month']); ?></td>
                                        <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($payment['nic']); ?></td>
                                        <td class="py-2 px-4 border-b"><?php echo date('Y-m-d', strtotime($payment['payment_date'])); ?></td>
                                        <td class="py-2 px-4 border-b">
                                            <span class="px-2 py-1 rounded text-xs <?php echo $payment['status'] == 1 ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                                <?php echo getStatusText($payment['status']); ?>
                                            </span>
                                        </td>
                                        <td class="py-2 px-4 border-b">
                                            <a href="editPayment.php?id=<?php echo $payment['payment_id']; ?>" class="text-blue-600 hover:text-blue-900 mr-2">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="#" onclick="confirmDelete(<?php echo $payment['payment_id']; ?>)" class="text-red-600 hover:text-red-900">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-lg p-6 max-w-sm mx-auto">
            <h3 class="text-lg font-bold mb-4">Confirm Deletion</h3>
            <p class="mb-6">Are you sure you want to delete this payment record? This action cannot be undone.</p>
            <div class="flex justify-end space-x-4">
                <button onclick="closeModal()" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-100">
                    Cancel
                </button>
                <a id="confirmDeleteBtn" href="#" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                    Delete
                </a>
            </div>
        </div>
    </div>

    <script>
        function confirmDelete(paymentId) {
            const modal = document.getElementById('deleteModal');
            const confirmBtn = document.getElementById('confirmDeleteBtn');
            
            modal.classList.remove('hidden');
            confirmBtn.href = `managePayments.php?action=delete&id=${paymentId}`;
        }
        
        function closeModal() {
            const modal = document.getElementById('deleteModal');
            modal.classList.add('hidden');
        }

        // Show notification messages
        <?php if ($message): ?>
            document.addEventListener('DOMContentLoaded', function() {
                Toastify({
                    text: "<?php echo $message; ?>",
                    duration: 3000,
                    gravity: "top",
                    position: "center",
                    backgroundColor: "#48BB78",
                    stopOnFocus: true
                }).showToast();
            });
        <?php endif; ?>
        
        <?php if ($error): ?>
            document.addEventListener('DOMContentLoaded', function() {
                Toastify({
                    text: "<?php echo $error; ?>",
                    duration: 3000,
                    gravity: "top",
                    position: "center",
                    backgroundColor: "#F56565",
                    stopOnFocus: true
                }).showToast();
            });
        <?php endif; ?>
    </script>
</body>
</html>