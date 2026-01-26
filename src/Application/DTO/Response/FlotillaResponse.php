<?php

declare(strict_types=1);

namespace App\Application\DTO\Response;

/**
 * Flotilla Response DTO
 *
 * Data transfer object for flotilla assignment information.
 */
final readonly class FlotillaResponse
{
    /**
     * @param string $eventId
     * @param array<array<string, mixed>> $crewedBoats
     * @param array<array<string, mixed>> $waitlistBoats
     * @param array<array<string, mixed>> $waitlistCrews
     */
    public function __construct(
        public string $eventId,
        public array $crewedBoats,
        public array $waitlistBoats,
        public array $waitlistCrews,
    ) {
    }

    /**
     * Create from flotilla data
     *
     * @param string $eventId
     * @param array<string, mixed> $flotillaData
     */
    public static function fromFlotilla(string $eventId, array $flotillaData): self
    {
        return new self(
            eventId: $eventId,
            crewedBoats: $flotillaData['crewed_boats'] ?? [],
            waitlistBoats: $flotillaData['waitlist_boats'] ?? [],
            waitlistCrews: $flotillaData['waitlist_crews'] ?? [],
        );
    }

    /**
     * Convert to array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'event_id' => $this->eventId,
            'crewed_boats' => $this->crewedBoats,
            'waitlist_boats' => $this->waitlistBoats,
            'waitlist_crews' => $this->waitlistCrews,
        ];
    }

    /**
     * Get count of crewed boats
     */
    public function getCrewedBoatCount(): int
    {
        return count($this->crewedBoats);
    }

    /**
     * Get total assigned crew count
     */
    public function getAssignedCrewCount(): int
    {
        $count = 0;
        foreach ($this->crewedBoats as $crewedBoat) {
            $count += count($crewedBoat['crews'] ?? []);
        }
        return $count;
    }
}
