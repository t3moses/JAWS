<?php

declare(strict_types=1);

namespace App\Application\Port\Service;

use App\Domain\Entity\User;

/**
 * Email Template Service Interface
 *
 * Provides email template rendering for various notification types.
 * All templates generate HTML emails with consistent styling.
 */
interface EmailTemplateServiceInterface
{
    /**
     * Render crew registration notification email
     *
     * @param User $user User entity
     * @param array $profile Crew profile data containing:
     *   - firstName: string
     *   - lastName: string
     *   - displayName: string|null
     *   - mobile: string|null
     *   - skill: int
     *   - membershipNumber: string|null
     *   - partnerKey: string|null
     *   - socialPreference: mixed
     *   - experience: string|null
     * @return string HTML email body
     */
    public function renderCrewRegistrationNotification(User $user, array $profile): string;

    /**
     * Render boat owner registration notification email
     *
     * @param User $user User entity
     * @param array $profile Boat profile data containing:
     *   - displayName: string|null
     *   - ownerFirstName: string
     *   - ownerLastName: string
     *   - ownerMobile: string|null
     *   - minBerths: int
     *   - maxBerths: int
     *   - assistanceRequired: mixed
     *   - socialPreference: mixed
     * @return string HTML email body
     */
    public function renderBoatOwnerRegistrationNotification(User $user, array $profile): string;

    /**
     * Render assignment notification email
     *
     * @param string $recipientFirstName Recipient's first name
     * @param string $eventId Event identifier
     * @param string $boatName Boat name
     * @param array $crews Array of crew data, each containing:
     *   - first_name: string
     *   - last_name: string
     *   - email: string
     *   - skill: int
     * @return string HTML email body
     */
    public function renderAssignmentNotification(
        string $recipientFirstName,
        string $eventId,
        string $boatName,
        array $crews
    ): string;
}
