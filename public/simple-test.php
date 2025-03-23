<?php
// A simple test that doesn't depend on Slim or routing

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load autoloader
require __DIR__ . '/../vendor/autoload.php';

// Configure paths directly
$uploadDir = __DIR__ . '/uploads';
$processedDir = __DIR__ . '/images';
$filename = $_GET['filename'] ?? 'freepik__enhance__63208.jpeg';

// Output as text for better debugging
header('Content-Type: text/plain');

echo "=== PHP Image CDN Simple Test ===\n\n";

// Check if original file exists
$originalPath = $uploadDir . '/' . $filename;
echo "Original file check:\n";
echo "- Path: $originalPath\n";

if (file_exists($originalPath)) {
    echo "- Status: EXISTS\n";
    $filesize = filesize($originalPath);
    echo "- Size: " . number_format($filesize) . " bytes\n";
    
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($originalPath);
    echo "- MIME type: $mimeType\n\n";
    
    // Process the image directly with Intervention\Image
    echo "Processing image directly with Intervention\Image...\n";
    try {
        // Parameters from query string
        $width = isset($_GET['width']) ? (int)$_GET['width'] : 500;
        $quality = isset($_GET['quality']) ? (int)$_GET['quality'] : 80;
        echo "- Requested width: {$width}px\n";
        echo "- Requested quality: {$quality}%\n";
        
        // Create processed filename
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $filenameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);
        $processedFilename = sprintf('%s_w%d_q%d.%s', 
            $filenameWithoutExt,
            $width, 
            $quality, 
            $extension
        );
        $processedPath = $processedDir . '/' . $processedFilename;
        
        echo "- Target processed file: $processedFilename\n";
        
        // Check if already processed
        if (file_exists($processedPath)) {
            echo "- Processed file already exists\n";
        } else {
            echo "- Processing image...\n";
            
            // Use Intervention Image directly
            require_once __DIR__ . '/../vendor/autoload.php';
            
            // Configure driver
            \Intervention\Image\ImageManagerStatic::configure(['driver' => 'gd']);
            
            // Load image
            $image = \Intervention\Image\ImageManagerStatic::make($originalPath);
            echo "- Image loaded successfully\n";
            echo "- Original dimensions: {$image->width()}x{$image->height()}\n";
            
            // Resize only if needed
            if ($width < $image->width()) {
                $image->resize($width, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
                echo "- Image resized to width: {$width}px\n";
            } else {
                echo "- No resize needed, keeping original width: {$image->width()}px\n";
            }
            
            // Create directory if not exists
            if (!is_dir($processedDir)) {
                echo "- Creating processed directory: $processedDir\n";
                if (!mkdir($processedDir, 0755, true)) {
                    echo "- ERROR: Failed to create processed directory!\n";
                    exit;
                }
            }
            
            // Check permissions
            if (!is_writable($processedDir)) {
                echo "- ERROR: Processed directory not writable!\n";
                exit;
            }
            
            // Save image
            echo "- Saving to: $processedPath\n";
            $image->save($processedPath, $quality);
            
            // Verify saved
            if (file_exists($processedPath)) {
                echo "- Successfully saved processed image\n";
            } else {
                echo "- ERROR: Failed to save processed image!\n";
                exit;
            }
        }
        
        // Image URLs
        $baseUrl = "http://localhost:8000";
        echo "\nImage URLs:\n";
        echo "1. Original: $baseUrl/uploads/$filename\n";
        echo "2. Processed: $baseUrl/images/$processedFilename\n";
        echo "3. Direct fetch: $baseUrl/direct-link.php?file=$filename\n";
        
    } catch (Exception $e) {
        echo "- ERROR: " . $e->getMessage() . "\n";
        echo "- File: " . $e->getFile() . " (line " . $e->getLine() . ")\n";
        echo "- Stack trace:\n" . $e->getTraceAsString() . "\n";
    }
} else {
    echo "- Status: NOT FOUND\n";
    echo "- ERROR: Original file does not exist!\n";
    
    // List available files
    echo "\nAvailable files in upload directory:\n";
    $files = glob($uploadDir . '/*');
    if (count($files) > 0) {
        foreach ($files as $file) {
            if (basename($file) != '.gitkeep') {
                echo "- " . basename($file) . "\n";
            }
        }
    } else {
        echo "- No files found in upload directory.\n";
    }
}

echo "\n=== End of Test ===\n"; 