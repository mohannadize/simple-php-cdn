<?php

namespace App\Services;

use App\Config\Config;
use Intervention\Image\ImageManagerStatic as Image;
use Ramsey\Uuid\Uuid;

class ImageService
{
    private $config;
    private $uploadDir;
    private $processedDir;
    private $allowedExtensions;
    private $maxFileSize;

    public function __construct()
    {
        // Configure the image library first
        try {
            // Set default driver (GD or Imagick)
            Image::configure(['driver' => 'gd']);
        } catch (\Exception $e) {
            error_log('Error configuring image driver: ' . $e->getMessage());
        }

        $this->config = Config::getInstance();
        $this->uploadDir = $this->config->get('storage.upload_dir');
        $this->processedDir = $this->config->get('storage.processed_dir');
        $this->allowedExtensions = $this->config->get('storage.allowed_extensions');
        $this->maxFileSize = $this->config->get('storage.max_file_size');
        
        // Ensure directories exist
        $this->ensureDirectoriesExist();
    }

    public function upload(array $file): ?string
    {
        if (!$this->validateFile($file)) {
            error_log("File validation failed: " . json_encode($file));
            return null;
        }

        try {
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = Uuid::uuid4()->toString() . '.' . $extension;
            $destination = $this->uploadDir . '/' . $filename;
            
            // Check if we're dealing with a PSR-7 upload (tmp_name might not be a file path)
            if (isset($file['tmp_name'])) {
                // For traditional PHP uploads or if we have a file path
                if (file_exists($file['tmp_name'])) {
                    // Move the uploaded file
                    if (!copy($file['tmp_name'], $destination)) {
                        error_log("Failed to copy file from {$file['tmp_name']} to {$destination}");
                        return null;
                    }
                } else {
                    error_log("Temp file doesn't exist: {$file['tmp_name']}");
                    return null;
                }
            } else {
                error_log("Missing tmp_name in file data");
                return null;
            }
            
            return $filename;
        } catch (\Exception $e) {
            error_log("Upload exception: " . $e->getMessage());
            return null;
        }
    }

    public function process(string $filename, ?int $width = null, ?int $quality = null): ?string
    {
        $originalPath = $this->uploadDir . '/' . $filename;
        
        if (!file_exists($originalPath)) {
            error_log("Original image not found: {$originalPath}");
            return null;
        }

        $width = $width ?: $this->config->get('image.default_width');
        $quality = $quality ?: $this->config->get('image.default_quality');
        
        // Validate parameters
        $width = min($width, $this->config->get('image.max_width'));
        $quality = min(max($quality, 1), 100);
        
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $filenameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);
        
        // Create a unique name for the processed image
        $processedFilename = sprintf('%s_w%d_q%d.%s', 
            $filenameWithoutExt,
            $width, 
            $quality, 
            $extension
        );
        
        $processedPath = $this->processedDir . '/' . $processedFilename;
        
        // Check if processed image already exists
        if (file_exists($processedPath)) {
            return $processedFilename;
        }
        
