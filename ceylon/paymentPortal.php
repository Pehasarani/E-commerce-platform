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
    error_log("Payment Error: " . $e->getMessage());
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
                
                <div class="payment-details">
                    <!-- Left Column: Payment Details -->
                    <div class="left-column">
                        <form id="paymentForm">
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
                        <button type="submit" form="paymentForm" class="pay-btn">PAY 10,000 LKR</button>
                    </div>
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

            function validateCVV(cvv) {
                const cvvInput = cvv;
                if (cvv.length === 3) {
                    cvvInput.classList.remove('error');
                    cvvInput.classList.add('valid');
                } else {
                    cvvInput.classList.remove('valid');
                    cvvInput.classList.add('error');
                }
            }

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Check if all fields are valid
                const isValid = 
                    cardNumber.classList.contains('valid') &&
                    expiryDate.classList.contains('valid') &&
                    cvv.classList.contains('valid') &&
                    document.getElementById('card_name').value.trim() !== '';

                if (isValid) {
                    // Redirect to process payment
                    window.location.href = 'supplierPayment.php';
                } else {
                    alert('Please check your card details and try again.');
                }
            });
        });
    </script>
</body>
</html> 