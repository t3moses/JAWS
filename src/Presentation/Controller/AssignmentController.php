<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\UseCase\Crew\GetUserAssignmentsUseCase;
use App\Application\Exception\CrewNotFoundException;
use App\Domain\ValueObject\CrewKey;
use App\Presentation\Response\JsonResponse;

/**
 * Assignment Controller
 *
 * Handles assignment-related endpoints (authenticated access).
 */
class AssignmentController
{
    public function __construct(
        private GetUserAssignmentsUseCase $getUserAssignmentsUseCase,
    ) {
    }

    /**
     * GET /api/assignments
     *
     * Returns user's assignments across all events.
     *
     * @param array $auth Authentication data (first_name, last_name)
     */
    public function getUserAssignments(array $auth): JsonResponse
    {
        try {
            // Extract crew key from auth headers
            $crewKey = CrewKey::fromName($auth['first_name'], $auth['last_name']);

            $assignments = $this->getUserAssignmentsUseCase->execute($crewKey);

            return JsonResponse::success([
                'assignments' => array_map(fn($a) => $a->toArray(), $assignments),
            ]);
        } catch (CrewNotFoundException $e) {
            return JsonResponse::notFound($e->getMessage());
        } catch (\Exception $e) {
            return JsonResponse::serverError($e->getMessage());
        }
    }
}
