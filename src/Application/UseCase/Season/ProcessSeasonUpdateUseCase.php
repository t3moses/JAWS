<?php

declare(strict_types=1);

namespace App\Application\UseCase\Season;

use App\Application\Port\Repository\BoatRepositoryInterface;
use App\Application\Port\Repository\CrewRepositoryInterface;
use App\Application\Port\Repository\EventRepositoryInterface;
use App\Application\Port\Repository\SeasonRepositoryInterface;
use App\Domain\Collection\Fleet;
use App\Domain\Collection\Squad;
use App\Domain\Entity\Boat;
use App\Domain\Entity\Crew;
use App\Domain\Service\SelectionService;
use App\Domain\Service\AssignmentService;
use App\Domain\ValueObject\EventId;
use App\Domain\Enum\AvailabilityStatus;

/**
 * Process Season Update Use Case
 *
 * CRITICAL: This orchestrates the Selection → Assignment → Persistence pipeline
 * that was previously handled by season_update.php.
 *
 * Pipeline:
 * 1. Load Fleet, Squad, Season configuration
 * 2. For each future event:
 *    - Selection phase (rank and capacity match)
 *    - Event consolidation (form flotilla structure)
 *    - Assignment optimization (next event only - greedy swap optimization)
 *    - Update availability statuses
 *    - Update history
 *    - Save flotilla
 * 3. Persist all changes
 */
class ProcessSeasonUpdateUseCase
{
    public function __construct(
        private BoatRepositoryInterface $boatRepository,
        private CrewRepositoryInterface $crewRepository,
        private EventRepositoryInterface $eventRepository,
        private SeasonRepositoryInterface $seasonRepository,
        private SelectionService $selectionService,
        private AssignmentService $assignmentService,
    ) {
    }

    /**
     * Execute the season update pipeline
     *
     * @return array{success: bool, events_processed: int, flotillas_generated: int}
     */
    public function execute(): array
    {
        // Load all entities
        $fleet = $this->loadFleet();
        $squad = $this->loadSquad();
        $futureEvents = $this->eventRepository->findFutureEvents();
        $nextEventId = $this->eventRepository->findNextEvent();

        $eventsProcessed = 0;
        $flotillasGenerated = 0;

        // Process each future event
        foreach ($futureEvents as $eventIdString) {
            $eventId = EventId::fromString($eventIdString);

            // Phase 1: Selection (rank and capacity match)
            $selectionResult = $this->runSelection($fleet, $squad, $eventId);

            // Phase 2: Event consolidation (form flotilla structure)
            $flotilla = $this->consolidateEvent(
                $eventId,
                $selectionResult['selected_boats'],
                $selectionResult['selected_crews'],
                $selectionResult['waitlisted_boats'],
                $selectionResult['waitlisted_crews']
            );

            // Phase 3: Assignment optimization (next event only)
            if ($eventIdString === $nextEventId) {
                $flotilla = $this->runAssignment($flotilla);
            }

            // Phase 4: Update availability statuses
            $this->updateAvailabilityStatuses(
                $selectionResult['selected_boats'],
                $selectionResult['selected_crews'],
                $eventId
            );

            // Phase 5: Update history (for past events - not applicable here for future events)
            // History is updated after events occur via separate process

            // Phase 6: Save flotilla
            $this->seasonRepository->saveFlotilla($eventId, $flotilla);
            $flotillasGenerated++;

            $eventsProcessed++;
        }

        // Persist all changes
        $this->persistChanges($fleet, $squad);

        return [
            'success' => true,
            'events_processed' => $eventsProcessed,
            'flotillas_generated' => $flotillasGenerated,
        ];
    }

    /**
     * Load all boats from repository into Fleet collection
     */
    private function loadFleet(): Fleet
    {
        $boats = $this->boatRepository->findAll();
        $fleet = new Fleet();

        foreach ($boats as $boat) {
            $fleet->add($boat);
        }

        return $fleet;
    }

    /**
     * Load all crews from repository into Squad collection
     */
    private function loadSquad(): Squad
    {
        $crews = $this->crewRepository->findAll();
        $squad = new Squad();

        foreach ($crews as $crew) {
            $squad->add($crew);
        }

        return $squad;
    }

