<?php

declare(strict_types=1);

namespace App\Application\UseCase\Crew;

use App\Application\DTO\Response\AssignmentResponse;
use App\Application\Exception\CrewNotFoundException;
use App\Application\Port\Repository\CrewRepositoryInterface;
use App\Application\Port\Repository\EventRepositoryInterface;
use App\Application\Port\Repository\SeasonRepositoryInterface;
use App\Domain\ValueObject\CrewKey;

/**
 * Get User Assignments Use Case
 *
 * Retrieves all assignments for a crew member across all events.
 * This allows users to see which boats they're assigned to for each event.
 */
class GetUserAssignmentsUseCase
{
    public function __construct(
        private CrewRepositoryInterface $crewRepository,
        private EventRepositoryInterface $eventRepository,
        private SeasonRepositoryInterface $seasonRepository,
    ) {
    }

    /**
     * Execute the use case
     *
     * @param CrewKey $crewKey
     * @return array<AssignmentResponse>
     * @throws CrewNotFoundException
     */
    public function execute(CrewKey $crewKey): array
    {
        // Find crew
        $crew = $this->crewRepository->findByKey($crewKey);
        if ($crew === null) {
            throw new CrewNotFoundException($crewKey);
        }

        // Get all events
        $allEvents = $this->eventRepository->findAll();
        $assignments = [];

        // For each event, check if crew is assigned
        foreach ($allEvents as $eventIdString) {
            $eventId = \App\Domain\ValueObject\EventId::fromString($eventIdString);
            $eventData = $this->eventRepository->findById($eventId);

            if ($eventData === null) {
                continue;
            }

            // Get flotilla for this event
            $flotilla = $this->seasonRepository->getFlotilla($eventId);

            if ($flotilla === null) {
                // No flotilla yet, show availability status only
                $availability = $crew->getAvailability($eventId);
                $assignments[] = new AssignmentResponse(
                    eventId: $eventIdString,
                    eventDate: $eventData['event_date'],
                    startTime: $eventData['start_time'],
                    finishTime: $eventData['finish_time'],
                    availabilityStatus: $availability->value,
                    boatName: null,
                    boatKey: null,
                    crewmates: [],
                );
                continue;
            }

            // Search for crew in crewed boats
            $found = false;
            foreach ($flotilla['crewed_boats'] as $crewedBoat) {
                $boat = $crewedBoat['boat'];
                $crews = $crewedBoat['crews'];

                foreach ($crews as $assignedCrew) {
                    if ($assignedCrew->getKey()->equals($crewKey)) {
                        // Found assignment
                        $crewmates = array_map(
                            fn($c) => [
                                'key' => $c->getKey()->toString(),
                                'display_name' => $c->getDisplayName(),
                                'skill' => $c->getSkill()->value,
                            ],
                            array_filter($crews, fn($c) => !$c->getKey()->equals($crewKey))
                        );

                        $assignments[] = new AssignmentResponse(
                            eventId: $eventIdString,
                            eventDate: $eventData['event_date'],
                            startTime: $eventData['start_time'],
                            finishTime: $eventData['finish_time'],
                            availabilityStatus: $crew->getAvailability($eventId)->value,
                            boatName: $boat->getDisplayName(),
                            boatKey: $boat->getKey()->toString(),
                            crewmates: array_values($crewmates),
                        );
                        $found = true;
                        break 2;
                    }
                }
            }

            if (!$found) {
                // Not assigned to a boat (either waitlisted or unavailable)
                $availability = $crew->getAvailability($eventId);
                $assignments[] = new AssignmentResponse(
                    eventId: $eventIdString,
                    eventDate: $eventData['event_date'],
                    startTime: $eventData['start_time'],
                    finishTime: $eventData['finish_time'],
                    availabilityStatus: $availability->value,
                    boatName: null,
                    boatKey: null,
                    crewmates: [],
                );
            }
        }

        return $assignments;
    }
}
