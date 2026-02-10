<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\UseCase\Crew\GetUserAssignmentsUseCase;
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
     * @param array $auth Authentication data (user_id, email, account_type, is_admin)
     */
    public function getUserAssignments(array $auth): JsonResponse
    {
        try {
            // Execute use case with user ID
            $assignments = $this->getUserAssignmentsUseCase->execute($auth['user_id']);

            return JsonResponse::success([
                'assignments' => array_map(fn($a) => $a->toArray(), $assignments),
            ]);
        } catch (\Exception $e) {
            return JsonResponse::serverError($e->getMessage());
        }
    }
}
