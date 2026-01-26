<?php

declare(strict_types=1);

namespace App\Application\UseCase\Boat;

use App\Application\DTO\Request\RegisterBoatRequest;
use App\Application\DTO\Response\BoatResponse;
use App\Application\Exception\ValidationException;
use App\Application\Port\Repository\BoatRepositoryInterface;
use App\Application\Port\Service\TimeServiceInterface;
use App\Domain\Entity\Boat;
use App\Domain\ValueObject\BoatKey;

/**
 * Register Boat Use Case
 *
 * Handles boat registration (create or update).
 */
class RegisterBoatUseCase
{
    public function __construct(
        private BoatRepositoryInterface $boatRepository,
        private TimeServiceInterface $timeService,
    ) {
    }

    /**
     * Execute the use case
     *
     * @param RegisterBoatRequest $request
     * @return BoatResponse
     * @throws ValidationException
     */
    public function execute(RegisterBoatRequest $request): BoatResponse
    {
        // Validate request
        $errors = $request->validate();
        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        // Generate boat key
        $boatKey = BoatKey::fromBoatName($request->displayName);

        // Check if boat already exists
        $existingBoat = $this->boatRepository->findByKey($boatKey);

        if ($existingBoat !== null) {
            // Update existing boat
            $boat = $this->updateBoat($existingBoat, $request);
        } else {
            // Create new boat
            $boat = $this->createBoat($boatKey, $request);
        }

        // Save boat
        $this->boatRepository->save($boat);

        return BoatResponse::fromEntity($boat);
    }

    /**
     * Create new boat
     */
    private function createBoat(BoatKey $boatKey, RegisterBoatRequest $request): Boat
    {
        return new Boat(
            key: $boatKey,
            displayName: $request->displayName,
            ownerFirstName: $request->ownerFirstName,
            ownerLastName: $request->ownerLastName,
            ownerEmail: $request->ownerEmail,
            ownerMobile: $request->ownerMobile,
            minBerths: $request->minBerths,
            maxBerths: $request->maxBerths,
            assistanceRequired: $request->assistanceRequired,
            socialPreference: $request->socialPreference,
        );
    }

    /**
     * Update existing boat
     */
    private function updateBoat(Boat $boat, RegisterBoatRequest $request): Boat
    {
        $boat->setDisplayName($request->displayName);
        $boat->setOwnerFirstName($request->ownerFirstName);
        $boat->setOwnerLastName($request->ownerLastName);
        $boat->setOwnerEmail($request->ownerEmail);
        $boat->setOwnerMobile($request->ownerMobile);
        $boat->setMinBerths($request->minBerths);
        $boat->setMaxBerths($request->maxBerths);
        $boat->setAssistanceRequired($request->assistanceRequired);
        $boat->setSocialPreference($request->socialPreference);

        return $boat;
    }
}
