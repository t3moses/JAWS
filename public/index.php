<?php

declare(strict_types=1);

/**
 * Application Entry Point
 *
 * This is the main entry point for the JAWS REST API.
 * All HTTP requests are routed through this file.
 */

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load configuration
$config = require __DIR__ . '/../config/config.php';

// Set timezone
date_default_timezone_set($config['app']['timezone']);

// Error reporting
if ($config['app']['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Load dependency injection container
$container = require __DIR__ . '/../config/container.php';

// Load routes
$routes = require __DIR__ . '/../config/routes.php';

// Initialize middleware
$corsMiddleware = new \App\Presentation\Middleware\CorsMiddleware($config['cors']);
$authMiddleware = new \App\Presentation\Middleware\NameAuthMiddleware();
$errorMiddleware = new \App\Presentation\Middleware\ErrorHandlerMiddleware();

// Apply CORS headers
$corsMiddleware->apply();

// Initialize router
$router = new \App\Presentation\Router($routes);

try {
    // Get request method and path
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    // Match route
    $match = $router->match($method, $path);

    if ($match === null) {
        $response = $router->notFound();
        $response->send();
        exit;
    }

    // Check authentication requirement
    $auth = null;
    if ($match['auth']) {
        $auth = $authMiddleware->authenticate();
        if ($auth === null) {
            $response = $authMiddleware->authenticationFailed();
            $response->send();
            exit;
        }
    }

    // Get request body
    $body = [];
    if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
        $rawBody = file_get_contents('php://input');
        $body = json_decode($rawBody, true) ?? [];
    }

    // Resolve controller from container
    $controller = $container->get($match['controller']);

    // Call controller action
    $action = $match['action'];
    $params = $match['params'];

    // Determine method signature and call accordingly
    $reflection = new \ReflectionMethod($controller, $action);
    $methodParams = $reflection->getParameters();

    $args = [];
    foreach ($methodParams as $param) {
        $paramName = $param->getName();

        if ($paramName === 'params') {
            $args[] = $params;
        } elseif ($paramName === 'body') {
            $args[] = $body;
        } elseif ($paramName === 'auth') {
            $args[] = $auth;
        }
    }

    $response = $controller->$action(...$args);

    // Send response
    if ($response instanceof \App\Presentation\Response\JsonResponse) {
        $response->send();
    } else {
        // Fallback for non-JsonResponse returns
        $fallbackResponse = \App\Presentation\Response\JsonResponse::success($response);
        $fallbackResponse->send();
    }

} catch (\Throwable $e) {
    // Handle all uncaught exceptions
    $response = $errorMiddleware->handleException($e);
    $response->send();
}
