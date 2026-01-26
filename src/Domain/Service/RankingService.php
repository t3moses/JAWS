<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\Boat;
use App\Domain\Entity\Crew;
use App\Domain\ValueObject\EventId;
use App\Domain\Enum\RankDimension;
use App\Domain\Enum\AvailabilityStatus;

/**
 * Ranking Service
 *
 * Handles rank calculations and updates for boats and crews.
 * Ranks are used by the Selection algorithm to prioritize entities.
 */
class RankingService
{
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
            $boat->setRankDimension(RankDimension::BOAT_ABSENCE, $absences);
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
            $crew->setRankDimension(RankDimension::CREW_ABSENCE, $absences);
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

            $crew->setRankDimension(RankDimension::CREW_COMMITMENT, $commitmentRank);
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
        $crew->setRankDimension(RankDimension::CREW_MEMBERSHIP, $membershipRank);
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
