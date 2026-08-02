<?php

declare(strict_types=1);

namespace App\Application\UseCase\Admin;

use App\Application\Exception\CrewNotFoundException;
use App\Application\Port\Repository\CrewRepositoryInterface;
use App\Application\Port\Repository\EventRepositoryInterface;
use App\Domain\Enum\CrewRankDimension;
use App\Domain\ValueObject\CrewKey;
use Psr\Log\LoggerInterface;

/**
 * Remove Crew From Future Events Use Case
 *
 * Admin action that withdraws a crew member from every future event (deletes
 * their crew_availability rows for those events, the same effect as a manual
 * withdrawal) and sets their commitment rank to 0, the same treatment as a
 * repeat no-show flagged by a boat owner (see FlagAssignedCrewUseCase).
 */
class RemoveCrewFromFutureEventsUseCase
{
    public function __construct(
        private CrewRepositoryInterface $crewRepository,
        private EventRepositoryInterface $eventRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function execute(string $crewKey): array
    {
        $crew = $this->crewRepository->findByKey(CrewKey::fromString($crewKey));
        if ($crew === null) {
            throw new CrewNotFoundException("Crew not found: {$crewKey}");
        }

        $crew->setRankDimension(CrewRankDimension::COMMITMENT, 0);
        $this->crewRepository->updateRankCommitment($crew);

        $futureEventIds = $this->eventRepository->findFutureEvents();
        if (!empty($futureEventIds)) {
            $this->crewRepository->deleteAvailabilityForEvents($crew->getKey(), $futureEventIds);
        }

        $this->logger->info('admin.crew_removed_from_future_events', [
            'crew_key' => $crewKey,
            'future_event_count' => count($futureEventIds),
        ]);

        return [
            'crew_key' => $crewKey,
            'rank_commitment' => 0,
        ];
    }
}
