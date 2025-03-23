<?php

use App\Config\Config;
use App\Controllers\ImageController;
use App\Services\ImageService;
use Slim\Factory\AppFactory;
use DI\Container;

// Load configuration
$config = Config::getInstance();

// Create Container
$container = new Container();

// Register services
$container->set('config', function () use ($config) {
    return $config;
});

$container->set('imageService', function ($container) {
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

// Return the app
return $app; 