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

// Load environment variables from .env file
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad(); // Use safeLoad() to avoid errors if .env doesn't exist

// Putenv support - phpdotenv 5.x only populates $_ENV and $_SERVER by default
// We need to also populate getenv() for backward compatibility
foreach ($_ENV as $key => $value) {
    if (!getenv($key)) {
        putenv("{$key}={$value}");
    }
}

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
$jwtAuthMiddleware = $container->get(\App\Presentation\Middleware\JwtAuthMiddleware::class);
$errorMiddleware = new \App\Presentation\Middleware\ErrorHandlerMiddleware();

// Apply CORS headers
$corsMiddleware->apply();

// Initialize router
$router = new \App\Presentation\Router($routes);

try {
    // Get request method and path
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    // Serve frontend for non-API routes
    if (!str_starts_with($path, '/api')) {
        $frontendPath = __DIR__ . '/app/index.html';

        if (file_exists($frontendPath)) {
            // Serve the frontend SPA
            header('Content-Type: text/html; charset=UTF-8');
            readfile($frontendPath);
            exit;
        } else {
            // Frontend not found, show helpful message
            http_response_code(404);
            echo '<!DOCTYPE html>
<html>
<head>
    <title>JAWS - Frontend Not Found</title>
</head>
<body>
    <h1>Frontend Application Not Found</h1>
    <p>The frontend application is not installed. Please place your frontend files in <code>/public/app/</code>.</p>
    <p>API endpoints are available at <a href="/api/events">/api/events</a></p>
</body>
</html>';
            exit;
        }
    }

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
        // JWT authentication
        $auth = $jwtAuthMiddleware->authenticate();

        if ($auth === null) {
            $response = $jwtAuthMiddleware->authenticationFailed();
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
