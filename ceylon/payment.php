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
$saved_cards = [];
$message = '';

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
}

// Handle form submission to save card
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_card') {
    $card_holder = trim($_POST['card_name']);
    $card_number = trim($_POST['card_number']);
    $expiry_date = trim($_POST['expiry_date']);
    $cvv = trim($_POST['cvv']);
    
    // Basic validation
    if (empty($card_holder) || empty($card_number) || empty($expiry_date) || empty($cvv)) {
        $message = "All fields are required";
    } else {
        // Get card type based on first digit
        $first_digit = substr($card_number, 0, 1);
        $card_type = "Unknown";
        if ($first_digit == '4') {
            $card_type = "Visa";
        } elseif ($first_digit == '5') {
            $card_type = "MasterCard";
        } elseif ($first_digit == '6') {
            $card_type = "Discover";
        } elseif ($first_digit == '3') {
            $card_type = "Amex";
        }
        
        // Clean card number (remove dashes)
        $clean_card_number = str_replace('-', '', $card_number);
        
        try {
            // Store the full card details as requested (note: this is not PCI compliant)
            $stmt = $conn->prepare("INSERT INTO saved_cards (supplier_id, card_holder, card_number, expiry_date, cvv, card_type) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssss", $supplier_id, $card_holder, $clean_card_number, $expiry_date, $cvv, $card_type);
            $stmt->execute();
            $stmt->close();
            
            $message = "Card saved successfully!";
            
            // Process payment here if needed
            header("Location: payment.php?success=1");
            exit();
        } catch (Exception $e) {
            $message = "Error saving card: " . $e->getMessage();
        }
    }
}

// Handle card deletion
if (isset($_GET['delete_card'])) {
    $card_id = $_GET['delete_card'];
    
    try {
        $stmt = $conn->prepare("DELETE FROM saved_cards WHERE id = ? AND supplier_id = ?");
        $stmt->bind_param("ii", $card_id, $supplier_id);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            $message = "Card deleted successfully";
        } else {
            $message = "Card not found or you don't have permission to delete it";
        }
        
        $stmt->close();
    } catch (Exception $e) {
        $message = "Error deleting card: " . $e->getMessage();
    }
}

// Fetch saved cards
try {
    $stmt = $conn->prepare("SELECT id, card_holder, card_number, expiry_date, cvv, card_type, created_at FROM saved_cards WHERE supplier_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $supplier_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $saved_cards[] = $row;
    }
    
    $stmt->close();
} catch (Exception $e) {
    $message = "Error fetching saved cards: " . $e->getMessage();
}

