<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get the file name from the query string
$file = $_GET["file"] . ".pdf";
$filepath = "invoices/" . $file;

// Check if file exists
if (!file_exists($filepath)) {
    die("File not found");
}

// Set headers for download
header("Content-Type: application/octet-stream");
header("Content-Disposition: attachment; filename=" . urlencode($file));   
header("Content-Type: application/download");
header("Content-Description: File Transfer");            
header("Content-Length: " . filesize($filepath));

// Clear output buffer
flush();

// Read the file in chunks
$fp = fopen($filepath, "r");
while (!feof($fp)) {
    echo fread($fp, 65536);
    flush(); // This is essential for large downloads
} 

fclose($fp);
?> 