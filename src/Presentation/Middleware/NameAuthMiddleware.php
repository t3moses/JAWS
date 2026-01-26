<?php

declare(strict_types=1);

namespace App\Presentation\Middleware;

use App\Presentation\Response\JsonResponse;

/**
 * Name-Based Authentication Middleware
 *
 * Extracts first_name and last_name from HTTP headers for authentication.
 * This is a simplified authentication system for the initial implementation.
 *
 * Headers:
 * - X-User-FirstName: First name of the user
 * - X-User-LastName: Last name of the user
 *
 * Future Enhancement: Replace with JWT/password-based authentication.
 */
class NameAuthMiddleware
{
    /**
     * Process authentication
     *
     * @return array|null Authentication data or null if failed
     */
    public function authenticate(): ?array
    {
        // Extract headers (case-insensitive)
        $headers = $this->getHeaders();

        $firstName = $headers['X-User-FirstName'] ?? $headers['x-user-firstname'] ?? null;
        $lastName = $headers['X-User-LastName'] ?? $headers['x-user-lastname'] ?? null;

        // Validate presence
        if (empty($firstName) || empty($lastName)) {
            return null;
        }

        // Sanitize inputs
        $firstName = trim($firstName);
        $lastName = trim($lastName);

        // Basic validation
        if (strlen($firstName) < 2 || strlen($lastName) < 2) {
            return null;
        }

        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
        ];
    }

    /**
     * Get all HTTP headers
     *
     * @return array
     */
    private function getHeaders(): array
    {
        if (function_exists('getallheaders')) {
            return getallheaders() ?: [];
        }

        // Fallback for servers that don't support getallheaders()
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) === 'HTTP_') {
                $header = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $headers[$header] = $value;
            }
        }
        return $headers;
    }

    /**
     * Create authentication failed response
     */
    public function authenticationFailed(): JsonResponse
    {
        return JsonResponse::error(
            'Authentication required. Please provide X-User-FirstName and X-User-LastName headers.',
            401
        );
    }
}
