<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\Boat;
use App\Domain\Entity\Crew;
use App\Domain\Collection\Fleet;
use App\Domain\Collection\Squad;
use App\Domain\Enum\RankDimension;

/**
 * Flex Service
 *
 * Handles "flex" logic - when boat owners are also crew or crew members own boats.
 * This affects ranking and capacity calculations.
 *
 * Flex Concept:
 * - A boat owner who is also registered as crew is "flexible"
 * - A crew member who owns a boat is "flexible"
 * - Flexible status improves ranking (lower rank value = higher priority)
 */
class FlexService
{
    /**
     * Check if a boat owner is also registered as crew
     *
     * @param Boat $boat
     * @param Squad $squad
     * @return bool True if boat owner is also crew
     */
    public function isBoatOwnerFlex(Boat $boat, Squad $squad): bool
    {
        $ownerKey = $boat->getOwnerKey();

        foreach ($squad->all() as $crew) {
            if ($crew->getKey()->equals($ownerKey)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a crew member owns a boat
     *
     * @param Crew $crew
     * @param Fleet $fleet
     * @return bool True if crew owns a boat
     */
    public function isCrewFlex(Crew $crew, Fleet $fleet): bool
    {
        $crewKey = $crew->getKey();

        foreach ($fleet->all() as $boat) {
            if ($boat->getOwnerKey()->equals($crewKey)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Update flexibility rank for a boat
     *
     * @param Boat $boat
     * @param Squad $squad
     */
    public function updateBoatFlexRank(Boat $boat, Squad $squad): void
    {
        $isFlex = $this->isBoatOwnerFlex($boat, $squad);
        $flexRank = $isFlex ? 0 : 1; // 0 = flexible (higher priority), 1 = inflexible
        $boat->setRankDimension(RankDimension::BOAT_FLEXIBILITY, $flexRank);
    }

    /**
     * Update flexibility rank for a crew
     *
     * @param Crew $crew
     * @param Fleet $fleet
     */
    public function updateCrewFlexRank(Crew $crew, Fleet $fleet): void
    {
        $isFlex = $this->isCrewFlex($crew, $fleet);
        $flexRank = $isFlex ? 0 : 1; // 0 = flexible (higher priority), 1 = inflexible
        $crew->setRankDimension(RankDimension::CREW_FLEXIBILITY, $flexRank);
    }

    /**
     * Update flexibility ranks for all boats in a fleet
     *
     * @param Fleet $fleet
     * @param Squad $squad
     */
    public function updateAllBoatFlexRanks(Fleet $fleet, Squad $squad): void
    {
        foreach ($fleet->all() as $boat) {
            $this->updateBoatFlexRank($boat, $squad);
        }
    }

    /**
     * Update flexibility ranks for all crews in a squad
     *
     * @param Squad $squad
     * @param Fleet $fleet
     */
    public function updateAllCrewFlexRanks(Squad $squad, Fleet $fleet): void
    {
        foreach ($squad->all() as $crew) {
            $this->updateCrewFlexRank($crew, $fleet);
        }
    }

    /**
     * Update all flex ranks (boats and crews)
     *
     * @param Fleet $fleet
     * @param Squad $squad
     */
    public function updateAllFlexRanks(Fleet $fleet, Squad $squad): void
    {
        $this->updateAllBoatFlexRanks($fleet, $squad);
        $this->updateAllCrewFlexRanks($squad, $fleet);
    }
}
