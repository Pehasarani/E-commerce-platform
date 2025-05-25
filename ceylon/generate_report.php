<?php
session_start();
require('connection.php');
require('fpdf/fpdf.php');

if (!isset($_SESSION['supplier_id'])) {
    header("Location: supplierLogin.php");
    exit();
}

$supplier_id = $_SESSION['supplier_id'];
$business_email = $_SESSION['business_email'];

// Get supplier details
$supplier_query = "SELECT business_name FROM suppliers WHERE supplier_id = ?";
$stmt = $conn->prepare($supplier_query);
$stmt->bind_param("i", $supplier_id);
$stmt->execute();
$supplier_result = $stmt->get_result();
$supplier = $supplier_result->fetch_assoc();
$stmt->close();

// Get product details with sales quantity and current stock
$products_query = "SELECT 
                    p.product_id,
                    p.name as product_name,
                    p.no_of_products as current_stock,
                    COALESCE(SUM(oi.quantity), 0) as total_sold,
                    p.price as unit_price,
                    COALESCE(SUM(oi.quantity * p.price), 0) as total_revenue
                  FROM product p
                  LEFT JOIN order_items oi ON p.product_id = oi.product_id
                  LEFT JOIN orders o ON oi.order_id = o.id
                  WHERE p.top_email = ?
                  GROUP BY p.product_id, p.name, p.no_of_products, p.price
                  ORDER BY total_sold DESC";

$stmt = $conn->prepare($products_query);
$stmt->bind_param("s", $business_email);
$stmt->execute();
$products_result = $stmt->get_result();
$products = $products_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fix: Count total products correctly by counting the number of products in the array
$total_products = count($products);

// Get summary statistics
$summary_query = "SELECT 
                    COUNT(DISTINCT o.id) as total_orders,
                    COALESCE(SUM(oi.quantity), 0) as total_items_sold,
                    COALESCE(SUM(oi.quantity * p.price), 0) as total_revenue
                 FROM product p
                 LEFT JOIN order_items oi ON p.product_id = oi.product_id
                 LEFT JOIN orders o ON oi.order_id = o.id
                 WHERE p.top_email = ?";

$stmt = $conn->prepare($summary_query);
$stmt->bind_param("s", $business_email);
$stmt->execute();
$summary = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fix null values in summary
$summary['total_orders'] = $summary['total_orders'] ?? 0;
$summary['total_items_sold'] = $summary['total_items_sold'] ?? 0;
$summary['total_revenue'] = $summary['total_revenue'] ?? 0;

// Set total_products from our accurate count instead of the query
$summary['total_products'] = $total_products;

// Create PDF
class PDF extends FPDF {
    function Header() {
        // Logo
        $this->Image('./images/logo.png', 10, 10, 30);
        // Title
        $this->SetFont('Arial', 'B', 15);
        $this->Cell(0, 10, 'CeylonCart Supplier Report', 0, 1, 'C');
        $this->Ln(10);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . ' of {nb}', 0, 0, 'C');
        $this->Cell(0, 10, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 0, 'R');
    }
}

$pdf = new PDF();
$pdf->AliasNbPages(); // For page count in footer
$pdf->AddPage();

// Supplier Information
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Supplier: ' . $supplier['business_name'], 0, 1);
$pdf->Cell(0, 10, 'Report Generated: ' . date('Y-m-d H:i:s'), 0, 1);
$pdf->Ln(5);

// Summary Section
$pdf->SetFillColor(240, 240, 240);
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, 'Summary Statistics', 0, 1, '', true);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(100, 8, 'Total Orders:', 0, 0);
$pdf->Cell(0, 8, number_format($summary['total_orders']), 0, 1);
$pdf->Cell(100, 8, 'Total Products:', 0, 0);
$pdf->Cell(0, 8, number_format($summary['total_products']), 0, 1);
$pdf->Cell(100, 8, 'Total Items Sold:', 0, 0);
$pdf->Cell(0, 8, number_format($summary['total_items_sold']), 0, 1);
$pdf->Cell(100, 8, 'Total Revenue:', 0, 0);
$pdf->Cell(0, 8, 'Rs. ' . number_format($summary['total_revenue'], 2), 0, 1);
$pdf->Ln(10);

// Product Details Table
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, 'Product Details', 0, 1, '', true);

// Table Header
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetFillColor(200, 200, 200);
$pdf->Cell(60, 10, 'Product Name', 1, 0, 'C', true);
$pdf->Cell(30, 10, 'Current Stock', 1, 0, 'C', true);
$pdf->Cell(30, 10, 'Total Sold', 1, 0, 'C', true);
$pdf->Cell(35, 10, 'Unit Price', 1, 0, 'C', true);
$pdf->Cell(35, 10, 'Revenue', 1, 1, 'C', true);

// Table Content
$pdf->SetFont('Arial', '', 10);
$fill = false;
$pdf->SetFillColor(230, 230, 230);

