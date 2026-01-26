<?php

declare(strict_types=1);

namespace App\Application\DTO\Request;

/**
 * Register Boat Request DTO
 *
 * Data transfer object for boat registration.
 */
final readonly class RegisterBoatRequest
{
    public function __construct(
        public string $displayName,
        public string $ownerFirstName,
        public string $ownerLastName,
        public string $ownerEmail,
        public string $ownerMobile,
        public int $minBerths,
        public int $maxBerths,
        public bool $assistanceRequired,
        public bool $socialPreference,
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
            displayName: $data['display_name'] ?? '',
            ownerFirstName: $data['owner_first_name'] ?? '',
            ownerLastName: $data['owner_last_name'] ?? '',
            ownerEmail: $data['owner_email'] ?? '',
            ownerMobile: $data['owner_mobile'] ?? '',
            minBerths: (int)($data['min_berths'] ?? 1),
            maxBerths: (int)($data['max_berths'] ?? 1),
            assistanceRequired: (bool)($data['assistance_required'] ?? false),
            socialPreference: (bool)($data['social_preference'] ?? false),
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

        if (empty($this->displayName)) {
            $errors['display_name'] = 'Boat name is required';
        }

        if (empty($this->ownerFirstName)) {
            $errors['owner_first_name'] = 'Owner first name is required';
        }

        if (empty($this->ownerLastName)) {
            $errors['owner_last_name'] = 'Owner last name is required';
        }

        if (empty($this->ownerEmail)) {
            $errors['owner_email'] = 'Owner email is required';
        } elseif (!filter_var($this->ownerEmail, FILTER_VALIDATE_EMAIL)) {
            $errors['owner_email'] = 'Invalid email format';
        }

        if ($this->minBerths < 1) {
            $errors['min_berths'] = 'Minimum berths must be at least 1';
        }

        if ($this->maxBerths < $this->minBerths) {
            $errors['max_berths'] = 'Maximum berths cannot be less than minimum berths';
        }

        return $errors;
    }
}
