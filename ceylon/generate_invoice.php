<?php
session_start();
require('fpdf/fpdf.php');
include('connection.php');

// Check if supplier is logged in
if (!isset($_SESSION['supplier_id'])) {
    header("Location: supplierLogin.php");
    exit();
}

$supplier_id = $_SESSION['supplier_id'];

// Get the latest payment for the supplier
$payment_query = "SELECT sp.*, s.business_name, s.business_address, s.contact_number 
                 FROM supplier_payment sp 
                 JOIN suppliers s ON sp.supplier_name = s.business_name 
                 WHERE s.supplier_id = ? 
                 ORDER BY sp.payment_date DESC 
                 LIMIT 1";

$stmt = $conn->prepare($payment_query);
$stmt->bind_param("i", $supplier_id);
$stmt->execute();
$payment_result = $stmt->get_result();
$payment = $payment_result->fetch_assoc();

// Create new PDF document
$pdf = new FPDF();
$pdf->AddPage();

// Set font
$pdf->SetFont('Arial', 'B', 16);

// Title
$pdf->Cell(0, 10, 'CeylonCart Payment Invoice', 0, 1, 'C');
$pdf->Ln(10);

// Invoice details
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, 'Invoice #' . $payment['payment_id'], 0, 1);
$pdf->Cell(0, 10, 'Date: ' . date('Y-m-d H:i:s', strtotime($payment['payment_date'])), 0, 1);
$pdf->Ln(10);

// Supplier information
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Supplier Details:', 0, 1);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 7, $payment['business_name'], 0, 1);
$pdf->Cell(0, 7, $payment['business_address'], 0, 1);
$pdf->Cell(0, 7, $payment['contact_number'], 0, 1);
$pdf->Ln(10);

// Payment details
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Payment Details:', 0, 1);
$pdf->SetFont('Arial', '', 12);

// Create a table for payment details
$pdf->Cell(60, 10, 'Description', 1, 0, 'L');
$pdf->Cell(60, 10, 'Value', 1, 1, 'L');

$pdf->Cell(60, 10, 'Amount', 1, 0, 'L');
$pdf->Cell(60, 10, 'LKR ' . number_format($payment['amount'], 2), 1, 1, 'L');

$pdf->Cell(60, 10, 'Month', 1, 0, 'L');
$pdf->Cell(60, 10, $payment['month'], 1, 1, 'L');

$pdf->Cell(60, 10, 'NIC', 1, 0, 'L');
$pdf->Cell(60, 10, $payment['nic'], 1, 1, 'L');

$pdf->Cell(60, 10, 'Status', 1, 0, 'L');
$pdf->Cell(60, 10, 'Completed', 1, 1, 'L');

// Output the PDF directly to the browser
$pdf->Output('I', 'invoice_' . $payment['payment_id'] . '.pdf');
?> 