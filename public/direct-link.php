<?php
// This script directly serves an image file from the uploads directory
// It's a simple way to test if images are accessible without the Slim router

// Get the filename from the query string
$filename = $_GET['file'] ?? '';

// Validate filename (basic security check)
if (empty($filename) || !preg_match('/^[a-zA-Z0-9_\-.]+\.(jpg|jpeg|png|gif)$/i', $filename)) {
    header('HTTP/1.1 400 Bad Request');
    exit('Invalid filename');
}

// Path to uploads directory
$uploadsDir = __DIR__ . '/uploads';
$filePath = $uploadsDir . '/' . $filename;

// Check if file exists
if (!file_exists($filePath)) {
    header('HTTP/1.1 404 Not Found');
    exit('File not found: ' . $filePath);
}

// Get file info
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($filePath);

// Set the content type and serve the file
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: public, max-age=86400');

// Output the file contents
readfile($filePath);
?> 