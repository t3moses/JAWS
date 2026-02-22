<?php

declare(strict_types=1);

namespace App\Domain\Enum;

/**
 * Crew Rank Dimensions
 *
 * Defines the dimensions used in multi-dimensional ranking for crews.
 * Rankings are compared lexicographically (left to right) during sorting.
 * Higher rank values = higher priority.
 */
enum CrewRankDimension: int
{
    case COMMITMENT = 0;   // Availability for next event
    case MEMBERSHIP = 1;   // 0=non-member, 1=member
    case ABSENCE = 2;      // Count of past no-shows

    /**
     * Get all crew rank dimensions in order (3D)
     *
     * @return array<CrewRankDimension>
     */
    public static function all(): array
    {
        return [
            self::COMMITMENT,
            self::MEMBERSHIP,
            self::ABSENCE,
        ];
    }
}
