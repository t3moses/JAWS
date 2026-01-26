<?php

declare(strict_types=1);

namespace App\Application\DTO\Request;

/**
 * Update Config Request DTO
 *
 * Request to update season configuration.
 */
class UpdateConfigRequest
{
    public function __construct(
        public readonly ?string $source = null,
        public readonly ?string $simulatedDate = null,
        public readonly ?int $year = null,
        public readonly ?string $startTime = null,
        public readonly ?string $finishTime = null,
        public readonly ?string $blackoutFrom = null,
        public readonly ?string $blackoutTo = null,
    ) {
    }

    /**
     * Validate the request
     *
     * @return array<string, string> Validation errors (field => error message)
     */
    public function validate(): array
    {
        $errors = [];

        // Validate source
        if ($this->source !== null && !in_array($this->source, ['simulated', 'production'])) {
            $errors['source'] = 'Source must be either "simulated" or "production"';
        }

        // Validate simulated_date format (YYYY-MM-DD)
        if ($this->simulatedDate !== null) {
            $date = \DateTime::createFromFormat('Y-m-d', $this->simulatedDate);
            if (!$date || $date->format('Y-m-d') !== $this->simulatedDate) {
                $errors['simulated_date'] = 'Simulated date must be in format YYYY-MM-DD';
            }
        }

        // Validate year
        if ($this->year !== null && ($this->year < 2020 || $this->year > 2100)) {
            $errors['year'] = 'Year must be between 2020 and 2100';
        }

        // Validate time formats (HH:MM:SS)
        $timeFields = [
            'start_time' => $this->startTime,
            'finish_time' => $this->finishTime,
            'blackout_from' => $this->blackoutFrom,
            'blackout_to' => $this->blackoutTo,
        ];

        foreach ($timeFields as $field => $value) {
            if ($value !== null && !preg_match('/^([01]\d|2[0-3]):([0-5]\d):([0-5]\d)$/', $value)) {
                $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' must be in format HH:MM:SS';
            }
        }

        return $errors;
    }
}