if (count($products) > 0) {
    foreach ($products as $product) {
        // Check if line break is needed
        if ($pdf->GetY() > 250) {
            $pdf->AddPage();
            
            // Repeat table header on new page
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->SetFillColor(200, 200, 200);
            $pdf->Cell(60, 10, 'Product Name', 1, 0, 'C', true);
            $pdf->Cell(30, 10, 'Current Stock', 1, 0, 'C', true);
            $pdf->Cell(30, 10, 'Total Sold', 1, 0, 'C', true);
            $pdf->Cell(35, 10, 'Unit Price', 1, 0, 'C', true);
            $pdf->Cell(35, 10, 'Revenue', 1, 1, 'C', true);
            $pdf->SetFont('Arial', '', 10);
            $pdf->SetFillColor(230, 230, 230);
        }

        // Ensure values are numeric and handle null values
        $total_sold = $product['total_sold'] ?? 0;
        $unit_price = $product['unit_price'] ?? 0;
        $total_revenue = $product['total_revenue'] ?? 0;
        $current_stock = $product['current_stock'] ?? 0;

        // Truncate product name if too long
        $product_name = $product['product_name'];
        if (strlen($product_name) > 25) {
            $product_name = substr($product_name, 0, 22) . '...';
        }

        $pdf->Cell(60, 10, $product_name, 1, 0, 'L', $fill);
        $pdf->Cell(30, 10, number_format($current_stock), 1, 0, 'C', $fill);
        $pdf->Cell(30, 10, number_format($total_sold), 1, 0, 'C', $fill);
        $pdf->Cell(35, 10, 'Rs. ' . number_format($unit_price, 2), 1, 0, 'R', $fill);
        $pdf->Cell(35, 10, 'Rs. ' . number_format($total_revenue, 2), 1, 1, 'R', $fill);
        
        // Alternate row colors
        $fill = !$fill;
    }
} else {
    $pdf->Cell(190, 10, 'No products found', 1, 1, 'C');
}

// Add a chart or graph if needed
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, 'Top 5 Best-Selling Products', 0, 1, '', true);
$pdf->Ln(5);

// Table for top products
if (count($products) > 0) {
    // Sort products by total_sold in descending order
    usort($products, function($a, $b) {
        return $b['total_sold'] - $a['total_sold'];
    });

    // Get top 5 products or fewer if less than 5 products
    $top_products = array_slice($products, 0, min(5, count($products)));

    // Table Header
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetFillColor(200, 200, 200);
    $pdf->Cell(60, 10, 'Product Name', 1, 0, 'C', true);
    $pdf->Cell(40, 10, 'Total Sold', 1, 0, 'C', true);
    $pdf->Cell(45, 10, 'Total Revenue', 1, 1, 'C', true);
    
    // Table Content
    $pdf->SetFont('Arial', '', 10);
    $fill = false;
    $pdf->SetFillColor(230, 230, 230);
    
    foreach ($top_products as $product) {
        // Ensure values are numeric
        $total_sold = $product['total_sold'] ?? 0;
        $total_revenue = $product['total_revenue'] ?? 0;
        
        // Truncate product name if too long
        $product_name = $product['product_name'];
        if (strlen($product_name) > 25) {
            $product_name = substr($product_name, 0, 22) . '...';
        }
        
        $pdf->Cell(60, 10, $product_name, 1, 0, 'L', $fill);
        $pdf->Cell(40, 10, number_format($total_sold), 1, 0, 'C', $fill);
        $pdf->Cell(45, 10, 'Rs. ' . number_format($total_revenue, 2), 1, 1, 'R', $fill);
        
        // Alternate row colors
        $fill = !$fill;
    }
} else {
    $pdf->Cell(145, 10, 'No products found', 1, 1, 'C');
}

// Add inventory status section
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, 'Inventory Status', 0, 1, '', true);
$pdf->Ln(5);

// Table for inventory status
if (count($products) > 0) {
    // Sort products by current stock in ascending order to highlight low stock items
    usort($products, function($a, $b) {
        return $a['current_stock'] - $b['current_stock'];
    });

    // Table Header
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetFillColor(200, 200, 200);
    $pdf->Cell(60, 10, 'Product Name', 1, 0, 'C', true);
    $pdf->Cell(40, 10, 'Current Stock', 1, 0, 'C', true);
    $pdf->Cell(45, 10, 'Status', 1, 1, 'C', true);
    
    // Table Content
    $pdf->SetFont('Arial', '', 10);
    $fill = false;
    $pdf->SetFillColor(230, 230, 230);
    
    foreach ($products as $product) {
        // Get current stock
        $current_stock = $product['current_stock'] ?? 0;
        
        // Determine status based on stock level
        if ($current_stock <= 5) {
            $status = 'Critical - Restock Needed';
            $pdf->SetTextColor(255, 0, 0); // Red
        } elseif ($current_stock <= 15) {
            $status = 'Low - Consider Restocking';
            $pdf->SetTextColor(255, 165, 0); // Orange
        } else {
            $status = 'Good';
            $pdf->SetTextColor(0, 128, 0); // Green
        }
        
        // Truncate product name if too long
        $product_name = $product['product_name'];
        if (strlen($product_name) > 25) {
            $product_name = substr($product_name, 0, 22) . '...';
        }
        
        $pdf->Cell(60, 10, $product_name, 1, 0, 'L', $fill);
        $pdf->Cell(40, 10, number_format($current_stock), 1, 0, 'C', $fill);
        $pdf->Cell(45, 10, $status, 1, 1, 'C', $fill);
        
        // Reset text color
        $pdf->SetTextColor(0, 0, 0);
        
        // Alternate row colors
        $fill = !$fill;
    }
} else {
    $pdf->Cell(145, 10, 'No products found', 1, 1, 'C');
}

// Output the PDF
$pdf->Output('I', 'Supplier_Report_' . date('Y-m-d') . '.pdf');
?>