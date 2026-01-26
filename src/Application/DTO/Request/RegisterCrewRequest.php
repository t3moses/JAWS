<?php

declare(strict_types=1);

namespace App\Application\DTO\Request;

/**
 * Register Crew Request DTO
 *
 * Data transfer object for crew registration.
 */
final readonly class RegisterCrewRequest
{
    public function __construct(
        public string $displayName,
        public string $firstName,
        public string $lastName,
        public ?string $partnerFirstName,
        public ?string $partnerLastName,
        public string $email,
        public string $mobile,
        public bool $socialPreference,
        public string $membershipNumber,
        public int $skill,
        public string $experience,
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
            firstName: $data['first_name'] ?? '',
            lastName: $data['last_name'] ?? '',
            partnerFirstName: $data['partner_first_name'] ?? null,
            partnerLastName: $data['partner_last_name'] ?? null,
            email: $data['email'] ?? '',
            mobile: $data['mobile'] ?? '',
            socialPreference: (bool)($data['social_preference'] ?? false),
            membershipNumber: $data['membership_number'] ?? '',
            skill: (int)($data['skill'] ?? 0),
            experience: $data['experience'] ?? '',
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
            $errors['display_name'] = 'Display name is required';
        }

        if (empty($this->firstName)) {
            $errors['first_name'] = 'First name is required';
        }

        if (empty($this->lastName)) {
            $errors['last_name'] = 'Last name is required';
        }

        if (empty($this->email)) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }

        if ($this->skill < 0 || $this->skill > 2) {
            $errors['skill'] = 'Skill level must be 0 (novice), 1 (intermediate), or 2 (advanced)';
        }

        return $errors;
    }
}
