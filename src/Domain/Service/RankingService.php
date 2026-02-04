<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\Boat;
use App\Domain\Entity\Crew;
use App\Domain\ValueObject\EventId;
use App\Domain\ValueObject\Rank;
use App\Domain\Enum\BoatRankDimension;
use App\Domain\Enum\CrewRankDimension;
use App\Domain\Enum\AvailabilityStatus;
use App\Domain\Collection\Fleet;
use App\Domain\Collection\Squad;

/**
 * Ranking Service
 *
 * Handles rank calculations and updates for boats and crews.
 * Ranks are used by the Selection algorithm to prioritize entities.
 */
class RankingService
{
    /**
     * Calculate initial rank for a crew
     *
     * @param Crew $crew Crew entity
     * @param array<string> $pastEventIds Past event IDs for absence calculation
     * @param EventId|null $nextEventId Next event for commitment calculation (optional)
     * @param Fleet|null $fleet Fleet for flexibility calculation (optional)
     * @return Rank Calculated crew rank (4D: commitment, flexibility, membership, absence)
     */
    public function calculateCrewRank(
        Crew $crew,
        array $pastEventIds,
        ?EventId $nextEventId = null,
        ?Fleet $fleet = null
    ): Rank {
        // Calculate commitment (availability for next event)
        $commitment = 1; // Default: AVAILABLE
        if ($nextEventId !== null) {
            $availability = $crew->getAvailability($nextEventId);
            $commitment = match ($availability) {
                AvailabilityStatus::GUARANTEED => 0,    // Highest priority
                AvailabilityStatus::AVAILABLE => 1,     // Medium priority
                AvailabilityStatus::WITHDRAWN => 2,     // Lower priority
                AvailabilityStatus::UNAVAILABLE => 3,   // Lowest priority
            };
        }

        // Calculate flexibility (whether crew owns a boat)
        $flexibility = 1; // Default: not flexible
        if ($fleet !== null) {
            $crewKey = $crew->getKey();
            foreach ($fleet->all() as $boat) {
                if ($boat->getOwnerKey()->equals($crewKey)) {
                    $flexibility = 0; // Flexible (owns boat)
                    break;
                }
            }
        }

        // Calculate membership (has valid membership number)
        $membership = empty($crew->getMembershipNumber()) ? 0 : 1;

        // Calculate absence (count of past no-shows)
        $absence = 0;
        foreach ($pastEventIds as $eventIdString) {
            $eventId = EventId::fromString($eventIdString);
            if ($crew->getHistory($eventId) === '') {
                $absence++;
            }
        }

        return Rank::forCrew($commitment, $flexibility, $membership, $absence);
    }

    /**
     * Calculate initial rank for a boat
     *
     * @param Boat $boat Boat entity
     * @param array<string> $pastEventIds Past event IDs for absence calculation
     * @param Squad|null $squad Squad for flexibility calculation (optional)
     * @return Rank Calculated boat rank (2D: flexibility, absence)
     */
    public function calculateBoatRank(
        Boat $boat,
        array $pastEventIds,
        ?Squad $squad = null
    ): Rank {
        // Calculate flexibility (whether owner is also crew)
        $flexibility = 1; // Default: not flexible
        if ($squad !== null) {
            $ownerKey = $boat->getOwnerKey();
            foreach ($squad->all() as $crew) {
                if ($crew->getKey()->equals($ownerKey)) {
                    $flexibility = 0; // Flexible (owner is crew)
                    break;
                }
            }
        }

        // Calculate absence (count of past no-shows)
        $absence = 0;
        foreach ($pastEventIds as $eventIdString) {
            $eventId = EventId::fromString($eventIdString);
            if ($boat->getHistory($eventId) === '') {
                $absence++;
            }
        }

        return Rank::forBoat($flexibility, $absence);
    }

    /**
     * Update absence rank for boats based on past events
     *
     * @param array<Boat> $boats
     * @param array<string> $pastEventIds Array of past event ID strings
     */
    public function updateBoatAbsenceRanks(array $boats, array $pastEventIds): void
    {
        foreach ($boats as $boat) {
            $absences = 0;
            foreach ($pastEventIds as $eventIdString) {
                $eventId = EventId::fromString($eventIdString);
                if ($boat->getHistory($eventId) === '') {
                    $absences++;
                }
            }
            $boat->setRankDimension(BoatRankDimension::ABSENCE, $absences);
        }
    }

    /**
     * Update absence rank for crews based on past events
     *
     * @param array<Crew> $crews
     * @param array<string> $pastEventIds Array of past event ID strings
     */
    public function updateCrewAbsenceRanks(array $crews, array $pastEventIds): void
    {
        foreach ($crews as $crew) {
            $absences = 0;
            foreach ($pastEventIds as $eventIdString) {
                $eventId = EventId::fromString($eventIdString);
                if ($crew->getHistory($eventId) === '') {
                    $absences++;
                }
            }
            $crew->setRankDimension(CrewRankDimension::ABSENCE, $absences);
        }
    }

    /**
     * Update commitment rank for crews based on availability for the next event
     *
     * @param array<Crew> $crews
     * @param EventId $nextEventId
     */
    public function updateCrewCommitmentRanks(array $crews, EventId $nextEventId): void
    {
        foreach ($crews as $crew) {
            $availability = $crew->getAvailability($nextEventId);

            // Map availability to commitment rank
            // Lower rank = higher priority
            $commitmentRank = match ($availability) {
                AvailabilityStatus::GUARANTEED => 0,    // Highest priority
                AvailabilityStatus::AVAILABLE => 1,     // Medium priority
                AvailabilityStatus::WITHDRAWN => 2,     // Lower priority
                AvailabilityStatus::UNAVAILABLE => 3,   // Lowest priority
            };

            $crew->setRankDimension(CrewRankDimension::COMMITMENT, $commitmentRank);
        }
    }

    /**
     * Update membership rank for a crew
     *
     * @param Crew $crew
     */
    public function updateCrewMembershipRank(Crew $crew): void
    {
        $membershipRank = empty($crew->getMembershipNumber()) ? 0 : 1;
        $crew->setRankDimension(CrewRankDimension::MEMBERSHIP, $membershipRank);
    }

    /**
     * Update all ranks for boats
     *
     * @param array<Boat> $boats
     * @param array<string> $pastEventIds
     */
    public function updateAllBoatRanks(array $boats, array $pastEventIds): void
    {
        $this->updateBoatAbsenceRanks($boats, $pastEventIds);
        // Flexibility rank is updated by FlexService
    }

    /**
     * Update all ranks for crews
     *
     * @param array<Crew> $crews
     * @param array<string> $pastEventIds
     * @param EventId $nextEventId
     */
    public function updateAllCrewRanks(array $crews, array $pastEventIds, EventId $nextEventId): void
    {
        $this->updateCrewAbsenceRanks($crews, $pastEventIds);
        $this->updateCrewCommitmentRanks($crews, $nextEventId);
        // Flexibility and membership ranks are updated elsewhere
    }
}
