<?php
session_start();
include('connection.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch cart items from the database
$cart_query = "SELECT c.cart_id, c.qty, c.total_cost, p.name, p.price, p.product_images 
               FROM cart c 
               JOIN product p ON c.product_product_id = p.product_id
               WHERE c.userid = '$user_id'";
$cart_result = $conn->query($cart_query);

// Include FPDF library
require('fpdf/fpdf.php');

// Create new PDF document
$pdf = new FPDF();
$pdf->AddPage();

// Set font
$pdf->SetFont('Arial', 'B', 16);

// Title
$pdf->Cell(0, 10, 'Shopping Cart Details', 0, 1, 'C');
$pdf->Ln(10);

// Set font for content
$pdf->SetFont('Arial', '', 12);

// Add cart items
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(80, 10, 'Product Name', 1);
$pdf->Cell(30, 10, 'Quantity', 1);
$pdf->Cell(30, 10, 'Price', 1);
$pdf->Cell(30, 10, 'Total', 1);
$pdf->Ln();

$pdf->SetFont('Arial', '', 12);
$grand_total = 0;

while ($item = $cart_result->fetch_assoc()) {
    $pdf->Cell(80, 10, $item['name'], 1);
    $pdf->Cell(30, 10, $item['qty'], 1);
    $pdf->Cell(30, 10, '$' . number_format($item['price'], 2), 1);
    $pdf->Cell(30, 10, '$' . number_format($item['total_cost'], 2), 1);
    $pdf->Ln();
    $grand_total += $item['total_cost'];
}

// Add grand total
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(140, 10, 'Grand Total:', 1);
$pdf->Cell(30, 10, '$' . number_format($grand_total, 2), 1);

// Add footer
$pdf->SetY(-15);
$pdf->SetFont('Arial', 'I', 8);
$pdf->Cell(0, 10, 'Generated on ' . date('Y-m-d H:i:s'), 0, 0, 'C');

// Output the PDF
$pdf->Output('D', 'cart_details.pdf');
?> 