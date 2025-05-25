<?php
session_start();
include('connection.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user data
$user_query = "SELECT * FROM customers WHERE id = '$user_id'";
$user_result = $conn->query($user_query);

if (!$user_result) {
    die("Error fetching user data: " . $conn->error);
}

$user = $user_result->fetch_assoc();

if (!$user) {
    die("User not found");
}

// Fetch order statistics
$orders_query = "SELECT COUNT(*) as total_orders, SUM(amount) as total_spent 
                FROM orders 
                WHERE user_id = '$user_id'";
$orders_result = $conn->query($orders_query);
$order_stats = $orders_result->fetch_assoc();

// Fetch last 3 orders
$recent_orders_query = "SELECT o.id, o.created_at, o.status, 
                              GROUP_CONCAT(p.name SEPARATOR ', ') as products
                       FROM orders o
                       JOIN order_items oi ON o.id = oi.order_id
                       JOIN product p ON oi.product_id = p.product_id
                       WHERE o.user_id = '$user_id'
                       GROUP BY o.id
                       ORDER BY o.created_at DESC
                       LIMIT 3";
$recent_orders_result = $conn->query($recent_orders_query);

// Include FPDF library
require('fpdf/fpdf.php');

// Create new PDF document
$pdf = new FPDF();
$pdf->AddPage();

// Set font
$pdf->SetFont('Arial', 'B', 16);

// Title and Logo
$pdf->Image('./images/logo.png', 10, 10, 30);
$pdf->SetFont('Arial', 'B', 15);
$pdf->Cell(0, 10, 'CeylonCart User Profile Details', 0, 1, 'C');
$pdf->Ln(2);
// Show user name
$pdf->SetFont('Arial', 'B', 13);
$pdf->Cell(0, 10, 'User: ' . $user['username'], 0, 1, 'C');
$pdf->Ln(8);

// Customer Statistics Section
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, 'Customer Statistics', 0, 1, 'L');
$pdf->SetFont('Arial', '', 12);

// Valued Customer Since
$pdf->Cell(60, 10, 'Valued Customer Since:', 0);
$pdf->Cell(0, 10, date('F d, Y', strtotime($user['created_at'])), 0, 1);

// Lifetime Orders
$pdf->Cell(60, 10, 'Lifetime Orders:', 0);
$pdf->Cell(0, 10, $order_stats['total_orders'] ?? '0', 0, 1);

// Total Spent
$pdf->Cell(60, 10, 'Total Spent:', 0);
$pdf->Cell(0, 10, '$' . number_format($order_stats['total_spent'] ?? 0, 2), 0, 1);

$pdf->Ln(5);

// Last 3 Orders Section
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, 'Last 3 Orders', 0, 1, 'L');
$pdf->SetFont('Arial', '', 12);

if ($recent_orders_result && $recent_orders_result->num_rows > 0) {
    // Table header
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(30, 10, 'Order ID', 1, 0, 'C', true);
    $pdf->Cell(70, 10, 'Products', 1, 0, 'C', true);
    $pdf->Cell(50, 10, 'Date', 1, 0, 'C', true);
    $pdf->Cell(40, 10, 'Status', 1, 1, 'C', true);
    
    // Table data
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetFillColor(255, 255, 255);
    
    while ($order = $recent_orders_result->fetch_assoc()) {
        $status = $order['status']; // Use status as in orders table
        
        // Handle long product names by wrapping text
        $products = $order['products'];
        if (strlen($products) > 40) {
            $products = substr($products, 0, 37) . '...';
        }
        
        $pdf->Cell(30, 10, '#' . $order['id'], 1, 0, 'C');
        $pdf->Cell(70, 10, $products, 1, 0, 'L');
        $pdf->Cell(50, 10, date('M d, Y', strtotime($order['created_at'])), 1, 0, 'C');
        $pdf->Cell(40, 10, $status, 1, 1, 'C');
    }
} else {
    $pdf->Cell(0, 10, 'No orders found', 0, 1);
}

$pdf->Ln(5);

// Default Shipping Address Section
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, 'Default Shipping Address', 0, 1, 'L');
$pdf->SetFont('Arial', '', 12);

$pdf->MultiCell(0, 10, $user['address'], 0, 1);
$pdf->Cell(0, 10, $user['postal_code'] . ', ' . $user['country'], 0, 1);

$pdf->Ln(5);


// Add footer
$pdf->SetY(-15);
$pdf->SetFont('Arial', 'I', 8);
$pdf->Cell(0, 10, 'Generated on ' . date('Y-m-d H:i:s'), 0, 0, 'C');

// Output the PDF
$pdf->Output('D', 'user_profile_' . $user['username'] . '.pdf');
?> 