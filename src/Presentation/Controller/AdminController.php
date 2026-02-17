<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\UseCase\Admin\GetMatchingDataUseCase;
use App\Application\UseCase\Admin\SendNotificationsUseCase;
use App\Application\UseCase\Admin\GetConfigUseCase;
use App\Application\UseCase\Admin\GetAllUsersUseCase;
use App\Application\UseCase\Admin\SetUserAdminUseCase;
use App\Application\UseCase\Season\UpdateConfigUseCase;
use App\Application\DTO\Request\UpdateConfigRequest;
use App\Application\Exception\EventNotFoundException;
use App\Application\Exception\FlotillaNotFoundException;
use App\Application\Exception\ValidationException;
use App\Domain\ValueObject\EventId;
use App\Presentation\Response\JsonResponse;

/**
 * Admin Controller
 *
 * Handles administrative endpoints (authenticated admin access).
 */
class AdminController
{
    public function __construct(
        private GetMatchingDataUseCase $getMatchingDataUseCase,
        private SendNotificationsUseCase $sendNotificationsUseCase,
        private GetConfigUseCase $getConfigUseCase,
        private UpdateConfigUseCase $updateConfigUseCase,
        private GetAllUsersUseCase $getAllUsersUseCase,
        private SetUserAdminUseCase $setUserAdminUseCase,
    ) {
    }

    /**
     * Check if the current user is an admin
     *
     * @param array $auth Authentication context from JWT middleware
     * @return bool
     */
    private function isAdmin(array $auth): bool
    {
        return isset($auth['is_admin']) && $auth['is_admin'] === true;
    }

    /**
     * GET /api/admin/matching/{eventId}
     *
     * Returns matching data for an event (capacity analysis).
     *
     * @param array $params Route parameters
     * @param array $auth Authentication context
     */
    public function getMatchingData(array $params, array $auth): JsonResponse
    {
        if (!$this->isAdmin($auth)) {
            return JsonResponse::error('Admin privileges required', 403);
        }

        try {
            $eventId = EventId::fromString($params['eventId']);
            $result = $this->getMatchingDataUseCase->execute($eventId);

            return JsonResponse::success($result);
        } catch (EventNotFoundException $e) {
            return JsonResponse::notFound($e->getMessage());
        } catch (\Exception $e) {
            return JsonResponse::serverError($e->getMessage());
        }
    }

    /**
     * POST /api/admin/notifications/{eventId}
     *
     * Sends email notifications and calendar invites for an event.
     *
     * @param array $params Route parameters
     * @param array $body Request body
     * @param array $auth Authentication context
     */
    public function sendNotifications(array $params, array $body, array $auth): JsonResponse
    {
        if (!$this->isAdmin($auth)) {
            return JsonResponse::error('Admin privileges required', 403);
        }

        try {
            $eventId = EventId::fromString($params['eventId']);
            $includeCalendar = $body['include_calendar'] ?? true;

            $result = $this->sendNotificationsUseCase->execute($eventId, $includeCalendar);

            return JsonResponse::success($result);
        } catch (EventNotFoundException | FlotillaNotFoundException $e) {
            return JsonResponse::notFound($e->getMessage());
        } catch (\Exception $e) {
            return JsonResponse::serverError($e->getMessage());
        }
    }

    /**
     * GET /api/admin/config
     *
     * Returns current season configuration.
     *
     * @param array $auth Authentication context
     */
    public function getConfig(array $auth): JsonResponse
    {
        if (!$this->isAdmin($auth)) {
            return JsonResponse::error('Admin privileges required', 403);
        }

        try {
            $result = $this->getConfigUseCase->execute();

            return JsonResponse::success($result);
        } catch (\Exception $e) {
            return JsonResponse::serverError($e->getMessage());
        }
    }

    /**
     * PATCH /api/admin/config
     *
     * Updates season configuration.
     *
     * @param array $body Request body
     * @param array $auth Authentication context
     */
    public function updateConfig(array $body, array $auth): JsonResponse
    {
        if (!$this->isAdmin($auth)) {
            return JsonResponse::error('Admin privileges required', 403);
        }

        try {
            $request = new UpdateConfigRequest(
                source: $body['source'] ?? null,
                simulatedDate: $body['simulated_date'] ?? null,
                year: isset($body['year']) ? (int)$body['year'] : null,
                startTime: $body['start_time'] ?? null,
                finishTime: $body['finish_time'] ?? null,
                blackoutFrom: $body['blackout_from'] ?? null,
                blackoutTo: $body['blackout_to'] ?? null,
            );

            $result = $this->updateConfigUseCase->execute($request);

            return JsonResponse::success($result);
        } catch (ValidationException $e) {
            return JsonResponse::error($e->getMessage(), 400, $e->getErrors());
        } catch (\Exception $e) {
            return JsonResponse::serverError($e->getMessage());
        }
    }

    /**
     * GET /api/admin/users
     *
     * Returns a list of all registered users (no password hashes).
     *
     * @param array $auth Authentication context
     */
    public function getUsers(array $auth): JsonResponse
    {
        if (!$this->isAdmin($auth)) {
            return JsonResponse::error('Admin privileges required', 403);
        }

        try {
            $result = $this->getAllUsersUseCase->execute();

            return JsonResponse::success($result);
        } catch (\Exception $e) {
            return JsonResponse::serverError($e->getMessage());
        }
    }

    /**
     * PATCH /api/admin/users/{userId}/admin
     *
     * Grants or revokes admin privileges for a user.
     *
     * @param array $params Route parameters (userId)
     * @param array $body   Request body (is_admin boolean)
     * @param array $auth   Authentication context
     */
    public function setUserAdmin(array $params, array $body, array $auth): JsonResponse
    {
        if (!$this->isAdmin($auth)) {
            return JsonResponse::error('Admin privileges required', 403);
        }

        try {
            $targetUserId = (int)$params['userId'];
            $isAdmin = (bool)($body['is_admin'] ?? false);
            $requestingUserId = (int)$auth['user_id'];

            $result = $this->setUserAdminUseCase->execute($targetUserId, $isAdmin, $requestingUserId);

            return JsonResponse::success($result);
        } catch (ValidationException $e) {
            return JsonResponse::error($e->getMessage(), 400, $e->getErrors());
        } catch (\RuntimeException $e) {
            return JsonResponse::notFound($e->getMessage());
        } catch (\Exception $e) {
            return JsonResponse::serverError($e->getMessage());
        }
    }
}
