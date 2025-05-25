<?php
session_start();
require('connection.php');
require('fpdf/fpdf.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get filter values from GET
$item_name = isset($_GET['item_name']) ? trim($_GET['item_name']) : '';
$start_date = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';
$min_value = isset($_GET['min_value']) ? trim($_GET['min_value']) : '';
$max_value = isset($_GET['max_value']) ? trim($_GET['max_value']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';

// Build the query (same as in orderhistory.php)
$query = "SELECT o.*, 
          GROUP_CONCAT(p.name SEPARATOR '||') as product_names,
          GROUP_CONCAT(oi.quantity SEPARATOR '||') as quantities,
          GROUP_CONCAT(oi.price SEPARATOR '||') as prices,
          GROUP_CONCAT(p.product_images SEPARATOR '||') as product_images
          FROM orders o 
          LEFT JOIN order_items oi ON o.id = oi.order_id 
          LEFT JOIN product p ON oi.product_id = p.product_id 
          WHERE o.user_id = ?";

$params = [$user_id];
$types = "i";

if (!empty($status)) {
    $query .= " AND o.status = ?";
    $params[] = $status;
    $types .= "s";
}

if (!empty($item_name)) {
    $query .= " AND p.name LIKE ?";
    $params[] = "%$item_name%";
    $types .= "s";
}

if (!empty($start_date)) {
    $query .= " AND DATE(o.created_at) >= ?";
    $params[] = $start_date;
    $types .= "s";
}

if (!empty($end_date)) {
    $query .= " AND DATE(o.created_at) <= ?";
    $params[] = $end_date;
    $types .= "s";
}

if (!empty($min_value)) {
    $query .= " AND o.amount >= ?";
    $params[] = $min_value;
    $types .= "d";
}

if (!empty($max_value)) {
    $query .= " AND o.amount <= ?";
    $params[] = $max_value;
    $types .= "d";
}

$query .= " GROUP BY o.id ORDER BY o.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Get user details
$user_query = "SELECT username, email FROM customers WHERE id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();

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
$pdf->Cell(0, 10, 'ORDER HISTORY REPORT', 0, 1, 'C');
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, 'Generated on: ' . date('F j, Y'), 0, 1, 'R');
$pdf->Ln(5);

// User Information
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'User Information:', 0, 1);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 8, 'Name: ' . $user['username'], 0, 1);
$pdf->Cell(0, 8, 'Email: ' . $user['email'], 0, 1);
$pdf->Ln(5);

// Filter Summary
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Filter Summary:', 0, 1);
$pdf->SetFont('Arial', '', 12);

if (!empty($status)) $pdf->Cell(0, 8, 'Status: ' . $status, 0, 1);
if (!empty($item_name)) $pdf->Cell(0, 8, 'Item Name: ' . $item_name, 0, 1);
if (!empty($start_date)) $pdf->Cell(0, 8, 'Start Date: ' . $start_date, 0, 1);
if (!empty($end_date)) $pdf->Cell(0, 8, 'End Date: ' . $end_date, 0, 1);
if (!empty($min_value)) $pdf->Cell(0, 8, 'Min Value: Rs.' . number_format($min_value, 2), 0, 1);
if (!empty($max_value)) $pdf->Cell(0, 8, 'Max Value: Rs.' . number_format($max_value, 2), 0, 1);
$pdf->Ln(10);

// Orders Table Header
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(30, 10, 'Order ID', 1, 0, 'C');
$pdf->Cell(40, 10, 'Date', 1, 0, 'C');
$pdf->Cell(40, 10, 'Status', 1, 0, 'C');
$pdf->Cell(80, 10, 'Items', 1, 1, 'C');

// Orders Table Content
$pdf->SetFont('Arial', '', 10);
$total_amount = 0;

while ($order = $result->fetch_assoc()) {
    $total_amount += $order['amount'];
    
    // Handle long product names
    $product_names = explode('||', $order['product_names']);
    $quantities = explode('||', $order['quantities']);
    $items_text = '';
    
    for ($i = 0; $i < count($product_names); $i++) {
        if (!empty($product_names[$i])) {
            $items_text .= $product_names[$i] . ' (x' . $quantities[$i] . ")\n";
        }
    }
    
    // Truncate items text if too long
    if (strlen($items_text) > 60) {
        $items_text = substr($items_text, 0, 57) . '...';
    }
    
    $pdf->Cell(30, 10, '#' . $order['id'], 1, 0, 'C');
    $pdf->Cell(40, 10, date('M d, Y', strtotime($order['created_at'])), 1, 0, 'C');
    
    // Set status color based on status
    switch(strtolower($order['status'])) {
        case 'pending':
            $pdf->SetTextColor(146, 64, 14); // #92400E - dark amber
            $pdf->SetFillColor(254, 243, 199); // #FEF3C7 - light amber
            break;
        case 'in transit':
            $pdf->SetTextColor(6, 95, 70); // #065F46 - dark green
            $pdf->SetFillColor(209, 250, 229); // #D1FAE5 - light green
            break;
        case 'complete':
            $pdf->SetTextColor(6, 95, 70); // #065F46 - dark green
            $pdf->SetFillColor(209, 250, 229); // #D1FAE5 - light green
            break;
        default:
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFillColor(255, 255, 255);
    }
    
    $pdf->Cell(40, 10, $order['status'], 1, 0, 'C', true);
    
    // Reset colors for the next cell
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(255, 255, 255);
    
    $pdf->Cell(80, 10, $items_text, 1, 1, 'L');
}

// Total Amount
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(150, 10, 'Total Amount:', 1, 0, 'R');
$pdf->Cell(40, 10, 'Rs.' . number_format($total_amount, 2), 1, 1, 'R');

// Thank you message
$pdf->Ln(10);
$pdf->SetFont('Arial', 'I', 12);
$pdf->Cell(0, 10, 'Thank you for using CeylonCart!', 0, 1, 'C');
$pdf->Cell(0, 10, 'For any questions, please contact support@ceyloncart.com', 0, 1, 'C');

// Output PDF
$pdf->Output('I', 'CeylonCart_Order_History_' . date('Y-m-d') . '.pdf');
?> 