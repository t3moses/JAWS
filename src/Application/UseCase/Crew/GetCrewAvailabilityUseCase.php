<?php

declare(strict_types=1);

namespace App\Application\UseCase\Crew;

use App\Application\DTO\Response\AvailabilityResponse;
use App\Application\Exception\CrewNotFoundException;
use App\Application\Port\Repository\CrewRepositoryInterface;
use App\Application\Port\Repository\EventRepositoryInterface;
use App\Domain\Enum\AvailabilityStatus;

/**
 * Get Crew Availability Use Case
 *
 * Retrieves crew availability across all events in a simplified boolean format.
 */
class GetCrewAvailabilityUseCase
{
    public function __construct(
        private CrewRepositoryInterface $crewRepository,
        private EventRepositoryInterface $eventRepository,
    ) {
    }

    /**
     * Execute the use case
     *
     * @param int $userId
     * @return AvailabilityResponse
     * @throws CrewNotFoundException
     */
    public function execute(int $userId): AvailabilityResponse
    {
        // Find crew by user ID
        $crew = $this->crewRepository->findByUserId($userId);
        if ($crew === null) {
            throw new CrewNotFoundException("Crew not found for user ID: {$userId}");
        }

        // Get crew availability (event_id => AvailabilityStatus)
        $crewAvailability = $crew->getAllAvailability();

        // Get event_id => event_date mapping (single efficient query)
        $eventDateMap = $this->eventRepository->getEventDateMap();

        // Convert to boolean format with ISO dates
        $availability = [];
        foreach ($crewAvailability as $eventId => $status) {
            if (isset($eventDateMap[$eventId])) {
                $isAvailable = in_array($status, [
                    AvailabilityStatus::AVAILABLE,
                    AvailabilityStatus::GUARANTEED
                ], true);
                $availability[$eventDateMap[$eventId]] = $isAvailable;
            }
        }

        return new AvailabilityResponse($availability);
    }
}
