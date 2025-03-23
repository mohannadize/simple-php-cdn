<?php

namespace App\Utils;

use Psr\Http\Message\ResponseInterface;

class ResponseHelper
{
    /**
     * Create a JSON response
     *
     * @param ResponseInterface $response The PSR-7 response
     * @param mixed $data The data to encode as JSON
     * @param int $status The HTTP status code
     * @return ResponseInterface
     */
    public static function json(ResponseInterface $response, $data, int $status = 200): ResponseInterface
    {
        // Ensure we always return valid JSON
        $jsonData = json_encode($data, JSON_PRETTY_PRINT);
        
        // If JSON encoding fails, create a valid JSON error response instead
        if ($jsonData === false) {
            $jsonData = json_encode([
                'success' => false,
                'error' => 'Failed to encode response data as JSON',
                'status' => $status
            ]);
        }
        
        $response->getBody()->write($jsonData);
        
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
    
    /**
     * Create a success JSON response
     *
     * @param ResponseInterface $response The PSR-7 response
     * @param mixed $data The data to include
     * @param int $status The HTTP status code
     * @return ResponseInterface
     */
    public static function success(ResponseInterface $response, $data = null, int $status = 200): ResponseInterface
    {
        $payload = ['success' => true];
        
        if ($data !== null) {
            $payload['data'] = $data;
        }
        
        return self::json($response, $payload, $status);
    }
    
    /**
     * Create an error JSON response
     *
     * @param ResponseInterface $response The PSR-7 response
     * @param string $message The error message
     * @param int $status The HTTP status code
     * @return ResponseInterface
     */
    public static function error(ResponseInterface $response, string $message, int $status = 400): ResponseInterface
    {
        return self::json($response, [
            'success' => false,
            'error' => $message
        ], $status);
    }
} 