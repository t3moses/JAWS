<?php

declare(strict_types=1);

namespace Tests\Integration\Application\UseCase\Admin;

use App\Application\Exception\CrewNotFoundException;
use App\Application\UseCase\Admin\RemoveCrewFromFutureEventsUseCase;
use App\Domain\Enum\AvailabilityStatus;
use App\Domain\ValueObject\CrewKey;
use App\Domain\ValueObject\EventId;
use App\Infrastructure\Persistence\SQLite\CrewRepository;
use App\Infrastructure\Persistence\SQLite\EventRepository;
use App\Infrastructure\Persistence\SQLite\SeasonRepository;
use App\Infrastructure\Service\SystemTimeService;
use Psr\Log\NullLogger;
use Tests\Integration\IntegrationTestCase;

/**
 * Integration tests for RemoveCrewFromFutureEventsUseCase
 *
 * Verifies the admin "Remove from future events" action deletes
 * crew_availability rows only for future events (past events untouched)
 * and sets the crew's commitment rank to 0.
 */
class RemoveCrewFromFutureEventsUseCaseTest extends IntegrationTestCase
{
    private RemoveCrewFromFutureEventsUseCase $useCase;
    private CrewRepository $crewRepository;

    protected function setUp(): void
    {
        parent::setUp();

        // Simulated "now" (set in IntegrationTestCase::initializeSeasonConfig) is 2026-05-01.
        $this->createTestEvent('Fri Apr 17', '2026-04-17');
        $this->createTestEvent('Fri May 15', '2026-05-15');
        $this->createTestEvent('Fri May 22', '2026-05-22');

        $this->crewRepository = new CrewRepository();
        $seasonRepository = new SeasonRepository();
        $eventRepository = new EventRepository(new SystemTimeService($seasonRepository));

        $this->useCase = new RemoveCrewFromFutureEventsUseCase(
            $this->crewRepository,
            $eventRepository,
            new NullLogger()
        );
    }

    protected function createCrewProfileForUser(int $userId, array $overrides = []): string
    {
        $key = $overrides['key'] ?? 'crew_' . $userId;

        $stmt = $this->pdo->prepare('
            INSERT INTO crews (
                key, display_name, first_name, last_name, skill,
                commitment_rank, user_id, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        ');
        $stmt->execute([
            $key,
            $overrides['displayName'] ?? 'Test Crew',
            'Test',
            'Crew',
            1,
            $overrides['commitmentRank'] ?? 2,
            $userId,
        ]);

        return $key;
    }

    protected function getCommitmentRank(string $crewKey): int
    {
        $stmt = $this->pdo->prepare('SELECT commitment_rank FROM crews WHERE key = ?');
        $stmt->execute([$crewKey]);
        return (int) $stmt->fetchColumn();
    }

    protected function hasAvailabilityRecord(string $crewKey, string $eventId): bool
    {
        $stmt = $this->pdo->prepare('
            SELECT COUNT(*) FROM crew_availability ca
            JOIN crews c ON c.id = ca.crew_id
            WHERE c.key = ? AND ca.event_id = ?
        ');
        $stmt->execute([$crewKey, $eventId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function testRemovesAvailabilityForFutureEventsOnlyAndZeroesCommitmentRank(): void
    {
        $userId = $this->createTestUser('crew1@example.com', 'crew');
        $crewKey = $this->createCrewProfileForUser($userId, ['commitmentRank' => 2]);

        $this->crewRepository->updateAvailability(
            CrewKey::fromString($crewKey),
            EventId::fromString('Fri Apr 17'),
            AvailabilityStatus::NOT_SELECTED
        );
        $this->crewRepository->updateAvailability(
            CrewKey::fromString($crewKey),
            EventId::fromString('Fri May 15'),
            AvailabilityStatus::NOT_SELECTED
        );
        $this->crewRepository->updateAvailability(
            CrewKey::fromString($crewKey),
            EventId::fromString('Fri May 22'),
            AvailabilityStatus::SELECTED
        );

        $result = $this->useCase->execute($crewKey);

        $this->assertEquals(0, $result['rank_commitment']);
        $this->assertEquals(0, $this->getCommitmentRank($crewKey));

        // Past event availability is untouched.
        $this->assertTrue($this->hasAvailabilityRecord($crewKey, 'Fri Apr 17'));
        // Future events are withdrawn.
        $this->assertFalse($this->hasAvailabilityRecord($crewKey, 'Fri May 15'));
        $this->assertFalse($this->hasAvailabilityRecord($crewKey, 'Fri May 22'));
    }

    public function testCrewWithNoFutureAvailabilityStillZeroesCommitmentRank(): void
    {
        $userId = $this->createTestUser('crew2@example.com', 'crew');
        $crewKey = $this->createCrewProfileForUser($userId, ['commitmentRank' => 1]);

        $result = $this->useCase->execute($crewKey);

        $this->assertEquals(0, $result['rank_commitment']);
        $this->assertEquals(0, $this->getCommitmentRank($crewKey));
    }

    public function testCrewNotFoundThrowsException(): void
    {
        $this->expectException(CrewNotFoundException::class);

        $this->useCase->execute('nonexistent_crew');
    }
}
