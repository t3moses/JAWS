<?php

declare(strict_types=1);

namespace App\Application\UseCase\Crew;

use App\Application\DTO\Request\UpdateAvailabilityRequest;
use App\Application\DTO\Response\CrewResponse;
use App\Application\Exception\ValidationException;
use App\Application\Exception\CrewNotFoundException;
use App\Application\Port\Repository\CrewRepositoryInterface;
use App\Domain\ValueObject\CrewKey;
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
    ) {
    }

    /**
     * Execute the use case
     *
     * @param CrewKey $crewKey
     * @param UpdateAvailabilityRequest $request
     * @return CrewResponse
     * @throws ValidationException
     * @throws CrewNotFoundException
     */
    public function execute(CrewKey $crewKey, UpdateAvailabilityRequest $request): CrewResponse
    {
        // Validate request
        $errors = $request->validate();
        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        // Find crew
        $crew = $this->crewRepository->findByKey($crewKey);
        if ($crew === null) {
            throw new CrewNotFoundException($crewKey);
        }

        // Update availability for each event
        foreach ($request->availabilities as $eventIdString => $statusValue) {
            $eventId = EventId::fromString($eventIdString);
            $status = AvailabilityStatus::from((int)$statusValue);
            $crew->setAvailability($eventId, $status);
        }

        // Save crew
        $this->crewRepository->save($crew);

        return CrewResponse::fromEntity($crew);
    }
}
