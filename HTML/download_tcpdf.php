<?php
// URL of the TCPDF zip file
$url = 'https://github.com/tecnickcom/TCPDF/archive/refs/tags/6.6.2.zip';
$zipFile = 'tcpdf.zip';
$extractPath = 'tcpdf';

// Download the file
file_put_contents($zipFile, file_get_contents($url));

// Create a new ZipArchive instance
$zip = new ZipArchive;

// Open the downloaded zip file
if ($zip->open($zipFile) === TRUE) {
    // Extract the contents to the specified directory
    $zip->extractTo($extractPath);
    $zip->close();
    
    // Move the contents from the extracted folder to the tcpdf directory
    $source = $extractPath . '/TCPDF-6.6.2';
    $destination = $extractPath;
    
    // Get all files and directories in the source
    $files = scandir($source);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            rename($source . '/' . $file, $destination . '/' . $file);
        }
    }
    
    // Remove the empty source directory
    rmdir($source);
    
    // Remove the zip file
    unlink($zipFile);
    
    echo "TCPDF has been successfully downloaded and installed!";
} else {
    echo "Failed to extract TCPDF";
}
?> 