<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\DTO\Request\LoginRequest;
use App\Application\DTO\Request\RegisterRequest;
use App\Application\UseCase\Auth\GetSessionUseCase;
use App\Application\UseCase\Auth\LoginUseCase;
use App\Application\UseCase\Auth\LogoutUseCase;
use App\Application\UseCase\Auth\RegisterUseCase;
use App\Presentation\Response\JsonResponse;

/**
 * Authentication Controller
 *
 * Handles user authentication endpoints:
 * - POST /api/auth/register - Register new user
 * - POST /api/auth/login - Login with email/password
 * - GET /api/auth/session - Get current session info
 * - POST /api/auth/logout - Logout current user
 */
class AuthController
{
    public function __construct(
        private RegisterUseCase $registerUseCase,
        private LoginUseCase $loginUseCase,
        private GetSessionUseCase $getSessionUseCase,
        private LogoutUseCase $logoutUseCase,
    ) {
    }

    /**
     * Register new user
     *
     * POST /api/auth/register
     *
     * @param array $body Request body
     * @return JsonResponse
     */
    public function register(array $body): JsonResponse
    {
        $request = RegisterRequest::fromArray($body);
        $response = $this->registerUseCase->execute($request);

        $data = $response->toArray();
        $data['message'] = 'Registration successful';

        return JsonResponse::success($data, 201);
    }

    /**
     * Login with email and password
     *
     * POST /api/auth/login
     *
     * @param array $body Request body
     * @return JsonResponse
     */
    public function login(array $body): JsonResponse
    {
        $request = LoginRequest::fromArray($body);
        $response = $this->loginUseCase->execute($request);

        return JsonResponse::success($response->toArray());
    }

    /**
     * Get current session information
     *
     * GET /api/auth/session
     *
     * @param array $auth Authentication context from JWT middleware
     * @return JsonResponse
     */
    public function getSession(array $auth): JsonResponse
    {
        $response = $this->getSessionUseCase->execute($auth['user_id']);

        return JsonResponse::success([
            'user' => $response->toArray(),
        ]);
    }

    /**
     * Logout current user
     *
     * POST /api/auth/logout
     *
     * Updates the user's last_logout timestamp for audit trail.
     * Note: JWT remains technically valid until expiration. Client must
     * delete the token from storage to complete logout.
     *
     * @param array $auth Authentication context from JWT middleware
     * @return JsonResponse
     */
    public function logout(array $auth): JsonResponse
    {
        $this->logoutUseCase->execute($auth['user_id']);

        return JsonResponse::success([
            'message' => 'Logout successful. Please delete your token.',
        ]);
    }
}
