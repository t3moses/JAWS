<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\UseCase\Event\GetAllEventsUseCase;
use App\Application\UseCase\Event\GetEventUseCase;
use App\Application\UseCase\Flotilla\GetAllFlotillasUseCase;
use App\Application\Exception\EventNotFoundException;
use App\Domain\ValueObject\EventId;
use App\Presentation\Response\JsonResponse;

/**
 * Event Controller
 *
 * Handles event-related endpoints (public access).
 */
class EventController
{
    public function __construct(
        private GetAllEventsUseCase $getAllEventsUseCase,
        private GetEventUseCase $getEventUseCase,
        private GetAllFlotillasUseCase $getAllFlotillasUseCase,
    ) {
    }

    /**
     * GET /api/events
     *
     * Returns all events for the season.
     */
    public function getAll(): JsonResponse
    {
        try {
            $events = $this->getAllEventsUseCase->execute();

            return JsonResponse::success([
                'events' => array_map(fn($event) => $event->toArray(), $events),
            ]);
        } catch (\Exception $e) {
            return JsonResponse::serverError($e->getMessage());
        }
    }

    /**
     * GET /api/events/{id}
     *
     * Returns specific event with flotilla assignments.
     *
     * @param array $params Route parameters
     */
    public function getOne(array $params): JsonResponse
    {
        try {
            $eventId = EventId::fromString($params['id']);
            $result = $this->getEventUseCase->execute($eventId);

            $response = [
                'event' => $result['event']->toArray(),
            ];

            if ($result['flotilla'] !== null) {
                $response['flotilla'] = $result['flotilla']->toArray();
            }

            return JsonResponse::success($response);
        } catch (EventNotFoundException $e) {
            return JsonResponse::notFound($e->getMessage());
        } catch (\Exception $e) {
            return JsonResponse::serverError($e->getMessage());
        }
    }

    /**
     * GET /api/flotillas
     *
     * Returns all flotillas for future events.
     */
    public function getAllFlotillas(): JsonResponse
    {
        try {
            $flotillas = $this->getAllFlotillasUseCase->execute();

            return JsonResponse::success([
                'flotillas' => $flotillas,
            ]);
        } catch (\Exception $e) {
            return JsonResponse::serverError($e->getMessage());
        }
    }
}
