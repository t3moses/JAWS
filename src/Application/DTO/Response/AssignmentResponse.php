<?php

declare(strict_types=1);

namespace App\Application\DTO\Response;

/**
 * Assignment Response DTO
 *
 * Represents a crew member's assignment (or lack thereof) for a specific event.
 */
class AssignmentResponse
{
    public function __construct(
        public readonly string $eventId,
        public readonly string $eventDate,
        public readonly string $startTime,
        public readonly string $finishTime,
        public readonly int $availabilityStatus,
        public readonly ?string $boatName,
        public readonly ?string $boatKey,
        public readonly array $crewmates,
    ) {
    }

    /**
     * Convert to array for JSON serialization
     */
    public function toArray(): array
    {
        return [
            'event_id' => $this->eventId,
            'event_date' => $this->eventDate,
            'start_time' => $this->startTime,
            'finish_time' => $this->finishTime,
            'availability_status' => $this->availabilityStatus,
            'boat_name' => $this->boatName,
            'boat_key' => $this->boatKey,
            'crewmates' => $this->crewmates,
        ];
    }
}
