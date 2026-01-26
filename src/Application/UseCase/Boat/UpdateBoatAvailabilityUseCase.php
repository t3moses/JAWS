<?php

declare(strict_types=1);

namespace App\Application\UseCase\Boat;

use App\Application\DTO\Request\UpdateAvailabilityRequest;
use App\Application\DTO\Response\BoatResponse;
use App\Application\Exception\ValidationException;
use App\Application\Exception\BoatNotFoundException;
use App\Application\Port\Repository\BoatRepositoryInterface;
use App\Domain\ValueObject\EventId;

/**
 * Update Boat Availability Use Case
 *
 * Updates berths offered by a boat for multiple events.
 */
class UpdateBoatAvailabilityUseCase
{
    public function __construct(
        private BoatRepositoryInterface $boatRepository,
    ) {
    }

    /**
     * Execute the use case
     *
     * @param string $ownerFirstName
     * @param string $ownerLastName
     * @param UpdateAvailabilityRequest $request
     * @return BoatResponse
     * @throws ValidationException
     * @throws BoatNotFoundException
     */
    public function execute(string $ownerFirstName, string $ownerLastName, UpdateAvailabilityRequest $request): BoatResponse
    {
        // Validate request
        $errors = $request->validate();
        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        // Find boat by owner name
        $boat = $this->boatRepository->findByOwnerName($ownerFirstName, $ownerLastName);
        if ($boat === null) {
            throw new BoatNotFoundException("Boat not found for owner: {$ownerFirstName} {$ownerLastName}");
        }

        // Update availability for each event
        foreach ($request->availabilities as $eventIdString => $berths) {
            $eventId = EventId::fromString($eventIdString);
            $boat->setBerths($eventId, (int)$berths);
        }

        // Save boat
        $this->boatRepository->save($boat);

        return BoatResponse::fromEntity($boat);
    }
}
