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
$card = null;

// Check if card ID is provided
if (!isset($_GET['card_id']) || empty($_GET['card_id'])) {
    header("Location: payment.php");
    exit();
}

$card_id = $_GET['card_id'];

// Fetch the card details
try {
    $stmt = $conn->prepare("SELECT id, card_holder, card_number, expiry_date, cvv, card_type FROM saved_cards WHERE id = ? AND supplier_id = ?");
    $stmt->bind_param("ii", $card_id, $supplier_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header("Location: payment.php");
        exit();
    }
    
    $card = $result->fetch_assoc();
    $stmt->close();
} catch (Exception $e) {
    $message = "Error fetching card details: " . $e->getMessage();
}

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['make_payment'])) {
    // In a real application, you would process the payment with a payment gateway here
    
    // For demonstration purposes, we'll just simulate a successful payment
    $payment_amount = 10000; // 10,000 LKR
    $payment_date = date('Y-m-d H:i:s');
    
    try {
        $stmt = $conn->prepare("INSERT INTO supplier_payments (supplier_id, amount, payment_date, card_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("idsi", $supplier_id, $payment_amount, $payment_date, $card_id);
        $stmt->execute();
        $stmt->close();
        
        // Redirect to success page
        header("Location: paymentSuccess.php");
        exit();
    } catch (Exception $e) {
        $message = "Error processing payment: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ceylon Cart - Make Payment</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .payment-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #333;
        }
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        .pay-btn {
            background-color: #FF6F00;
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 4px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.3s;
        }
        .pay-btn:hover {
            background-color: #ff4b00;
        }
        .back-link {
            color: #FF6F00;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .card-preview {
            padding: 1.5rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .card-icon {
            font-size: 3rem;
        }
        .message {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
            background-color: #f8d7da;
            color: #721c24;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
        }
        .payment-summary {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .payment-summary h3 {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 1rem;
        }
        .payment-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #eee;
        }
        .payment-row:last-child {
            border-bottom: none;
            font-weight: bold;
            font-size: 1.2rem;
            padding-top: 1rem;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="navbar-logo">
            <img src="./images/logo.png" alt="Logo" class="logo">
        </div>
        <div class="navbar-search">
            <input type="text" placeholder="Search in CeylonCart">
            <button><i class="fas fa-search"></i></button>
        </div>
        <div class="navbar-icons">
            <i class="fas fa-cart-shopping"></i>
            <i class="fas fa-user-circle"></i>
        </div>
    </nav>

    <!-- Dashboard Container -->
    <div class="dashboard-container">
        <main class="main-content">
            <!-- Payment Section -->
            <div class="payment-container">
                <a href="payment.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to Payment
                </a>
                <h1 class="text-3xl font-bold text-center mb-8">Complete Payment</h1>
                
                <?php if (!empty($message)): ?>
                    <div class="message <?php echo strpos($message, 'successfully') !== false ? 'success' : ''; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($card): ?>
                    <div class="card-preview">
                        <?php if ($card['card_type'] === 'Visa'): ?>
                            <i class="fab fa-cc-visa card-icon"></i>
                        <?php elseif ($card['card_type'] === 'MasterCard'): ?>
                            <i class="fab fa-cc-mastercard card-icon"></i>
                        <?php elseif ($card['card_type'] === 'Discover'): ?>
                            <i class="fab fa-cc-discover card-icon"></i>
                        <?php elseif ($card['card_type'] === 'Amex'): ?>
                            <i class="fab fa-cc-amex card-icon"></i>
                        <?php else: ?>
                            <i class="fas fa-credit-card card-icon"></i>
                        <?php endif; ?>
                        
                        <div>
                            <div class="text-xl font-bold"><?php echo htmlspecialchars($card['card_holder']); ?></div>
                            <div>Card Number: <?php echo htmlspecialchars($card['card_number']); ?></div>
                            <div>Expires: <?php echo htmlspecialchars($card['expiry_date']); ?></div>
                        </div>
                    </div>
                    
                    <div class="payment-summary">
                        <h3>Payment Summary</h3>
                        <div class="payment-row">
                            <span>Subscription Fee</span>
                            <span>9,500.00 LKR</span>
                        </div>
                        <div class="payment-row">
                            <span>Tax (5%)</span>
                            <span>500.00 LKR</span>
                        </div>
                        <div class="payment-row">
                            <span>Total Amount</span>
                            <span>10,000.00 LKR</span>
                        </div>
                    </div>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="make_payment" value="1">
                        <button type="submit" class="pay-btn">Confirm Payment - 10,000 LKR</button>
                    </form>
                <?php else: ?>
                    <p>Card not found or you don't have permission to use it.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>