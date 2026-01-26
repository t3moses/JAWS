<?php

declare(strict_types=1);

namespace App\Application\UseCase\Event;

use App\Application\DTO\Response\EventResponse;
use App\Application\Port\Repository\EventRepositoryInterface;

/**
 * Get All Events Use Case
 *
 * Retrieves all events for the season.
 */
class GetAllEventsUseCase
{
    public function __construct(
        private EventRepositoryInterface $eventRepository,
    ) {
    }

    /**
     * Execute the use case
     *
     * @return array<EventResponse>
     */
    public function execute(): array
    {
        $eventIds = $this->eventRepository->findAll();
        $events = [];

        foreach ($eventIds as $eventId) {
            $eventData = $this->eventRepository->findById(\App\Domain\ValueObject\EventId::fromString($eventId));
            if ($eventData !== null) {
                $events[] = EventResponse::fromArray($eventData);
            }
        }

        return $events;
    }
}
