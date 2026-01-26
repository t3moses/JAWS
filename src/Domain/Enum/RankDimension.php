<?php

declare(strict_types=1);

namespace App\Domain\Enum;

/**
 * Rank Dimensions
 *
 * Defines the dimensions used in multi-dimensional ranking for boats and crews.
 * Rankings are compared lexicographically (left to right) during sorting.
 * Lower rank values = higher priority.
 */
enum RankDimension: int
{
    // Boat rank dimensions (2D tensor)
    case BOAT_FLEXIBILITY = 0;  // 0=flexible (owner is crew), 1=inflexible
    case BOAT_ABSENCE = 1;      // Count of past no-shows

    // Crew rank dimensions (4D tensor)
    case CREW_COMMITMENT = 0;   // Availability for next event
    case CREW_FLEXIBILITY = 1;  // 0=flexible (owns boat), 1=inflexible
    case CREW_MEMBERSHIP = 2;   // 0=non-member, 1=member
    case CREW_ABSENCE = 3;      // Count of past no-shows

    /**
     * Get all boat rank dimensions in order
     *
     * @return array<RankDimension>
     */
    public static function boatDimensions(): array
    {
        return [
            self::BOAT_FLEXIBILITY,
            self::BOAT_ABSENCE,
        ];
    }

    /**
     * Get all crew rank dimensions in order
     *
     * @return array<RankDimension>
     */
    public static function crewDimensions(): array
    {
        return [
            self::CREW_COMMITMENT,
            self::CREW_FLEXIBILITY,
            self::CREW_MEMBERSHIP,
            self::CREW_ABSENCE,
        ];
    }
}
