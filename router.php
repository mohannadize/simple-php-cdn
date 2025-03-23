<?php
/**
 * Router for PHP's built-in web server
 * This file helps to emulate .htaccess behavior with PHP's built-in server
 */

// Set error display for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Current request URI
$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
);

// Root / route should go to upload.html
if ($uri === '/') {
    if (file_exists(__DIR__ . '/public/upload.html')) {
        readfile(__DIR__ . '/public/upload.html');
        exit;
    } else {
        echo '<h1>PHP Image CDN</h1><p>Setup the upload.html page to get started.</p>';
        exit;
    }
}

// Static file check - if the file exists in public, serve it directly
if (file_exists(__DIR__ . '/public' . $uri)) {
    // Get file extension to set correct content type
    $extension = pathinfo(__DIR__ . '/public' . $uri, PATHINFO_EXTENSION);
    $contentType = 'text/plain';
    
    // Set appropriate content type
    switch ($extension) {
        case 'html':
        case 'htm':
            $contentType = 'text/html';
            break;
        case 'css':
            $contentType = 'text/css';
            break;
        case 'js':
            $contentType = 'application/javascript';
            break;
        case 'json':
            $contentType = 'application/json';
            break;
        case 'jpg':
        case 'jpeg':
            $contentType = 'image/jpeg';
            break;
        case 'png':
            $contentType = 'image/png';
            break;
        case 'gif':
            $contentType = 'image/gif';
            break;
        case 'webp':
            $contentType = 'image/webp';
            break;
    }
    
    header('Content-Type: ' . $contentType);
    readfile(__DIR__ . '/public' . $uri);
    exit;
}

// Route special paths
if (preg_match('/^\/uploads\//', $uri)) {
    // Route to upload files
    $filename = basename($uri);
    $path = __DIR__ . '/public/uploads/' . $filename;
    
    if (file_exists($path)) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($path);
        header('Content-Type: ' . $mimeType);
        header('Cache-Control: public, max-age=31536000, immutable');
        header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + 31536000));
        header('Pragma: public');
        readfile($path);
        exit;
    }
    
    header('HTTP/1.0 404 Not Found');
    echo 'File not found: ' . htmlspecialchars($path);
    exit;
} 

if (preg_match('/^\/images\//', $uri)) {
    // Route to processed files
    $filename = basename($uri);
    $path = __DIR__ . '/public/images/' . $filename;
    
    if (file_exists($path)) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($path);
        header('Content-Type: ' . $mimeType);
        header('Cache-Control: public, max-age=31536000, immutable');
        header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + 31536000));
        header('Pragma: public');
        readfile($path);
        exit;
    }
    
    header('HTTP/1.0 404 Not Found');
    echo 'Processed image not found: ' . htmlspecialchars($path);
    exit;
}

// Set working directory to simulate the web server environment
chdir('public');

// If we got here, route everything to index.php
$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__ . '/public/index.php'; 