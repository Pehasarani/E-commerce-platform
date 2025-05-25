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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fieldErrors = [];
    $amount = $_POST['amount'];
    $nic = trim($_POST['nic']);
    $month = $_POST['month'];
    $card_name = trim($_POST['card_name']);
    $card_number = preg_replace('/\D/', '', $_POST['card_number']);
    $expiry_date = $_POST['expiry_date'];
    $cvv = $_POST['cvv'];
    $supplier_name = $supplier['business_name'];
    $payment_date = date('Y-m-d H:i:s');
    $status = 1; // Changed from 'Completed' to integer 1

    // Validate NIC
    if (empty($nic)) {
        $fieldErrors['nic'] = "NIC is required";
    } elseif (!preg_match('/^\d{9}[VvXx]$|^\d{12}$/', $nic)) {
        $fieldErrors['nic'] = "NIC must be 12 digits or 9 digits followed by V or X";
    }

    // Validate Card Name
    if (empty($card_name)) {
        $fieldErrors['card_name'] = "Card holder name is required";
    } elseif (!preg_match('/^[A-Za-z\s]+$/', $card_name)) {
        $fieldErrors['card_name'] = "Name must contain letters only";
    }

    // Validate Card Number (using Luhn algorithm)
    if (empty($card_number)) {
        $fieldErrors['card_number'] = "Card number is required";
    } elseif (strlen($card_number) !== 16) {
        $fieldErrors['card_number'] = "Card number must be 16 digits";
    } 
    // Validate Expiry Date
    if (empty($expiry_date)) {
        $fieldErrors['expiry_date'] = "Expiry date is required";
    } else {
        $today = new DateTime();
        $expiry = DateTime::createFromFormat('m/y', $expiry_date);
        if (!$expiry) {
            $fieldErrors['expiry_date'] = "Invalid date format";
        } elseif ($expiry <= $today) {
            $fieldErrors['expiry_date'] = "Card has expired";
        }
    }

    // Validate CVV
    if (empty($cvv)) {
        $fieldErrors['cvv'] = "CVV is required";
    } elseif (!preg_match('/^\d{3,4}$/', $cvv)) {
        $fieldErrors['cvv'] = "CVV must be 3 or 4 digits";
    }

    if (empty($fieldErrors)) {
        try {
            // Insert payment record
            $sql = "INSERT INTO `supplier_payment` (`amount`, `status`, `payment_date`, `month`, `nic`, `supplier_name`) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("dissss", $amount, $status, $payment_date, $month, $nic, $supplier_name);
            
            if ($stmt->execute()) {
                // Redirect to success page
                header("Location: paymentSuccess.php");
                exit();
            } else {
                $error = "Error processing payment: " . $stmt->error;
            }
            $stmt->close();
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Luhn algorithm function

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier Payment</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <style>
        .success-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease;
        }
        .success-overlay.show {
            opacity: 1;
            visibility: visible;
        }
        .success-message {
            background-color: white;
            padding: 2rem;
            border-radius: 0.5rem;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
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
    <!-- Success Overlay -->
    <div id="successOverlay" class="success-overlay">
        <div class="success-message">
            <i class="fas fa-check-circle text-green-500 text-5xl mb-4"></i>
            <h2 class="text-2xl font-bold text-gray-800 mb-2">Payment Successful!</h2>
            <p class="text-gray-600">You will be redirected to the dashboard shortly...</p>
        </div>
    </div>

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
                    <a href="managePayments.php" style="text-decoration: none; color: white;">
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
                <h1>Payment Processing</h1>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6 mt-6">
                <?php if ($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form id="paymentForm" method="POST" class="max-w-lg mx-auto p-6 bg-white rounded-lg shadow-md">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="amount">
                            Payment Amount ($)
                        </label>
                        <input type="number" id="amount" name="amount" value="100" readonly
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-gray-100"
                            placeholder="100">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="month">
                            Payment Month
                        </label>
                        <input type="month" id="month" name="month" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-white">
                    </div>

                    <div class="mb-4">
                        <label for="nic" class="block text-gray-700 text-sm font-bold mb-2">NIC Number *</label>
                        <input type="text" 
                               id="nic" 
                               name="nic" 
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?php echo isset($fieldErrors['nic']) ? 'border-red-500' : ''; ?>"
                               value="<?php echo htmlspecialchars($_POST['nic'] ?? ''); ?>"
                               maxlength="12">
                        <?php if (isset($fieldErrors['nic'])): ?>
                            <p class="text-red-500 text-xs mt-1"><?php echo htmlspecialchars($fieldErrors['nic']); ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="border-t border-gray-200 pt-4">
                        <h3 class="text-lg font-semibold mb-4">Card Details</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="card_name" class="block text-gray-700 text-sm font-bold mb-2">Card Holder Name *</label>
                                <input type="text" 
                                       id="card_name" 
                                       name="card_name" 
                                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?php echo isset($fieldErrors['card_name']) ? 'border-red-500' : ''; ?>"
                                       value="<?php echo htmlspecialchars($_POST['card_name'] ?? ''); ?>">
                                <?php if (isset($fieldErrors['card_name'])): ?>
                                    <p class="text-red-500 text-xs mt-1"><?php echo htmlspecialchars($fieldErrors['card_name']); ?></p>
                                <?php endif; ?>
                            </div>

                            <div>
                                <label for="card_number" class="block text-gray-700 text-sm font-bold mb-2">Card Number *</label>
                                <input type="text" 
                                       id="card_number" 
                                       name="card_number" 
                                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?php echo isset($fieldErrors['card_number']) ? 'border-red-500' : ''; ?>"
                                       maxlength="19">
                                <?php if (isset($fieldErrors['card_number'])): ?>
                                    <p class="text-red-500 text-xs mt-1"><?php echo htmlspecialchars($fieldErrors['card_number']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mt-4">
                            <div class="mb-4">
                                <label for="expiry_date" class="block text-gray-700 text-sm font-bold mb-2">Expiry Date *</label>
                                <input type="text" 
                                       id="expiry_date" 
                                       name="expiry_date" 
                                       placeholder="MM/YY"
                                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?php echo isset($fieldErrors['expiry_date']) ? 'border-red-500' : ''; ?>"
                                       maxlength="5">
                                <?php if (isset($fieldErrors['expiry_date'])): ?>
                                    <p class="text-red-500 text-xs mt-1"><?php echo htmlspecialchars($fieldErrors['expiry_date']); ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="mb-4">
                                <label for="cvv" class="block text-gray-700 text-sm font-bold mb-2">CVV *</label>
                                <input type="text" 
                                       id="cvv" 
                                       name="cvv" 
                                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?php echo isset($fieldErrors['cvv']) ? 'border-red-500' : ''; ?>"
                                       maxlength="4">
                                <?php if (isset($fieldErrors['cvv'])): ?>
                                    <p class="text-red-500 text-xs mt-1"><?php echo htmlspecialchars($fieldErrors['cvv']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <button type="submit" 
                            class="bg-[#FF6F00] hover:bg-[#ff4b00] text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                            Pay $100
                        </button>
                        <a href="supplierDashboard.php" 
                            class="text-[#FF6F00] hover:text-[#ff4b00]">
                            Back to Dashboard
                        </a>
                    </div>
                </form>
            </div>
        </main>
    </div>
    <script>
        // Add formatting for card number input
        document.getElementById('card_number').addEventListener('input', function(e) {
            // Remove non-digit characters
            let value = this.value.replace(/\D/g, '');
            // Add spaces every 4 digits
            if (value.length > 0) {
                value = value.match(new RegExp('.{1,4}', 'g')).join(' ');
            }
            this.value = value;
        });

        // Add formatting for expiry date input
        document.getElementById('expiry_date').addEventListener('input', function(e) {
            let value = this.value.replace(/\D/g, '');
            if (value.length > 2) {
                value = value.slice(0, 2) + '/' + value.slice(2, 4);
            }
            this.value = value;
        });

        // Check if there's a success message in the URL
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('success')) {
            const overlay = document.getElementById('successOverlay');
            if (overlay) {
                overlay.classList.add('show');
                setTimeout(() => {
                    window.location.href = "supplierDashboard.php";
                }, 3000);
            }
        }
    </script>
</body>
</html>