        try {
            // Ensure the directory exists
            if (!is_dir($this->processedDir)) {
                error_log("Creating processed directory: {$this->processedDir}");
                if (!mkdir($this->processedDir, 0755, true)) {
                    error_log("Failed to create processed directory: {$this->processedDir}");
                    return null;
                }
            }
            
            // Check write permissions
            if (!is_writable($this->processedDir)) {
                error_log("Processed directory is not writable: {$this->processedDir}");
                return null;
            }
            
            // Process the image
            $image = Image::make($originalPath);
            
            // Check that we got an image
            if (!$image) {
                error_log("Failed to create image from: {$originalPath}");
                return null;
            }
            
            error_log("Image loaded successfully: {$originalPath}, dimensions: {$image->width()}x{$image->height()}");
            
            // Resize only if needed (when requested width is smaller than original)
            if ($width < $image->width()) {
                $image->resize($width, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
                error_log("Image resized to width: {$width}px");
            } else {
                error_log("No resize needed, requested width {$width}px is >= original width {$image->width()}px");
            }
            
            // Save with specified quality
            error_log("Saving processed image to: {$processedPath} with quality: {$quality}");
            $image->save($processedPath, $quality);
            
            // Verify the file was saved
            if (!file_exists($processedPath)) {
                error_log("Failed to save processed image: {$processedPath}");
                return null;
            }
            
            error_log("Image processed successfully: {$processedPath}");
            return $processedFilename;
        } catch (\Exception $e) {
            // Log error
            error_log("Image processing error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
            error_log("Stack trace: " . $e->getTraceAsString());
            return null;
        }
    }
    
    public function getImageUrl(string $filename, ?int $width = null, ?int $quality = null): ?string
    {
        $processedFilename = $this->process($filename, $width, $quality);
        
        if (!$processedFilename) {
            return null;
        }
        
        return $this->config->get('app.url') . '/images/' . $processedFilename;
    }
    
    public function getConfig(): Config
    {
        return $this->config;
    }
    
    private function validateFile(array $file): bool
    {
        // Check for upload errors
        if (isset($file['error']) && $file['error'] !== UPLOAD_ERR_OK) {
            error_log("Upload error code: " . $file['error']);
            return false;
        }
        
        // Check if necessary file data exists
        if (!isset($file['name']) || !isset($file['tmp_name']) || !isset($file['size'])) {
            error_log("Missing required file data keys");
            return false;
        }
        
        // Check file size
        if ($file['size'] > $this->maxFileSize) {
            error_log("File too large: {$file['size']} > {$this->maxFileSize}");
            return false;
        }
        
        // Check file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedExtensions)) {
            error_log("Invalid extension: {$extension}");
            return false;
        }
        
        // File doesn't exist
        if (!file_exists($file['tmp_name'])) {
            error_log("Temp file doesn't exist: {$file['tmp_name']}");
            return false;
        }
        
        // Validate that it's actually an image
        try {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file['tmp_name']);
            
            if (strpos($mimeType, 'image/') !== 0) {
                error_log("Invalid MIME type: {$mimeType}");
                return false;
            }
        } catch (\Exception $e) {
            error_log("MIME type check error: " . $e->getMessage());
            return false;
        }
        
        return true;
    }
    
    private function ensureDirectoriesExist(): void
    {
        // Create uploads directory
        if (!is_dir($this->uploadDir)) {
            error_log("Creating upload directory: {$this->uploadDir}");
            if (!mkdir($this->uploadDir, 0755, true)) {
                error_log("Failed to create upload directory: {$this->uploadDir}");
            }
        }
        
        // Check upload directory permissions
        if (!is_writable($this->uploadDir)) {
            error_log("Upload directory is not writable: {$this->uploadDir}");
        }
        
        // Create processed images directory
        if (!is_dir($this->processedDir)) {
            error_log("Creating processed directory: {$this->processedDir}");
            if (!mkdir($this->processedDir, 0755, true)) {
                error_log("Failed to create processed directory: {$this->processedDir}");
            }
        }
        
        // Check processed directory permissions
        if (!is_writable($this->processedDir)) {
            error_log("Processed directory is not writable: {$this->processedDir}");
        }
    }

    /**
     * Download and save an image from a URL
     * @param string $url The URL of the image to download
     * @return string|null The filename if successful, null otherwise
     */
    public function downloadFromUrl(string $url): ?string
    {
        try {
            // Create a temporary file
            $tempFile = tempnam(sys_get_temp_dir(), 'cdn_download_');
            
            // Download the file
            $imageData = file_get_contents($url);
            if ($imageData === false) {
                error_log("Failed to download image from URL: {$url}");
                return null;
            }
            
            // Save to temp file
            if (file_put_contents($tempFile, $imageData) === false) {
                error_log("Failed to save downloaded image to temp file");
                return null;
            }
            
            // Get file info
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($tempFile);
            
            // Validate mime type
            if (strpos($mimeType, 'image/') !== 0) {
                error_log("Invalid MIME type for downloaded file: {$mimeType}");
                unlink($tempFile);
                return null;
            }
            
            // Get file extension from mime type
            $extensions = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp'
            ];
            
            $extension = $extensions[$mimeType] ?? null;
            if (!$extension) {
                error_log("Unsupported image type: {$mimeType}");
                unlink($tempFile);
                return null;
            }
            
            // Create file array for upload method
            $file = [
                'name' => basename($url),
                'type' => $mimeType,
                'tmp_name' => $tempFile,
                'error' => UPLOAD_ERR_OK,
                'size' => filesize($tempFile)
            ];
            
            // Use existing upload method
            $filename = $this->upload($file);
            
            // Clean up temp file
            unlink($tempFile);
            
            return $filename;
        } catch (\Exception $e) {
            error_log("Error downloading image from URL: " . $e->getMessage());
            if (isset($tempFile) && file_exists($tempFile)) {
                unlink($tempFile);
            }
            return null;
        }
    }
} 