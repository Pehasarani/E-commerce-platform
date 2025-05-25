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
$payment = null;

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

// Check if payment ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: managePayments.php");
    exit();
}

$payment_id = $_GET['id'];

// Fetch payment details
try {
    $stmt = $conn->prepare("SELECT * FROM `supplier_payment` WHERE `payment_id` = ? AND `supplier_name` = ?");
    $stmt->bind_param("is", $payment_id, $supplier['business_name']);
    $stmt->execute();
    $result = $stmt->get_result();
    $payment = $result->fetch_assoc();
    $stmt->close();
    
    if (!$payment) {
        header("Location: managePayments.php?error=notfound");
        exit();
    }
    
} catch (Exception $e) {
    $error = "Error fetching payment details: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fieldErrors = [];
    $amount = $_POST['amount'];
    $nic = trim($_POST['nic']);
    $month = $_POST['month'];
    $status = (int)$_POST['status'];
    
    // Validate NIC
    if (empty($nic)) {
        $fieldErrors['nic'] = "NIC is required";
    } elseif (!preg_match('/^\d{9}[VvXx]$|^\d{12}$/', $nic)) {
        $fieldErrors['nic'] = "NIC must be 12 digits or 9 digits followed by V or X";
    }
    
    if (empty($fieldErrors)) {
        try {
            // Update payment record
            $sql = "UPDATE `supplier_payment` SET `amount` = ?, `status` = ?, `month` = ?, `nic` = ? WHERE `payment_id` = ? AND `supplier_name` = ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("disssi", $amount, $status, $month, $nic, $payment_id, $supplier['business_name']);
            
            if ($stmt->execute()) {
                // Redirect to success page
                header("Location: managePayments.php?message=updated");
                exit();
            } else {
                $error = "Error updating payment: " . $stmt->error;
            }
            $stmt->close();
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Function to check selected status
function isSelected($statusValue, $currentStatus) {
    return $statusValue == $currentStatus ? 'selected' : '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Payment Record</title>
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
                <h1>Edit Payment Record</h1>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6 mt-6">
                <?php if ($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="max-w-lg mx-auto p-6 bg-white rounded-lg shadow-md">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="payment_id">
                            Payment ID
                        </label>
                        <input type="text" id="payment_id" name="payment_id" value="<?php echo htmlspecialchars($payment['payment_id']); ?>" readonly
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-gray-100">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="amount">
                            Payment Amount ($)
                        </label>
                        <input type="number" id="amount" name="amount" value="<?php echo htmlspecialchars($payment['amount']); ?>"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                            required>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="month">
                            Payment Month
                        </label>
                        <input type="month" id="month" name="month" value="<?php echo htmlspecialchars($payment['month']); ?>" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>

                    <div class="mb-4">
                        <label for="nic" class="block text-gray-700 text-sm font-bold mb-2">NIC Number *</label>
                        <input type="text" 
                               id="nic" 
                               name="nic" 
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?php echo isset($fieldErrors['nic']) ? 'border-red-500' : ''; ?>"
                               value="<?php echo htmlspecialchars($payment['nic']); ?>"
                               maxlength="12" required>
                        <?php if (isset($fieldErrors['nic'])): ?>
                            <p class="text-red-500 text-xs mt-1"><?php echo htmlspecialchars($fieldErrors['nic']); ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="payment_date">
                            Payment Date
                        </label>
                        <input type="text" id="payment_date" name="payment_date" value="<?php echo date('Y-m-d', strtotime($payment['payment_date'])); ?>" readonly
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-gray-100">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="status">
                            Payment Status
                        </label>
                        <select id="status" name="status" 
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            <option value="1" <?php echo isSelected(1, $payment['status']); ?>>Completed</option>
                            <option value="2" <?php echo isSelected(2, $payment['status']); ?>>Pending</option>
                            <option value="3" <?php echo isSelected(3, $payment['status']); ?>>Failed</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-between">
                        <button type="submit" 
                            class="bg-[#FF6F00] hover:bg-[#ff4b00] text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                            Update Payment
                        </button>
                        <a href="managePayments.php" 
                            class="text-[#FF6F00] hover:text-[#ff4b00]">
                            Back to Payment List
                        </a>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
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