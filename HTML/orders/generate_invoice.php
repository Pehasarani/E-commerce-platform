<?php
session_start();
require('connection.php');
require('fpdf/fpdf.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Show error page with header
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Error - Ceylon Cart</title>
        <link href="https://fonts.googleapis.com/css2?family=Koulen&family=Abel&display=swap" rel="stylesheet">
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
        <style>
            /* General Styles */
            body {
                margin: 0;
                font-family: 'Abel', sans-serif;
                background-color: #f0f0f0;
                color: #333;
            }

            /* Header */
            .header {
                background-color: #F35821;
                position: sticky;
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 16px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }

            /* Logo on the Left */
            .logo-container {
                display: flex;
                align-items: center;
            }

            .logo {
                width: 200px;
                height: auto;
            }

            /* Buttons on the Right */
            .header-buttons {
                display: flex;
                gap: 16px;
                align-items: center;
                margin-right: 24px;
            }

            /* Profile Button */
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

            /* Cart Button */
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

            /* Dropdown Menu */
            .profile-dropdown {
                position: relative;
                display: inline-block;
            }

            .dropdown-content {
                display: none;
                position: absolute;
                right: 0;
                background-color: #f9f9f9;
                min-width: 160px;
                box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
                z-index: 1;
                border-radius: 8px;
                overflow: hidden;
            }

            .dropdown-content a {
                color: black;
                padding: 12px 16px;
                text-decoration: none;
                display: block;
                font-size: 14px;
                font-weight: bold;
            }

            .dropdown-content a:hover {
                background-color: #f1f1f1;
            }

            .profile-dropdown:hover .dropdown-content {
                display: block;
            }
        </style>
    </head>
    <body class="bg-gray-50">
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

        <div class="container mx-auto px-4 py-8">
            <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-lg p-8 text-center">
                <h1 class="text-2xl font-bold mb-4" style="font-family: 'Koulen', sans-serif;">Access Denied</h1>
                <p class="text-gray-600 mb-4">Please log in to access this page.</p>
                <a href="login.php" class="inline-block bg-[#F35821] text-white py-2 px-6 rounded-lg font-bold hover:bg-[#e04d1a] transition">
                    Go to Login
                </a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// Check if order_id is provided
if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    // Show error page with header
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Error - Ceylon Cart</title>
        <link href="https://fonts.googleapis.com/css2?family=Koulen&family=Abel&display=swap" rel="stylesheet">
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
        <style>
            /* General Styles */
            body {
                margin: 0;
                font-family: 'Abel', sans-serif;
                background-color: #f0f0f0;
                color: #333;
            }

            /* Header */
            .header {
                background-color: #F35821;
                position: sticky;
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 16px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }

            /* Logo on the Left */
            .logo-container {
                display: flex;
                align-items: center;
            }

            .logo {
                width: 200px;
                height: auto;
            }

            /* Buttons on the Right */
            .header-buttons {
                display: flex;
                gap: 16px;
                align-items: center;
                margin-right: 24px;
            }

            /* Profile Button */
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

            /* Cart Button */
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

            /* Dropdown Menu */
            .profile-dropdown {
                position: relative;
                display: inline-block;
            }

            .dropdown-content {
                display: none;
                position: absolute;
                right: 0;
                background-color: #f9f9f9;
                min-width: 160px;
                box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
                z-index: 1;
                border-radius: 8px;
                overflow: hidden;
            }

            .dropdown-content a {
                color: black;
                padding: 12px 16px;
                text-decoration: none;
                display: block;
                font-size: 14px;
                font-weight: bold;
            }

            .dropdown-content a:hover {
                background-color: #f1f1f1;
            }

            .profile-dropdown:hover .dropdown-content {
                display: block;
            }
        </style>
    </head>
    <body class="bg-gray-50">
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

        <div class="container mx-auto px-4 py-8">
            <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-lg p-8 text-center">
                <h1 class="text-2xl font-bold mb-4" style="font-family: 'Koulen', sans-serif;">Invalid Order</h1>
                <p class="text-gray-600 mb-4">The order ID is missing or invalid.</p>
                <a href="orderhistory.php" class="inline-block bg-[#F35821] text-white py-2 px-6 rounded-lg font-bold hover:bg-[#e04d1a] transition">
                    View Order History
                </a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit();
}

