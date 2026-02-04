<?php

declare(strict_types=1);

namespace App\Application\UseCase\Boat;

use App\Application\DTO\Request\UpdateAvailabilityRequest;
use App\Application\DTO\Response\BoatResponse;
use App\Application\Exception\ValidationException;
use App\Application\Exception\BoatNotFoundException;
use App\Application\Exception\EventNotFoundException;
use App\Application\Port\Repository\BoatRepositoryInterface;
use App\Application\Port\Repository\EventRepositoryInterface;
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
        private EventRepositoryInterface $eventRepository,
    ) {
    }

    /**
     * Execute the use case
     *
     * @param int $userId
     * @param UpdateAvailabilityRequest $request
     * @return BoatResponse
     * @throws ValidationException
     * @throws BoatNotFoundException
     * @throws EventNotFoundException
     */
    public function execute(int $userId, UpdateAvailabilityRequest $request): BoatResponse
    {
        // Validate request
        $errors = $request->validate();
        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        // Find boat by owner user ID
        $boat = $this->boatRepository->findByOwnerUserId($userId);
        if ($boat === null) {
            throw new BoatNotFoundException("Boat not found for user ID: {$userId}");
        }

        // Get boat capacity once
        $maxBerths = $boat->getMaxBerths();

        // Update availability for each event
        foreach ($request->availabilities as $availability) {
            $eventId = EventId::fromString($availability['eventId']);

            // Validate event exists
            if (!$this->eventRepository->exists($eventId)) {
                throw new EventNotFoundException($availability['eventId']);
            }

            // Calculate berths: full capacity if available, 0 if not
            $berths = $availability['isAvailable'] ? $maxBerths : 0;

            // Validate capacity when setting available
            if ($availability['isAvailable'] && $maxBerths <= 0) {
                throw new ValidationException(['boat' => 'Boat has no capacity configured']);
            }

            $boat->setBerths($eventId, $berths);
        }

        // Save boat
        $this->boatRepository->save($boat);

        return BoatResponse::fromEntity($boat);
    }
}
