<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\Boat;
use App\Domain\Enum\BoatRankDimension;

/**
 * Flex Service
 *
 * Handles "flex" logic - when boat owners are willing to crew.
 * Flex status is set once at registration (willingToCrew=true sets rank_flexibility=0).
 * Flex boats appear in the crew waitlist when their boat is cut from selection.
 */
class FlexService
{
    /**
     * Check if a boat owner has flex status (willing to crew)
     *
     * Flex status is stored in rank_flexibility: 0 = flex, 1 = not flex.
     * This is set at registration and never changed dynamically.
     *
     * @param Boat $boat
     * @return bool True if boat owner is willing to crew (rank_flexibility === 0)
     */
    public function isBoatOwnerFlex(Boat $boat): bool
    {
        return $boat->getRank()->getDimension(BoatRankDimension::FLEXIBILITY) === 0;
    }
}
