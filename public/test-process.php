<?php
/**
 * This is a simple test script to debug image processing
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load autoloader
require __DIR__ . '/../vendor/autoload.php';

// Initialize the app
$app = require_once __DIR__ . '/../src/bootstrap.php';

// Get ImageService instance
$container = $app->getContainer();
$imageService = $container->get('imageService');

// Filename to test (can be passed as query parameter)
$filename = $_GET['filename'] ?? 'freepik__enhance__63208.jpeg';

// Output as text for better debugging
header('Content-Type: text/plain');

echo "=== PHP Image CDN Test Script ===\n\n";

// Configuration info
echo "Configuration:\n";
echo "- Upload directory: " . $imageService->getConfig()->get('storage.upload_dir') . "\n";
echo "- Processed directory: " . $imageService->getConfig()->get('storage.processed_dir') . "\n";
echo "- Base URL: " . $imageService->getConfig()->get('app.url') . "\n\n";

// Check if original file exists
$originalPath = $imageService->getConfig()->get('storage.upload_dir') . '/' . $filename;
echo "Original file check:\n";
echo "- Path: $originalPath\n";
if (file_exists($originalPath)) {
    echo "- Status: EXISTS\n";
    $filesize = filesize($originalPath);
    echo "- Size: " . number_format($filesize) . " bytes\n";
    
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($originalPath);
    echo "- MIME type: $mimeType\n\n";
    
    // Process the image
    echo "Processing image...\n";
    try {
        $width = $_GET['width'] ?? 500;
        $quality = $_GET['quality'] ?? 80;
        echo "- Requested width: {$width}px\n";
        echo "- Requested quality: {$quality}%\n";
        
        $processedFilename = $imageService->process($filename, (int)$width, (int)$quality);
        
        if ($processedFilename) {
            echo "- Success! Processed file: $processedFilename\n";
            
            $processedPath = $imageService->getConfig()->get('storage.processed_dir') . '/' . $processedFilename;
            echo "- Processed path: $processedPath\n";
            
            if (file_exists($processedPath)) {
                echo "- Processed file exists: YES\n";
                echo "- Processed file size: " . number_format(filesize($processedPath)) . " bytes\n";
                
                // Get image URL
                $url = $imageService->getImageUrl($filename, (int)$width, (int)$quality);
                echo "- Image URL: $url\n\n";
                
                echo "Direct URLs to access the image:\n";
                echo "1. Original: " . $imageService->getConfig()->get('app.url') . "/uploads/$filename\n";
                echo "2. Processed: " . $imageService->getConfig()->get('app.url') . "/images/$processedFilename\n";
                echo "3. Via controller: " . $imageService->getConfig()->get('app.url') . "/image/$filename?width=$width&quality=$quality\n";
            } else {
                echo "- ERROR: Processed file does not exist at expected path!\n";
            }
        } else {
            echo "- ERROR: Processing failed!\n";
            echo "- Check server logs for more details.\n";
        }
    } catch (Exception $e) {
        echo "- EXCEPTION: " . $e->getMessage() . "\n";
        echo "- File: " . $e->getFile() . " (line " . $e->getLine() . ")\n";
        echo "- Stack trace:\n" . $e->getTraceAsString() . "\n";
    }
} else {
    echo "- Status: NOT FOUND\n";
    echo "- ERROR: Original file does not exist!\n";
    
    // List available files
    echo "\nAvailable files in upload directory:\n";
    $files = glob($imageService->getConfig()->get('storage.upload_dir') . '/*');
    if (count($files) > 0) {
        foreach ($files as $file) {
            echo "- " . basename($file) . "\n";
        }
    } else {
        echo "- No files found in upload directory.\n";
    }
}

echo "\n=== End of Test ===\n";
?> 