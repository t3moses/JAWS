<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\UseCase\Admin\GetMatchingDataUseCase;
use App\Application\UseCase\Admin\SendNotificationsUseCase;
use App\Application\UseCase\Season\UpdateConfigUseCase;
use App\Application\DTO\Request\UpdateConfigRequest;
use App\Application\Exception\EventNotFoundException;
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
        private UpdateConfigUseCase $updateConfigUseCase,
    ) {
    }

    /**
     * GET /api/admin/matching/{eventId}
     *
     * Returns matching data for an event (capacity analysis).
     *
     * @param array $params Route parameters
     */
    public function getMatchingData(array $params): JsonResponse
    {
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
     */
    public function sendNotifications(array $params, array $body): JsonResponse
    {
        try {
            $eventId = EventId::fromString($params['eventId']);
            $includeCalendar = $body['include_calendar'] ?? true;

            $result = $this->sendNotificationsUseCase->execute($eventId, $includeCalendar);

            if (!$result['success']) {
                return JsonResponse::error($result['message'], 400);
            }

            return JsonResponse::success($result);
        } catch (EventNotFoundException $e) {
            return JsonResponse::notFound($e->getMessage());
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
     */
    public function updateConfig(array $body): JsonResponse
    {
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
}
