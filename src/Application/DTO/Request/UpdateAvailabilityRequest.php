<?php

declare(strict_types=1);

namespace App\Application\DTO\Request;

/**
 * Update Availability Request DTO
 *
 * Data transfer object for updating availability across multiple events.
 */
final readonly class UpdateAvailabilityRequest
{
    /**
     * @param array<string, int> $availabilities Map of event_id => berths (for boats) or status (for crews)
     */
    public function __construct(
        public array $availabilities,
    ) {
    }

    /**
     * Create from array (e.g., HTTP request data)
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            availabilities: $data['availabilities'] ?? [],
        );
    }

    /**
     * Validate the request
     *
     * @return array<string, string> Validation errors (empty if valid)
     */
    public function validate(): array
    {
        $errors = [];

        if (empty($this->availabilities)) {
            $errors['availabilities'] = 'At least one availability entry is required';
        }

        foreach ($this->availabilities as $eventId => $value) {
            if (empty($eventId)) {
                $errors['availabilities'] = 'Event ID cannot be empty';
            }
            if (!is_int($value) && !is_numeric($value)) {
                $errors['availabilities'] = 'Availability value must be numeric';
            }
        }

        return $errors;
    }
}
