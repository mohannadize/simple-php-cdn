<?php

namespace App\Middleware;

use App\Config\Config;
use App\Utils\ResponseHelper;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class AuthMiddleware implements MiddlewareInterface
{
    private $config;

    public function __construct()
    {
        $this->config = Config::getInstance();
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $privateKey = $this->config->get('security.private_key');
        
        // If no private key is configured, allow the request
        if (!$privateKey) {
            return $handler->handle($request);
        }

        // Get the Authorization header
        $authHeader = $request->getHeaderLine('Authorization');
        
        // Check if the header starts with "Bearer "
        if (!str_starts_with($authHeader, 'Bearer ')) {
            return ResponseHelper::error(
                new \Slim\Psr7\Response(),
                'Authorization header missing or invalid format',
                401
            );
        }

        // Extract the token
        $token = substr($authHeader, 7);
        
        // Verify the token matches our private key
        if ($token !== $privateKey) {
            return ResponseHelper::error(
                new \Slim\Psr7\Response(),
                'Invalid authorization token',
                401
            );
        }

        return $handler->handle($request);
    }
} 