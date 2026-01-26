<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\UseCase\Boat\RegisterBoatUseCase;
use App\Application\UseCase\Boat\UpdateBoatAvailabilityUseCase;
use App\Application\UseCase\Crew\RegisterCrewUseCase;
use App\Application\UseCase\Crew\UpdateCrewAvailabilityUseCase;
use App\Application\UseCase\Season\ProcessSeasonUpdateUseCase;
use App\Application\DTO\Request\RegisterBoatRequest;
use App\Application\DTO\Request\RegisterCrewRequest;
use App\Application\DTO\Request\UpdateAvailabilityRequest;
use App\Application\Exception\ValidationException;
use App\Application\Exception\BoatNotFoundException;
use App\Application\Exception\CrewNotFoundException;
use App\Domain\ValueObject\BoatKey;
use App\Domain\ValueObject\CrewKey;
use App\Presentation\Response\JsonResponse;

/**
 * Availability Controller
 *
 * Handles registration and availability updates (authenticated endpoints).
 */
class AvailabilityController
{
    public function __construct(
        private RegisterBoatUseCase $registerBoatUseCase,
        private UpdateBoatAvailabilityUseCase $updateBoatAvailabilityUseCase,
        private RegisterCrewUseCase $registerCrewUseCase,
        private UpdateCrewAvailabilityUseCase $updateCrewAvailabilityUseCase,
        private ProcessSeasonUpdateUseCase $processSeasonUpdateUseCase,
    ) {
    }

    /**
     * POST /api/boats/register
     *
     * Register a boat (create or update).
     *
     * @param array $body Request body
     */
    public function registerBoat(array $body): JsonResponse
    {
        try {
            $request = new RegisterBoatRequest(
                displayName: $body['display_name'] ?? '',
                ownerFirstName: $body['owner_first_name'] ?? '',
                ownerLastName: $body['owner_last_name'] ?? '',
                ownerEmail: $body['owner_email'] ?? '',
                ownerMobile: $body['owner_mobile'] ?? null,
                minBerths: isset($body['min_berths']) ? (int)$body['min_berths'] : 1,
                maxBerths: isset($body['max_berths']) ? (int)$body['max_berths'] : 1,
                assistanceRequired: $body['assistance_required'] ?? 'No',
                socialPreference: $body['social_preference'] ?? 'No',
            );

            $response = $this->registerBoatUseCase->execute($request);

            // Trigger season update
            $this->processSeasonUpdateUseCase->execute();

            return JsonResponse::success([
                'boat' => $response->toArray(),
                'message' => 'Boat registered successfully',
            ], 201);
        } catch (ValidationException $e) {
            return JsonResponse::error($e->getMessage(), 400, $e->getErrors());
        } catch (\Exception $e) {
            return JsonResponse::serverError($e->getMessage());
        }
    }

    /**
     * PATCH /api/boats/availability
     *
     * Update boat availability (berths offered per event).
     *
     * @param array $body Request body
     * @param array $auth Authentication data
     */
    public function updateBoatAvailability(array $body, array $auth): JsonResponse
    {
        try {
            // Extract boat key from auth (boat owner name)
            $boatKey = BoatKey::fromBoatName($body['boat_name'] ?? '');

            $request = new UpdateAvailabilityRequest(
                availabilities: $body['availabilities'] ?? []
            );

            $response = $this->updateBoatAvailabilityUseCase->execute($boatKey, $request);

            // Trigger season update
            $this->processSeasonUpdateUseCase->execute();

            return JsonResponse::success([
                'boat' => $response->toArray(),
                'message' => 'Boat availability updated successfully',
            ]);
        } catch (ValidationException $e) {
            return JsonResponse::error($e->getMessage(), 400, $e->getErrors());
        } catch (BoatNotFoundException $e) {
            return JsonResponse::notFound($e->getMessage());
        } catch (\Exception $e) {
            return JsonResponse::serverError($e->getMessage());
        }
    }

    /**
     * POST /api/crews/register
     *
     * Register a crew member (create or update).
     *
     * @param array $body Request body
     */
    public function registerCrew(array $body): JsonResponse
    {
        try {
            $request = new RegisterCrewRequest(
                displayName: $body['display_name'] ?? '',
                firstName: $body['first_name'] ?? '',
                lastName: $body['last_name'] ?? '',
                partnerFirstName: $body['partner_first_name'] ?? null,
                partnerLastName: $body['partner_last_name'] ?? null,
                email: $body['email'] ?? '',
                mobile: $body['mobile'] ?? null,
                socialPreference: $body['social_preference'] ?? 'No',
                membershipNumber: $body['membership_number'] ?? null,
                skill: isset($body['skill']) ? (int)$body['skill'] : 0,
                experience: $body['experience'] ?? null,
            );

            $response = $this->registerCrewUseCase->execute($request);

            // Trigger season update
            $this->processSeasonUpdateUseCase->execute();

            return JsonResponse::success([
                'crew' => $response->toArray(),
                'message' => 'Crew registered successfully',
            ], 201);
        } catch (ValidationException $e) {
            return JsonResponse::error($e->getMessage(), 400, $e->getErrors());
        } catch (\Exception $e) {
            return JsonResponse::serverError($e->getMessage());
        }
    }

    /**
     * PATCH /api/users/me/availability
     *
     * Update crew availability (status per event).
     *
     * @param array $body Request body
     * @param array $auth Authentication data (first_name, last_name)
     */
    public function updateCrewAvailability(array $body, array $auth): JsonResponse
    {
        try {
            // Extract crew key from auth headers
            $crewKey = CrewKey::fromName($auth['first_name'], $auth['last_name']);

            $request = new UpdateAvailabilityRequest(
                availabilities: $body['availabilities'] ?? []
            );

            $response = $this->updateCrewAvailabilityUseCase->execute($crewKey, $request);

            // Trigger season update
            $this->processSeasonUpdateUseCase->execute();

            return JsonResponse::success([
                'crew' => $response->toArray(),
                'message' => 'Availability updated successfully',
            ]);
        } catch (ValidationException $e) {
            return JsonResponse::error($e->getMessage(), 400, $e->getErrors());
        } catch (CrewNotFoundException $e) {
            return JsonResponse::notFound($e->getMessage());
        } catch (\Exception $e) {
            return JsonResponse::serverError($e->getMessage());
        }
    }
}
