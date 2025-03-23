<?php

namespace App\Config;

use Dotenv\Dotenv;

class Config
{
    private static $instance = null;
    private $config = [];

    private function __construct()
    {
        // Load environment variables
        $dotenv = Dotenv::createImmutable(dirname(__DIR__, 3));
        $dotenv->load();

        // Configure error logging
        $this->setupErrorLogging();

        // Set up configuration
        $this->config = [
            'app' => [
                'name' => $_ENV['APP_NAME'] ?? 'PHP-CDN',
                'env' => $_ENV['APP_ENV'] ?? 'local',
                'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'url' => $_ENV['APP_URL'] ?? 'http://localhost:8000',
            ],
            'storage' => [
                'upload_dir' => $_ENV['UPLOAD_DIR'] ?? 'public/uploads',
                'processed_dir' => $_ENV['PROCESSED_DIR'] ?? 'public/images',
                'max_file_size' => (int)($_ENV['MAX_FILE_SIZE'] ?? 10485760), // 10MB default
                'allowed_extensions' => explode(',', $_ENV['ALLOWED_EXTENSIONS'] ?? 'jpg,jpeg,png,gif,webp'),
            ],
            'security' => [
                'private_key' => $_ENV['PRIVATE_KEY'] ?? null,
            ],
            'image' => [
                'default_quality' => (int)($_ENV['DEFAULT_QUALITY'] ?? 80),
                'default_width' => (int)($_ENV['DEFAULT_WIDTH'] ?? 800),
                'max_width' => (int)($_ENV['MAX_WIDTH'] ?? 2000),
            ],
        ];
    }

    /**
     * Set up proper error logging
     */
    private function setupErrorLogging(): void
    {
        // Enable error reporting
        error_reporting(E_ALL);
        
        // In development mode, display errors
        if (isset($_ENV['APP_DEBUG']) && filter_var($_ENV['APP_DEBUG'], FILTER_VALIDATE_BOOLEAN)) {
            ini_set('display_errors', 1);
        } else {
            ini_set('display_errors', 0);
        }
        
        // Always log errors
        ini_set('log_errors', 1);
        
        // Set the log file path
        $logPath = dirname(__DIR__, 3) . '/logs';
        if (!is_dir($logPath)) {
            mkdir($logPath, 0755, true);
        }
        
        ini_set('error_log', $logPath . '/php-errors.log');
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get(string $key, $default = null)
    {
        $keys = explode('.', $key);
        $config = $this->config;

        foreach ($keys as $segment) {
            if (!isset($config[$segment])) {
                return $default;
            }
            $config = $config[$segment];
        }

        return $config;
    }
} 