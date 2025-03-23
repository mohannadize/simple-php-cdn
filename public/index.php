<?php

use App\Controllers\ImageController;
use App\Services\ImageService;
use App\Utils\ResponseHelper;
use App\Middleware\AuthMiddleware;
use Slim\Factory\AppFactory;
use Slim\Psr7\UploadedFile;
use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/../vendor/autoload.php';

// Create Container
$container = new Container();
$container->set('imageService', function () {
    return new ImageService();
});

$container->set(ImageController::class, function ($container) {
    return new ImageController(
        $container->get('imageService')
    );
});

// Create App
AppFactory::setContainer($container);
$app = AppFactory::create();

// Add error middleware with detailed error display in development
$displayErrorDetails = true; // Set to false in production
$app->addErrorMiddleware($displayErrorDetails, true, true);

// Add homepage route - check if upload.html exists and redirect to it
$app->get('/', function (Request $request, Response $response) {
    if (file_exists(__DIR__ . '/upload.html')) {
        return $response
            ->withHeader('Location', '/upload.html')
            ->withStatus(302);
    } else {
        // If upload.html doesn't exist, show a default page
        $response->getBody()->write('
            <h1>PHP Image CDN</h1>
            <p>API endpoints:</p>
            <ul>
                <li>POST /upload - Upload an image</li>
                <li>GET /image/{filename} - Retrieve an image with optional width and quality parameters</li>
            </ul>
        ');
        return $response->withHeader('Content-Type', 'text/html');
    }
});

// Add diagnostic routes
$app->get('/test-direct', function (Request $request, Response $response) {
    $filename = $request->getQueryParams()['file'] ?? 'freepik__enhance__63208.jpeg';
    $path = __DIR__ . '/uploads/' . $filename;
    
    if (file_exists($path)) {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $contentType = $finfo->file($path);
        
        $response = $response->withHeader('Content-Type', $contentType);
        $response->getBody()->write(file_get_contents($path));
        return $response;
    } else {
        $response->getBody()->write("File not found: " . htmlspecialchars($path));
        return $response->withStatus(404);
    }
});

// Define API routes
$app->post('/upload', [ImageController::class, 'upload'])->add(new AuthMiddleware());
$app->get('/image/{filename}', [ImageController::class, 'serve']);

// Add a catch-all route for 404 errors
$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function (Request $request, Response $response) {
    $uri = $request->getUri()->getPath();
    
    // More descriptive error for image paths
    if (strpos($uri, '/image/') === 0) {
        $filename = substr($uri, 7); // Remove '/image/' from the path
        $uploadPath = __DIR__ . '/uploads/' . $filename;
        $processedDir = __DIR__ . '/images/';
        
        $response->getBody()->write('
            <h1>Image Not Found</h1>
            <p>The requested image "' . htmlspecialchars($filename) . '" could not be found or processed.</p>
            <h2>Debugging Information:</h2>
            <ul>
                <li>Original upload path: ' . htmlspecialchars($uploadPath) . ' (exists: ' . (file_exists($uploadPath) ? 'Yes' : 'No') . ')</li>
                <li>Processed images directory: ' . htmlspecialchars($processedDir) . ' (exists: ' . (is_dir($processedDir) ? 'Yes' : 'No') . ', writable: ' . (is_writable($processedDir) ? 'Yes' : 'No') . ')</li>
            </ul>
            <p>Possible solutions:</p>
            <ol>
                <li>Make sure the file was properly uploaded</li>
                <li>Check that the URL is correct</li>
                <li>Verify that the processed images directory exists and is writable</li>
                <li>Use the <a href="/test-process.php">testing tool</a> to diagnose image processing</li>
            </ol>
            <p><a href="/">Return to Home</a></p>
        ');
    } else {
        $response->getBody()->write('
            <h1>Page Not Found</h1>
            <p>The page you are looking for (' . htmlspecialchars($uri) . ') could not be found. Please check the URL and try again.</p>
            <p><a href="/">Return to Home</a></p>
        ');
    }
    
    return $response
        ->withHeader('Content-Type', 'text/html')
        ->withStatus(404);
});

// Run app
$app->run(); 