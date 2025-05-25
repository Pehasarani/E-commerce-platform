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
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: payment.php");
    exit();
}

$card_id = $_GET['id'];

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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $card_holder = trim($_POST['card_holder']);
    $card_number = trim($_POST['card_number']);
    $expiry_date = trim($_POST['expiry_date']);
    $cvv = trim($_POST['cvv']);
    
    // Basic validation
    if (empty($card_holder) || empty($card_number) || empty($expiry_date) || empty($cvv)) {
        $message = "All fields are required";
    } else {
        // Clean card number (remove dashes)
        $clean_card_number = str_replace('-', '', $card_number);
        
        try {
            $stmt = $conn->prepare("UPDATE saved_cards SET card_holder = ?, card_number = ?, expiry_date = ?, cvv = ? WHERE id = ? AND supplier_id = ?");
            $stmt->bind_param("ssssii", $card_holder, $clean_card_number, $expiry_date, $cvv, $card_id, $supplier_id);
            $stmt->execute();
            
            if ($stmt->affected_rows > 0) {
                $message = "Card updated successfully";
                // Update the card object to reflect changes
                $card['card_holder'] = $card_holder;
                $card['card_number'] = $clean_card_number;
                $card['expiry_date'] = $expiry_date;
                $card['cvv'] = $cvv;
            } else {
                $message = "No changes were made";
            }
            
            $stmt->close();
        } catch (Exception $e) {
            $message = "Error updating card: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ceylon Cart - Edit Card</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .edit-container {
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
        .edit-btn {
            background-color: #FF6F00;
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 4px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .edit-btn:hover {
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
            <!-- Edit Card Section -->
            <div class="edit-container">
                <a href="payment.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to Payment
                </a>
                <h1 class="text-3xl font-bold text-center mb-8">Edit Card</h1>
                
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
                            <div>Current Card: <?php echo htmlspecialchars($card['card_number']); ?></div>
                            <div>Expires: <?php echo htmlspecialchars($card['expiry_date']); ?></div>
                        </div>
                    </div>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="card_holder">Name on Card</label>
                            <input type="text" id="card_holder" name="card_holder" required
                                value="<?php echo htmlspecialchars($card['card_holder']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="card_number">Card Number</label>
                            <input type="text" id="card_number" name="card_number" required
                                value="<?php echo htmlspecialchars($card['card_number']); ?>"
                                maxlength="19">
                        </div>
                        <div class="form-group">
                            <label for="expiry_date">Expiry Date</label>
                            <input type="text" id="expiry_date" name="expiry_date" required
                                value="<?php echo htmlspecialchars($card['expiry_date']); ?>"
                                placeholder="MM/YY"
                                maxlength="5">
                        </div>
                        <div class="form-group">
                            <label for="cvv">CVV</label>
                            <input type="text" id="cvv" name="cvv" required
                                value="<?php echo htmlspecialchars($card['cvv']); ?>"
                                placeholder="XXX"
                                maxlength="3">
                        </div>
                        
                        <button type="submit" class="edit-btn">Save Changes</button>
                    </form>
                <?php else: ?>
                    <p>Card not found or you don't have permission to edit it.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const cardNumber = document.getElementById('card_number');
            const expiryDate = document.getElementById('expiry_date');
            const cvv = document.getElementById('cvv');
            
            // Format card number
            cardNumber.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                value = value.replace(/(\d{4})(?=\d)/g, '$1-');
                e.target.value = value;
            });
            
            // Format expiry date
            expiryDate.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length >= 2) {
                    value = value.slice(0,2) + '/' + value.slice(2);
                }
                e.target.value = value;
            });
            
            // Format CVV
            cvv.addEventListener('input', function(e) {
                e.target.value = e.target.value.replace(/\D/g, '');
            });
        });
    </script>
</body>
</html>