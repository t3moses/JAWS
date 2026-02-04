<?php

declare(strict_types=1);

namespace App\Application\UseCase\Crew;

use App\Application\DTO\Request\UpdateAvailabilityRequest;
use App\Application\DTO\Response\CrewResponse;
use App\Application\Exception\ValidationException;
use App\Application\Exception\CrewNotFoundException;
use App\Application\Exception\EventNotFoundException;
use App\Application\Port\Repository\CrewRepositoryInterface;
use App\Application\Port\Repository\EventRepositoryInterface;
use App\Domain\ValueObject\EventId;
use App\Domain\Enum\AvailabilityStatus;

/**
 * Update Crew Availability Use Case
 *
 * Updates crew availability for multiple events.
 */
class UpdateCrewAvailabilityUseCase
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
     * @param UpdateAvailabilityRequest $request
     * @return CrewResponse
     * @throws ValidationException
     * @throws CrewNotFoundException
     * @throws EventNotFoundException
     */
    public function execute(int $userId, UpdateAvailabilityRequest $request): CrewResponse
    {
        // Validate request
        $errors = $request->validate();
        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        // Find crew by user ID
        $crew = $this->crewRepository->findByUserId($userId);
        if ($crew === null) {
            throw new CrewNotFoundException("Crew not found for user ID: {$userId}");
        }

        // Update availability for each event
        foreach ($request->availabilities as $availability) {
            $eventId = EventId::fromString($availability['eventId']);

            // Validate event exists
            if (!$this->eventRepository->exists($eventId)) {
                throw new EventNotFoundException($availability['eventId']);
            }

            // Map boolean to AvailabilityStatus enum
            $status = $availability['isAvailable']
                ? AvailabilityStatus::AVAILABLE
                : AvailabilityStatus::UNAVAILABLE;

            $crew->setAvailability($eventId, $status);
        }

        // Save crew
        $this->crewRepository->save($crew);

        return CrewResponse::fromEntity($crew);
    }
}
