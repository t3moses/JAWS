<?php

declare(strict_types=1);

namespace App\Application\UseCase\Boat;

use App\Application\DTO\Request\UpdateAvailabilityRequest;
use App\Application\DTO\Response\BoatResponse;
use App\Application\Exception\ValidationException;
use App\Application\Exception\BoatNotFoundException;
use App\Application\Port\Repository\BoatRepositoryInterface;
use App\Domain\ValueObject\BoatKey;
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
     * @param BoatKey $boatKey
     * @param UpdateAvailabilityRequest $request
     * @return BoatResponse
     * @throws ValidationException
     * @throws BoatNotFoundException
     */
    public function execute(BoatKey $boatKey, UpdateAvailabilityRequest $request): BoatResponse
    {
        // Validate request
        $errors = $request->validate();
        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        // Find boat
        $boat = $this->boatRepository->findByKey($boatKey);
        if ($boat === null) {
            throw new BoatNotFoundException($boatKey);
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
