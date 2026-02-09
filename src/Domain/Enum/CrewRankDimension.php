<?php

declare(strict_types=1);

namespace App\Domain\Enum;

/**
 * Crew Rank Dimensions
 *
 * Defines the dimensions used in multi-dimensional ranking for crews.
 * Rankings are compared lexicographically (left to right) during sorting.
 * Lower rank values = higher priority.
 */
enum CrewRankDimension: int
{
    case COMMITMENT = 0;   // Availability for next event
    case FLEXIBILITY = 1;  // 0=flexible (owns boat), 1=inflexible
    case MEMBERSHIP = 2;   // 0=non-member, 1=member
    case ABSENCE = 3;      // Count of past no-shows

    /**
     * Get all crew rank dimensions in order
     *
     * @return array<CrewRankDimension>
     */
    public static function all(): array
    {
        return [
            self::COMMITMENT,
            self::FLEXIBILITY,
            self::MEMBERSHIP,
            self::ABSENCE,
        ];
    }
}