// Check for success messages
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $message = "Card saved and payment processed successfully!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ceylon Cart - Payment</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .card-input {
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20"><path fill="%23888" d="M10 0C4.48 0 0 4.48 0 10s4.48 10 10 10 10-4.48 10-10S15.52 0 10 0zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6H9zm0 8h2v2H9z"/></svg>');
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 20px;
        }
        .card-input.error {
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20"><path fill="%23ff0000" d="M10 0C4.48 0 0 4.48 0 10s4.48 10 10 10 10-4.48 10-10S15.52 0 10 0zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6H9zm0 8h2v2H9z"/></svg>');
        }
        .card-input.valid {
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20"><path fill="%2300ff00" d="M10 0C4.48 0 0 4.48 0 10s4.48 10 10 10 10-4.48 10-10S15.52 0 10 0zm-1 15l-5-5 1.41-1.41L9 12.17l7.59-7.59L18 6l-9 9z"/></svg>');
        }
        .payment-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        .payment-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-top: 2rem;
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
        .payment-method {
            display: flex;
            gap: 1rem;
            margin: 1rem 0;
        }
        .payment-method i {
            font-size: 2rem;
            color: #666;
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
        .saved-cards {
            margin-top: 2rem;
        }
        .card-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .card-details {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .card-icon {
            font-size: 2rem;
        }
        .card-actions {
            display: flex;
            gap: 1rem;
        }
        .card-actions a {
            color: #FF6F00;
            cursor: pointer;
        }
        .card-actions a:hover {
            text-decoration: underline;
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
        .tab-buttons {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .tab-button {
            padding: 0.75rem 1.5rem;
            background-color: #eee;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        .tab-button.active {
            background-color: #FF6F00;
            color: white;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        tr:hover {
            background-color: #f5f5f5;
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
                <a href="supplierDashboard.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <h1 class="text-3xl font-bold text-center mb-8">PAYMENT</h1>
                
                <?php if (!empty($message)): ?>
                    <div class="message <?php echo strpos($message, 'successfully') !== false ? 'success' : ''; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <div class="tab-buttons">
                    <button class="tab-button active" data-tab="new-card">Add New Card</button>
                    <button class="tab-button" data-tab="saved-cards">Manage Saved Cards</button>
                    <button class="tab-button" data-tab="view-data">View Card Data</button>
                </div>
                
                <div id="new-card" class="tab-content active">
                    <div class="payment-details">
                        <!-- Left Column: Payment Details -->
                        <div class="left-column">
                            <form id="paymentForm" method="POST" action="">
                                <input type="hidden" name="action" value="save_card">
                                <div class="form-group">
                                    <label for="card_name">Name on Card</label>
                                    <input type="text" id="card_name" name="card_name" required
                                        class="card-input"
                                        placeholder="Enter name on card">
                                </div>
                                <div class="form-group">
                                    <label for="card_number">Card Number</label>
                                    <input type="text" id="card_number" name="card_number" required
                                        class="card-input"
                                        placeholder="XXXX-XXXX-XXXX-XXXX"
                                        maxlength="19">
                                </div>
                                <div class="form-group">
                                    <label for="expiry_date">Expiry Date</label>
                                    <input type="text" id="expiry_date" name="expiry_date" required
                                        class="card-input"
                                        placeholder="MM/YY"
                                        maxlength="5">
                                </div>
                                <div class="form-group">
                                    <label for="cvv">CVV</label>
                                    <input type="text" id="cvv" name="cvv" required
                                        class="card-input"
                                        placeholder="XXX"
                                        maxlength="3">
                                </div>
                            </form>
                        </div>

                        <!-- Right Column: Payment Method Icons -->
                        <div class="right-column">
                            <h2 class="text-2xl font-bold mb-4">PAYMENT</h2>
                            <div class="payment-method">
                                <i class="fab fa-cc-visa"></i>
                                <i class="fab fa-cc-mastercard"></i>
                                <i class="fab fa-cc-discover"></i>
                            </div>
                            <button type="submit" form="paymentForm" class="pay-btn">PAY & SAVE CARD</button>
                        </div>
                    </div>
                </div>
                
                <div id="saved-cards" class="tab-content">
                    <h2 class="text-2xl font-bold mb-4">Your Saved Cards</h2>
                    
                    <?php if (empty($saved_cards)): ?>
                        <p>You don't have any saved cards yet.</p>
                    <?php else: ?>
                        <div class="saved-cards">
                            <?php foreach ($saved_cards as $card): ?>
                                <div class="card-item">
                                    <div class="card-details">
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
                                            <div><strong><?php echo htmlspecialchars($card['card_holder']); ?></strong></div>
                                            <div>Card ending in <?php echo substr($card['card_number'], -4); ?></div>
                                            <div>Expires: <?php echo htmlspecialchars($card['expiry_date']); ?></div>
                                        </div>
                                    </div>
                                    
                                    <div class="card-actions">
                                        <a href="editCard.php?id=<?php echo $card['id']; ?>" class="edit-card">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="?delete_card=<?php echo $card['id']; ?>" class="delete-card" 
                                           onclick="return confirm('Are you sure you want to delete this card?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                        <a href="makePayment.php?card_id=<?php echo $card['id']; ?>" class="use-card">
                                            <i class="fas fa-credit-card"></i> Pay with this card
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div id="view-data" class="tab-content">
                    <h2 class="text-2xl font-bold mb-4">View All Card Data</h2>
                    
                    <?php if (empty($saved_cards)): ?>
                        <p>You don't have any saved cards yet.</p>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Card Holder</th>
                                    <th>Card Number</th>
                                    <th>Expiry Date</th>
                                    <th>CVV</th>
                                    <th>Card Type</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($saved_cards as $card): ?>
                                    <tr>
                                        <td><?php echo $card['id']; ?></td>
                                        <td><?php echo htmlspecialchars($card['card_holder']); ?></td>
                                        <td><?php echo htmlspecialchars($card['card_number']); ?></td>
                                        <td><?php echo htmlspecialchars($card['expiry_date']); ?></td>
                                        <td><?php echo htmlspecialchars($card['cvv']); ?></td>
                                        <td><?php echo htmlspecialchars($card['card_type']); ?></td>
                                        <td><?php echo htmlspecialchars($card['created_at']); ?></td>
                                        <td>
                                            <a href="editCard.php?id=<?php echo $card['id']; ?>" class="text-blue-500">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?delete_card=<?php echo $card['id']; ?>" class="text-red-500 ml-2" 
                                               onclick="return confirm('Are you sure you want to delete this card?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('paymentForm');
            const cardNumber = document.getElementById('card_number');
            const expiryDate = document.getElementById('expiry_date');
            const cvv = document.getElementById('cvv');
            const tabButtons = document.querySelectorAll('.tab-button');
            const tabContents = document.querySelectorAll('.tab-content');

            // Format card number
            cardNumber.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                value = value.replace(/(\d{4})(?=\d)/g, '$1-');
                e.target.value = value;
                validateCardNumber(value);
            });

            // Format expiry date
            expiryDate.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length >= 2) {
                    value = value.slice(0,2) + '/' + value.slice(2);
                }
                e.target.value = value;
                validateExpiryDate(value);
            });

            // Format CVV
            cvv.addEventListener('input', function(e) {
                e.target.value = e.target.value.replace(/\D/g, '');
                validateCVV(e.target.value);
            });

            function validateCardNumber(number) {
                const cardInput = cardNumber;
                const cleanNumber = number.replace(/-/g, '');
                if (cleanNumber.length === 16) {
                    cardInput.classList.remove('error');
                    cardInput.classList.add('valid');
                } else {
                    cardInput.classList.remove('valid');
                    cardInput.classList.add('error');
                }
            }

            function validateExpiryDate(date) {
                const expiryInput = expiryDate;
                const [month, year] = date.split('/');
                const currentDate = new Date();
                const currentYear = currentDate.getFullYear() % 100;
                const currentMonth = currentDate.getMonth() + 1;

                if (month && year && 
                    month >= 1 && month <= 12 && 
                    (year > currentYear || (year == currentYear && month >= currentMonth))) {
                    expiryInput.classList.remove('error');
                    expiryInput.classList.add('valid');
                } else {
                    expiryInput.classList.remove('valid');
                    expiryInput.classList.add('error');
                }
            }

            function validateCVV(cvvValue) {
                const cvvInput = document.getElementById('cvv');
                if (cvvValue.length === 3) {
                    cvvInput.classList.remove('error');
                    cvvInput.classList.add('valid');
                } else {
                    cvvInput.classList.remove('valid');
                    cvvInput.classList.add('error');
                }
            }

            // Tab functionality
            tabButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const tabId = button.getAttribute('data-tab');
                    
                    // Update active tab button
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    button.classList.add('active');
                    
                    // Update active tab content
                    tabContents.forEach(content => content.classList.remove('active'));
                    document.getElementById(tabId).classList.add('active');
                });
            });

            // Form validation before submission
            form.addEventListener('submit', function(e) {
                // Check if all fields are valid
                const isValid = 
                    cardNumber.classList.contains('valid') &&
                    expiryDate.classList.contains('valid') &&
                    cvv.classList.contains('valid') &&
                    document.getElementById('card_name').value.trim() !== '';

                if (!isValid) {
                    e.preventDefault();
                    alert('Please check your card details and try again.');
                }
            });
        });
    </script>
</body>
</html>