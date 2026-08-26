<?php

declare(strict_types=1);

namespace App\Application\UseCase\Boat;

use App\Application\Exception\BoatNotFoundException;
use App\Application\Port\Repository\BoatRepositoryInterface;
use App\Application\Port\Repository\CrewRepositoryInterface;
use App\Application\Port\Repository\EventRepositoryInterface;
use App\Application\Port\Repository\SeasonRepositoryInterface;
use App\Domain\Enum\CrewRankDimension;
use App\Domain\ValueObject\CrewKey;
use App\Domain\ValueObject\EventId;
use Psr\Log\LoggerInterface;

/**
 * Flag Assigned Crew Use Case
 *
 * Lets a boat owner flag crew members who were assigned to their boat for one
 * or more events. Every flag withdraws the crew from all future events
 * (their crew_availability rows are deleted, the same effect as a manual
 * withdrawal). Each flag also steps the crew's commitment rank down by one:
 * while commitment rank is above 0 it is decremented; once a crew is flagged
 * again while already at commitment rank 0, they are marked inactive
 * (active = false) instead of decrementing further. An inactive crew is
 * blocked from updating their own availability (see
 * UpdateCrewAvailabilityUseCase). Each verified (event, crew) pair is also
 * recorded in the no_shows table (silently ignored if already recorded).
 *
 * SECURITY: Client-submitted (eventId, crewKey) pairs are never trusted at
 * face value. Each pair is independently verified against the actual
 * persisted flotilla for that event before it counts toward the decrement,
 * so a boat owner can only flag crew who were genuinely assigned to their
 * own boat. Flags are also restricted to past events only — the next
 * event's assignment can still change before it happens, so it isn't
 * eligible for flagging yet.
 */
class FlagAssignedCrewUseCase
{
    public function __construct(
        private BoatRepositoryInterface $boatRepository,
        private CrewRepositoryInterface $crewRepository,
        private EventRepositoryInterface $eventRepository,
        private SeasonRepositoryInterface $seasonRepository,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param int $userId Authenticated boat owner's user ID
     * @param array<int, array{eventId: string, crewKey: string}> $flags
     * @return array<int, array{
     *     crew_key: string,
     *     display_name: ?string,
     *     flag_count: int,
     *     rank_commitment: int,
     *     active: bool,
     *     withdrawn_from_future_events: bool
     * }>
     * @throws BoatNotFoundException
     */
    public function execute(int $userId, array $flags): array
    {
        $boat = $this->boatRepository->findByOwnerUserId($userId);
        if ($boat === null) {
            throw new BoatNotFoundException("No boat found for user ID: {$userId}");
        }

        // Only events that have already happened are eligible for flagging.
        $pastEventIds = array_flip($this->eventRepository->findPastEvents());

        // Verify each (eventId, crewKey) pair against the real persisted flotilla,
        // de-duplicating so a repeated pair can't be counted more than once.
        $verifiedCrewKeys = [];
        foreach ($flags as $flag) {
            $pairKey = $flag['eventId'] . '|' . $flag['crewKey'];
            if (isset($verifiedCrewKeys[$pairKey])) {
                continue;
            }
            if (!isset($pastEventIds[$flag['eventId']])) {
                continue;
            }
            if ($this->wasAssignedToBoat($flag['eventId'], $flag['crewKey'], $boat->getKey()->toString())) {
                $verifiedCrewKeys[$pairKey] = $flag['crewKey'];
            }
        }

        $flagCountsByCrewKey = [];
        foreach ($verifiedCrewKeys as $pairKey => $crewKeyString) {
            $flagCountsByCrewKey[$crewKeyString] = ($flagCountsByCrewKey[$crewKeyString] ?? 0) + 1;

            [$eventIdString] = explode('|', $pairKey, 2);
            $this->crewRepository->recordNoShow(CrewKey::fromString($crewKeyString), EventId::fromString($eventIdString));
        }

        // Every flag withdraws the crew from all future events, regardless of
        // their current commitment rank.
        $futureEventIds = $this->eventRepository->findFutureEvents();

        $results = [];
        foreach ($flagCountsByCrewKey as $crewKeyString => $flagCount) {
            $crew = $this->crewRepository->findByKey(CrewKey::fromString($crewKeyString));
            if ($crew === null) {
                continue;
            }

            $withdrawnFromFutureEvents = false;
            if (!empty($futureEventIds)) {
                $this->crewRepository->deleteAvailabilityForEvents($crew->getKey(), $futureEventIds);
                $withdrawnFromFutureEvents = true;
            }

            // Walk the commitment state machine one flag at a time: decrement
            // while above zero; a flag that lands while already at zero
            // deactivates the crew instead of decrementing further.
            $rankBefore = $crew->getRank()->getDimension(CrewRankDimension::COMMITMENT);
            $rankAfter = $rankBefore;
            $becameInactive = false;
            for ($i = 0; $i < $flagCount; $i++) {
                if ($rankAfter > 0) {
                    $rankAfter--;
                } else {
                    $becameInactive = true;
                }
            }

            $crew->setRankDimension(CrewRankDimension::COMMITMENT, $rankAfter);
            $this->crewRepository->updateRankCommitment($crew);

            if ($becameInactive) {
                $crew->setActive(false);
                $this->crewRepository->updateActive($crew);
            }

            $this->logger->info('boat_owner.crew_flagged', [
                'boat_key'                     => $boat->getKey()->toString(),
                'crew_key'                     => $crewKeyString,
                'flag_count'                   => $flagCount,
                'rank_before'                  => $rankBefore,
                'rank_after'                   => $rankAfter,
                'became_inactive'              => $becameInactive,
                'withdrawn_from_future_events' => $withdrawnFromFutureEvents,
            ]);

            $results[] = [
                'crew_key'                     => $crewKeyString,
                'display_name'                 => $crew->getDisplayName(),
                'flag_count'                   => $flagCount,
                'rank_commitment'              => $rankAfter,
                'active'                       => $crew->isActive(),
                'withdrawn_from_future_events' => $withdrawnFromFutureEvents,
            ];
        }

        return $results;
    }

    /**
     * Check the persisted flotilla for $eventId to see whether $crewKeyString was
     * actually assigned to the boat identified by $boatKeyString.
     */
    private function wasAssignedToBoat(string $eventIdString, string $crewKeyString, string $boatKeyString): bool
    {
        if (!$this->eventRepository->exists(EventId::fromString($eventIdString))) {
            return false;
        }

        $flotilla = $this->seasonRepository->getFlotilla(EventId::fromString($eventIdString));
        if ($flotilla === null) {
            return false;
        }

        foreach ($flotilla['crewed_boats'] as $crewedBoat) {
            if ($crewedBoat['boat']['key'] !== $boatKeyString) {
                continue;
            }
            foreach ($crewedBoat['crews'] as $crew) {
                if ($crew['key'] === $crewKeyString) {
                    return true;
                }
            }
        }

        return false;
    }
}
