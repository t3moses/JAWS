<?php

declare(strict_types=1);

namespace App\Application\UseCase\Crew;

use App\Application\DTO\Request\RegisterCrewRequest;
use App\Application\DTO\Response\CrewResponse;
use App\Application\Exception\ValidationException;
use App\Application\Port\Repository\CrewRepositoryInterface;
use App\Application\Port\Service\TimeServiceInterface;
use App\Domain\Entity\Crew;
use App\Domain\ValueObject\CrewKey;
use App\Domain\Enum\SkillLevel;

/**
 * Register Crew Use Case
 *
 * Handles crew registration (create or update).
 */
class RegisterCrewUseCase
{
    public function __construct(
        private CrewRepositoryInterface $crewRepository,
        private TimeServiceInterface $timeService,
    ) {
    }

    /**
     * Execute the use case
     *
     * @param RegisterCrewRequest $request
     * @return CrewResponse
     * @throws ValidationException
     */
    public function execute(RegisterCrewRequest $request): CrewResponse
    {
        // Validate request
        $errors = $request->validate();
        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        // Generate crew key
        $crewKey = CrewKey::fromName($request->firstName, $request->lastName);

        // Check if crew already exists
        $existingCrew = $this->crewRepository->findByKey($crewKey);

        if ($existingCrew !== null) {
            // Update existing crew
            $crew = $this->updateCrew($existingCrew, $request);
        } else {
            // Create new crew
            $crew = $this->createCrew($crewKey, $request);
        }

        // Save crew
        $this->crewRepository->save($crew);

        return CrewResponse::fromEntity($crew);
    }

    /**
     * Create new crew
     */
    private function createCrew(CrewKey $crewKey, RegisterCrewRequest $request): Crew
    {
        $partnerKey = null;
        if ($request->partnerFirstName && $request->partnerLastName) {
            $partnerKey = CrewKey::fromName($request->partnerFirstName, $request->partnerLastName);
        }

        return new Crew(
            key: $crewKey,
            displayName: $request->displayName,
            firstName: $request->firstName,
            lastName: $request->lastName,
            partnerKey: $partnerKey,
            email: $request->email,
            mobile: $request->mobile,
            socialPreference: $request->socialPreference,
            membershipNumber: $request->membershipNumber,
            skill: SkillLevel::fromInt($request->skill),
            experience: $request->experience,
        );
    }

    /**
     * Update existing crew
     */
    private function updateCrew(Crew $crew, RegisterCrewRequest $request): Crew
    {
        $partnerKey = null;
        if ($request->partnerFirstName && $request->partnerLastName) {
            $partnerKey = CrewKey::fromName($request->partnerFirstName, $request->partnerLastName);
        }

        $crew->setDisplayName($request->displayName);
        $crew->setFirstName($request->firstName);
        $crew->setLastName($request->lastName);
        $crew->setPartnerKey($partnerKey);
        $crew->setEmail($request->email);
        $crew->setMobile($request->mobile);
        $crew->setSocialPreference($request->socialPreference);
        $crew->setMembershipNumber($request->membershipNumber);
        $crew->setSkill(SkillLevel::fromInt($request->skill));
        $crew->setExperience($request->experience);

        return $crew;
    }
}