$order_id = $_GET['order_id'];
$user_id = $_SESSION['user_id'];

// Verify that the order belongs to the logged-in user
$order_query = "SELECT o.*, c.username, c.email, c.phone_personal, c.address 
               FROM orders o 
               JOIN customers c ON o.user_id = c.id 
               WHERE o.id = ? AND o.user_id = ?";

$stmt = $conn->prepare($order_query);
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: profile.php");
    exit();
}

$order = $result->fetch_assoc();

// Get order items
$items_query = "SELECT oi.*, p.name, p.product_images 
               FROM order_items oi 
               JOIN product p ON oi.product_id = p.product_id 
               WHERE oi.order_id = ?";

$stmt = $conn->prepare($items_query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items_result = $stmt->get_result();
$order_items = [];

while ($item = $items_result->fetch_assoc()) {
    $order_items[] = $item;
}

// Create PDF
class PDF extends FPDF {
    function Header() {
        // Logo
        $this->Image('Images/Logo.png', 10, 10, 50);
        // Line break
        $this->Ln(20);
    }
    
    function Footer() {
        // Position at 1.5 cm from bottom
        $this->SetY(-15);
        // Arial italic 8
        $this->SetFont('Arial', 'I', 8);
        // Page number
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

// Initialize PDF
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);

// Title
$pdf->Cell(0, 10, 'INVOICE', 0, 1, 'C');
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, 'Invoice #: INV-' . str_pad($order_id, 6, '0', STR_PAD_LEFT), 0, 1, 'R');
$pdf->Cell(0, 10, 'Date: ' . date('F j, Y', strtotime($order['created_at'])), 0, 1, 'R');
$pdf->Ln(10);

// Customer Information
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Customer Information:', 0, 1);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 8, 'Name: ' . $order['username'], 0, 1);
$pdf->Cell(0, 8, 'Email: ' . $order['email'], 0, 1);
$pdf->Cell(0, 8, 'Phone: ' . $order['phone_personal'], 0, 1);
$pdf->Cell(0, 8, 'Address: ' . $order['address'], 0, 1);
$pdf->Ln(10);

// Order Items
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Order Items:', 0, 1);
$pdf->SetFont('Arial', 'B', 10);

// Table header
$pdf->Cell(90, 10, 'Product', 1, 0, 'C');
$pdf->Cell(30, 10, 'Price ($)', 1, 0, 'C');
$pdf->Cell(30, 10, 'Quantity', 1, 0, 'C');
$pdf->Cell(40, 10, 'Subtotal ($)', 1, 1, 'C');

// Table content
$pdf->SetFont('Arial', '', 10);
$total = 0;

foreach ($order_items as $item) {
    $subtotal = $item['price'] * $item['quantity'];
    $total += $subtotal;
    
    // Handle long product names
    $name = $item['name'];
    if (strlen($name) > 40) {
        $name = substr($name, 0, 37) . '...';
    }
    
    $pdf->Cell(90, 10, $name, 1, 0);
    $pdf->Cell(30, 10, '$ ' . number_format($item['price'], 2), 1, 0, 'R');
    $pdf->Cell(30, 10, $item['quantity'], 1, 0, 'C');
    $pdf->Cell(40, 10, '$ ' . number_format($subtotal, 2), 1, 1, 'R');
}

// Total
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(150, 10, 'Total:', 1, 0, 'R');
$pdf->Cell(40, 10, '$ ' . number_format($total, 2), 1, 1, 'R');

// Payment Information
$pdf->Ln(10);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Payment Information:', 0, 1);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 8, 'Payment Method: Credit Card', 0, 1);
$pdf->Cell(0, 8, 'Payment Status: Paid', 0, 1);
$pdf->Cell(0, 8, 'Transaction ID: ' . $order['payment_intent_id'], 0, 1);

// Thank you message
$pdf->Ln(10);
$pdf->SetFont('Arial', 'I', 12);
$pdf->Cell(0, 10, 'Thank you for shopping with CeylonCart!', 0, 1, 'C');
$pdf->Cell(0, 10, 'For any questions, please contact support@ceyloncart.com', 0, 1, 'C');

// Output PDF
$pdf->Output('I', 'CeylonCart_Invoice_' . $order_id . '.pdf');
?> 
