<?php

namespace App\Controllers;

use App\Services\ImageService;
use App\Utils\ResponseHelper;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\UploadedFileInterface;

class ImageController
{
    private $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    public function upload(Request $request, Response $response): Response
    {
        try {
            $uploadedFiles = $request->getUploadedFiles();
            
            // Debug: Check if we have uploaded files
            if (empty($uploadedFiles)) {
                return ResponseHelper::error($response, 'No files were uploaded', 400);
            }
            
            if (empty($uploadedFiles['image'])) {
                return ResponseHelper::error($response, 'No image field in the upload form', 400);
            }
            
            /** @var UploadedFileInterface $uploadedFile */
            $uploadedFile = $uploadedFiles['image'];
            
            // Debug: Check error code from upload
            if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
                $errorMessages = [
                    UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
                    UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form',
                    UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
                    UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                    UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload',
                ];
                
                $errorCode = $uploadedFile->getError();
                $errorMessage = $errorMessages[$errorCode] ?? 'Unknown upload error';
                
                return ResponseHelper::error($response, "Upload failed: {$errorMessage} (Code: {$errorCode})", 400);
            }
            
            // Create a temp file to work with
            $tempFile = tempnam(sys_get_temp_dir(), 'cdn_upload_');
            $uploadedFile->moveTo($tempFile);
            
            // Create a file array for our service
            $file = [
                'name' => $uploadedFile->getClientFilename(),
                'type' => $uploadedFile->getClientMediaType(),
                'tmp_name' => $tempFile,
                'error' => $uploadedFile->getError(),
                'size' => $uploadedFile->getSize(),
            ];
            
            // Debug: Verify file data is complete
            foreach (['name', 'type', 'tmp_name', 'error', 'size'] as $key) {
                if (empty($file[$key]) && $key !== 'error') {
                    return ResponseHelper::error(
                        $response, 
                        "Upload issue: Missing {$key} in file data. Upload data: " . json_encode($file), 
                        500
                    );
                }
            }
            
            $filename = $this->imageService->upload($file);
            
            if (!$filename) {
                // Clean up the temp file
                if (file_exists($tempFile)) {
                    unlink($tempFile);
                }
                return ResponseHelper::error($response, 'Failed to process image', 500);
            }
            
            // Clean up the temp file
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
            
            $baseUrl = $request->getUri()->getScheme() . '://' . $request->getUri()->getHost();
            if ($request->getUri()->getPort()) {
                $baseUrl .= ':' . $request->getUri()->getPort();
            }
            
            $imageUrl = $baseUrl . '/image/' . $filename;
            
            return ResponseHelper::json($response, [
                'success' => true,
                'filename' => $filename,
                'url' => $imageUrl,
                'usage' => [
                    'original' => $imageUrl,
                    'resize' => $imageUrl . '?w=500',
                    'quality' => $imageUrl . '?q=75',
                    'both' => $imageUrl . '?w=500&q=75',
                ],
            ]);
        } catch (\Exception $e) {
            // Log the exception
            error_log('Upload error: ' . $e->getMessage());
            return ResponseHelper::error(
                $response, 
                'Error during upload: ' . $e->getMessage(), 
                500
            );
        }
    }

    public function serve(Request $request, Response $response, array $args): Response
    {
        $filename = $args['filename'] ?? null;
        
        if (!$filename) {
            return ResponseHelper::error($response, 'Image not found - filename not provided', 404);
        }
        
        // Validate the filename format
        if (!preg_match('/^[a-zA-Z0-9_\-.]+\.(jpg|jpeg|png|gif)$/i', $filename)) {
            return ResponseHelper::error(
                $response, 
                'Invalid filename format: ' . $filename,
                400
            );
        }
        
        // Log the request
        error_log("ImageController::serve - Serving image: {$filename}");
        
        // Check if original file exists first
        $originalPath = $this->imageService->getConfig()->get('storage.upload_dir') . '/' . $filename;
        if (!file_exists($originalPath)) {
            error_log("Original image not found at: {$originalPath}");
            
            return ResponseHelper::error(
                $response, 
                "Image not found",
                404
            );
        }
        
        $queryParams = $request->getQueryParams();
        $width = isset($queryParams['w']) ? (int)$queryParams['w'] : null;
        $quality = isset($queryParams['q']) ? (int)$queryParams['q'] : null;
        $format = isset($queryParams['f']) ? strtolower($queryParams['f']) : null;
        
        error_log("Processing image with width: " . ($width ?: 'default') . ", quality: " . ($quality ?: 'default') . ", format: " . ($format ?: 'webp'));
        
        $processedFilename = $this->imageService->process($filename, $width, $quality, $format);
        
        if (!$processedFilename) {
            error_log("Failed to process image: {$filename}");
            return ResponseHelper::error($response, 'Image could not be processed', 500);
        }
        
        $processedDir = $this->imageService->getConfig()->get('storage.processed_dir');
        $imagePath = $processedDir . '/' . $processedFilename;
        
        if (!file_exists($imagePath)) {
            error_log("Processed image not found at path: {$imagePath}");
            return ResponseHelper::error(
                $response, 
                "Processed image not found at: {$imagePath}",
                404
            );
        }
        
        error_log("Successfully serving processed image: {$processedFilename}");
        
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $contentType = $finfo->file($imagePath);
        
        $response = $response->withHeader('Content-Type', $contentType);
        $response = $response->withHeader('Cache-Control', 'public, max-age=31536000, immutable');
        $response->withHeader('Expires', gmdate('D, d M Y H:i:s \G\M\T', time() + 31536000));
        $response->withHeader('Pragma', 'public');
        
        // If it's a versioned filename (contains hash), add immutable
        if (preg_match('/[a-f0-9]{32}/', $filename)) {
            $response = $response->withHeader('Cache-Control', 'public, max-age=31536000, immutable');
        }
        
        $response->getBody()->write(file_get_contents($imagePath));
        return $response;
    }

    /**
     * Upload an image from a URL
     */
    public function uploadFromUrl(Request $request, Response $response): Response
    {
        try {
            error_log("uploadFromUrl called - Request method: " . $request->getMethod());
            error_log("Request headers: " . json_encode($request->getHeaders()));
            
            $data = json_decode($request->getBody()->getContents(), true);
            error_log("Request body: " . json_encode($data));
            
            if (!isset($data['url']) || empty($data['url'])) {
                return ResponseHelper::error($response, 'URL parameter is required', 400);
            }
            
            $url = filter_var($data['url'], FILTER_VALIDATE_URL);
            if (!$url) {
                return ResponseHelper::error($response, 'Invalid URL format', 400);
            }
            
            $filename = $this->imageService->downloadFromUrl($url);
            
            if (!$filename) {
                return ResponseHelper::error($response, 'Failed to download or process image from URL', 500);
            }
            
            $baseUrl = $request->getUri()->getScheme() . '://' . $request->getUri()->getHost();
            if ($request->getUri()->getPort()) {
                $baseUrl .= ':' . $request->getUri()->getPort();
            }
            
            $imageUrl = $baseUrl . '/image/' . $filename;
            
            return ResponseHelper::json($response, [
                'success' => true,
                'filename' => $filename,
                'url' => $imageUrl,
                'usage' => [
                    'original' => $imageUrl,
                    'resize' => $imageUrl . '?w=500',
                    'quality' => $imageUrl . '?q=75',
                    'both' => $imageUrl . '?w=500&q=75',
                ],
            ]);
        } catch (\Exception $e) {
            error_log('URL upload error: ' . $e->getMessage());
            return ResponseHelper::error(
                $response,
                'Error during URL upload: ' . $e->getMessage(),
                500
            );
        }
    }
} 