    /**
     * Run Selection phase: rank boats and crews, match capacity
     *
     * @param Fleet $fleet
     * @param Squad $squad
     * @param EventId $eventId
     * @return array{selected_boats: array<Boat>, selected_crews: array<Crew>, waitlisted_boats: array<Boat>, waitlisted_crews: array<Crew>}
     */
    private function runSelection(Fleet $fleet, Squad $squad, EventId $eventId): array
    {
        // Get available boats and crews for this event
        $availableBoats = $fleet->getAvailableFor($eventId);
        $availableCrews = $squad->getAvailableFor($eventId);

        // Run selection algorithm (deterministic shuffle, bubble sort, capacity matching)
        return $this->selectionService->select(
            $availableBoats,
            $availableCrews,
            $eventId->toString()
        );
    }

    /**
     * Consolidate event into flotilla structure
     *
     * @param EventId $eventId
     * @param array<Boat> $selectedBoats
     * @param array<Crew> $selectedCrews
     * @param array<Boat> $waitlistedBoats
     * @param array<Crew> $waitlistedCrews
     * @return array{event_id: string, crewed_boats: array, waitlist_boats: array, waitlist_crews: array}
     */
    private function consolidateEvent(
        EventId $eventId,
        array $selectedBoats,
        array $selectedCrews,
        array $waitlistedBoats,
        array $waitlistedCrews
    ): array {
        // Build crewed boats array (from Selection output)
        $crewedBoats = [];
        foreach ($selectedBoats as $boat) {
            $crewedBoats[] = [
                'boat' => $boat,
                'crews' => [], // Will be populated by assignment or left empty for initial selection
            ];
        }

        // Distribute selected crews to boats (initial even distribution before optimization)
        $crewIndex = 0;
        foreach ($selectedCrews as $crew) {
            if (count($crewedBoats) > 0) {
                $boatIndex = $crewIndex % count($crewedBoats);
                $crewedBoats[$boatIndex]['crews'][] = $crew;
                $crewIndex++;
            }
        }

        return [
            'event_id' => $eventId->toString(),
            'crewed_boats' => $crewedBoats,
            'waitlist_boats' => $waitlistedBoats,
            'waitlist_crews' => $waitlistedCrews,
        ];
    }

    /**
     * Run Assignment optimization: greedy swap algorithm to minimize rule violations
     *
     * CRITICAL: Only run on next event, not all future events
     *
     * @param array{event_id: string, crewed_boats: array, waitlist_boats: array, waitlist_crews: array} $flotilla
     * @return array{event_id: string, crewed_boats: array, waitlist_boats: array, waitlist_crews: array}
     */
    private function runAssignment(array $flotilla): array
    {
        // Extract crewed boats array
        $crewedBoats = $flotilla['crewed_boats'];

        // Run assignment optimization (6 rules: ASSIST, WHITELIST, HIGH_SKILL, LOW_SKILL, PARTNER, REPEAT)
        $optimizedCrewedBoats = $this->assignmentService->assign($crewedBoats);

        // Return updated flotilla
        return [
            'event_id' => $flotilla['event_id'],
            'crewed_boats' => $optimizedCrewedBoats,
            'waitlist_boats' => $flotilla['waitlist_boats'],
            'waitlist_crews' => $flotilla['waitlist_crews'],
        ];
    }

    /**
     * Update availability statuses for selected boats and crews
     *
     * Selected entities get status GUARANTEED (2)
     *
     * @param array<Boat> $selectedBoats
     * @param array<Crew> $selectedCrews
     * @param EventId $eventId
     */
    private function updateAvailabilityStatuses(
        array $selectedBoats,
        array $selectedCrews,
        EventId $eventId
    ): void {
        // Update boat statuses (boats don't have availability status in same way, but we track selection via berths)
        // No-op for boats as berths already indicate selection

        // Update crew statuses to GUARANTEED
        foreach ($selectedCrews as $crew) {
            $crew->setAvailability($eventId, AvailabilityStatus::GUARANTEED);
        }
    }

    /**
     * Persist all changes to database
     *
     * @param Fleet $fleet
     * @param Squad $squad
     */
    private function persistChanges(Fleet $fleet, Squad $squad): void
    {
        // Save all boats
        foreach ($fleet->all() as $boat) {
            $this->boatRepository->save($boat);
        }

        // Save all crews
        foreach ($squad->all() as $crew) {
            $this->crewRepository->save($crew);
        }
    }
}
