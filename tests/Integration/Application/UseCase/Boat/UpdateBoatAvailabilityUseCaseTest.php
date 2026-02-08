<?php

declare(strict_types=1);

namespace Tests\Integration\Application\UseCase\Boat;

use App\Application\DTO\Request\UpdateAvailabilityRequest;
use App\Application\Exception\ValidationException;
use App\Application\Exception\BoatNotFoundException;
use App\Application\Exception\EventNotFoundException;
use App\Application\UseCase\Boat\UpdateBoatAvailabilityUseCase;
use App\Infrastructure\Persistence\SQLite\BoatRepository;
use App\Infrastructure\Persistence\SQLite\EventRepository;
use App\Infrastructure\Persistence\SQLite\Connection;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for UpdateBoatAvailabilityUseCase
 *
 * Tests the complete boat availability update workflow including:
 * - Single and multiple event availability updates
 * - Berths calculation (maxBerths when available, 0 when unavailable)
 * - Validation scenarios
 * - Edge cases and error conditions
 */
class UpdateBoatAvailabilityUseCaseTest extends TestCase
{
    private PDO $pdo;
    private UpdateBoatAvailabilityUseCase $useCase;
    private BoatRepository $boatRepository;
    private EventRepository $eventRepository;

    protected function setUp(): void
    {
        // Create in-memory database
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Run migrations
        $this->runMigrations();

        // Initialize season config and test data
        $this->initializeSeasonConfig();
        $this->initializeTestData();

        // Set test connection
        Connection::setTestConnection($this->pdo);

        // Initialize repositories
        $this->boatRepository = new BoatRepository();
        $this->eventRepository = new EventRepository();

        // Initialize use case
        $this->useCase = new UpdateBoatAvailabilityUseCase(
            $this->boatRepository,
            $this->eventRepository
        );
    }

    protected function tearDown(): void
    {
        Connection::resetTestConnection();
    }

    // ==================== HELPER METHODS ====================

    /**
     * Run database migrations
     */
    private function runMigrations(): void
    {
        $schemaFile = __DIR__ . '/../../../../fixtures/001_initial_schema.sql';
        $userSchemaFile = __DIR__ . '/../../../../fixtures/002_add_users_authentication.sql';

        foreach ([$schemaFile, $userSchemaFile] as $file) {
            if (file_exists($file)) {
                $schema = file_get_contents($file);
                $this->executeSqlStatements($schema);
            }
        }
    }

    /**
     * Execute SQL statements from a schema file
     */
    private function executeSqlStatements(string $sql): void
    {
        $lines = explode("\n", $sql);
        $cleanedSql = '';
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '--')) {
                continue;
            }
            $commentPos = strpos($line, '--');
            if ($commentPos !== false) {
                $line = substr($line, 0, $commentPos);
            }
            $cleanedSql .= $line . "\n";
        }

        $statements = explode(';', $cleanedSql);
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                try {
                    $this->pdo->exec($statement);
                } catch (\PDOException $e) {
                    // Ignore errors for test compatibility
                }
            }
        }
    }

    /**
     * Initialize season config
     */
    private function initializeSeasonConfig(): void
    {
        $this->pdo->exec("
            INSERT OR REPLACE INTO season_config (id, year, source, simulated_date, start_time, finish_time, blackout_from, blackout_to)
            VALUES (1, 2026, 'simulated', '2026-05-01', '12:45:00', '17:00:00', '10:00:00', '18:00:00')
        ");
    }

    /**
     * Initialize test data (events)
     */
    private function initializeTestData(): void
    {
        // Insert test events
        $events = [
            ['Fri May 15', '2026-05-15', '12:45:00', '17:00:00', 'upcoming'],
            ['Fri May 22', '2026-05-22', '12:45:00', '17:00:00', 'upcoming'],
            ['Fri May 29', '2026-05-29', '12:45:00', '17:00:00', 'upcoming'],
            ['Fri Jun 05', '2026-06-05', '12:45:00', '17:00:00', 'upcoming'],
        ];

        foreach ($events as $event) {
            $this->pdo->exec("
                INSERT INTO events (event_id, event_date, start_time, finish_time, status)
                VALUES ('{$event[0]}', '{$event[1]}', '{$event[2]}', '{$event[3]}', '{$event[4]}')
            ");
        }
    }

    /**
     * Create test user
     */
    private function createTestUser(string $email = 'test@example.com'): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO users (email, password_hash, account_type, is_admin, created_at, updated_at)
            VALUES (?, ?, ?, 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        ');
        $stmt->execute([
            $email,
            password_hash('TestPass123', PASSWORD_BCRYPT),
            'boat_owner'
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Create boat profile for user
     */
    private function createBoatProfileForUser(int $userId, array $overrides = []): string
    {
        $key = $overrides['key'] ?? 'boat_' . $userId;

        $stmt = $this->pdo->prepare('
            INSERT INTO boats (
                key, display_name, owner_first_name, owner_last_name, owner_email, owner_mobile,
                min_berths, max_berths, assistance_required, social_preference,
                owner_user_id, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        ');

        $stmt->execute([
            $key,
            $overrides['displayName'] ?? 'Test Boat',
            $overrides['ownerFirstName'] ?? 'Test',
            $overrides['ownerLastName'] ?? 'Owner',
            $overrides['ownerEmail'] ?? 'owner@example.com',
            $overrides['ownerMobile'] ?? '555-5678',
            $overrides['minBerths'] ?? 2,
            $overrides['maxBerths'] ?? 3,
            $overrides['assistanceRequired'] ?? 'No',
            $overrides['socialPreference'] ?? 'No',
            $userId
        ]);

        return $key;
    }

    /**
     * Get boat availability from database
     */
    private function getBoatAvailability(string $boatKey, string $eventId): ?int
    {
        $stmt = $this->pdo->prepare('
            SELECT ba.berths FROM boat_availability ba
            JOIN boats b ON ba.boat_id = b.id
            WHERE b.key = ? AND ba.event_id = ?
        ');
        $stmt->execute([$boatKey, $eventId]);
        $result = $stmt->fetchColumn();
        return $result !== false ? (int)$result : null;
    }

    // ==================== HAPPY PATH TESTS ====================

    public function testUpdateAvailabilityForSingleEvent(): void
    {
        // Arrange
        $userId = $this->createTestUser('owner1@example.com');
        $boatKey = $this->createBoatProfileForUser($userId, ['maxBerths' => 3]);

        $request = new UpdateAvailabilityRequest([
            ['eventId' => 'Fri May 15', 'isAvailable' => true]
        ]);

        // Act
        $response = $this->useCase->execute($userId, $request);

        // Assert
        $this->assertEquals($boatKey, $response->key);
        $this->assertArrayHasKey('Fri May 15', $response->availabilities);
        $this->assertEquals(3, $response->availabilities['Fri May 15']); // maxBerths

        // Verify database
        $berths = $this->getBoatAvailability($boatKey, 'Fri May 15');
        $this->assertEquals(3, $berths);
    }

    public function testUpdateAvailabilityForMultipleEvents(): void
    {
        // Arrange
        $userId = $this->createTestUser('owner2@example.com');
        $boatKey = $this->createBoatProfileForUser($userId, ['maxBerths' => 4]);

        $request = new UpdateAvailabilityRequest([
            ['eventId' => 'Fri May 15', 'isAvailable' => true],
            ['eventId' => 'Fri May 22', 'isAvailable' => false],
            ['eventId' => 'Fri May 29', 'isAvailable' => true],
        ]);

        // Act
        $response = $this->useCase->execute($userId, $request);

        // Assert
        $this->assertEquals(4, $response->availabilities['Fri May 15']); // maxBerths when available
        $this->assertEquals(0, $response->availabilities['Fri May 22']); // 0 when unavailable
        $this->assertEquals(4, $response->availabilities['Fri May 29']); // maxBerths when available

        // Verify database
        $this->assertEquals(4, $this->getBoatAvailability($boatKey, 'Fri May 15'));
        $this->assertEquals(0, $this->getBoatAvailability($boatKey, 'Fri May 22'));
        $this->assertEquals(4, $this->getBoatAvailability($boatKey, 'Fri May 29'));
    }

    public function testUpdateAvailabilityToAvailable(): void
    {
        // Arrange
        $userId = $this->createTestUser();
        $boatKey = $this->createBoatProfileForUser($userId, ['maxBerths' => 2]);

        $request = new UpdateAvailabilityRequest([
            ['eventId' => 'Fri May 15', 'isAvailable' => true]
        ]);

        // Act
        $response = $this->useCase->execute($userId, $request);

        // Assert - should set berths to maxBerths
        $this->assertEquals(2, $response->availabilities['Fri May 15']);
    }

    public function testUpdateAvailabilityToUnavailable(): void
    {
        // Arrange
        $userId = $this->createTestUser();
        $boatKey = $this->createBoatProfileForUser($userId, ['maxBerths' => 3]);

        $request = new UpdateAvailabilityRequest([
            ['eventId' => 'Fri May 15', 'isAvailable' => false]
        ]);

        // Act
        $response = $this->useCase->execute($userId, $request);

        // Assert - should set berths to 0
        $this->assertEquals(0, $response->availabilities['Fri May 15']);
    }

    public function testUpdateSameEventMultipleTimes(): void
    {
        // Arrange
        $userId = $this->createTestUser();
        $boatKey = $this->createBoatProfileForUser($userId, ['maxBerths' => 3]);

        // First update - available
        $request1 = new UpdateAvailabilityRequest([
            ['eventId' => 'Fri May 15', 'isAvailable' => true]
        ]);
        $response1 = $this->useCase->execute($userId, $request1);
        $this->assertEquals(3, $response1->availabilities['Fri May 15']);

        // Second update - unavailable
        $request2 = new UpdateAvailabilityRequest([
            ['eventId' => 'Fri May 15', 'isAvailable' => false]
        ]);
        $response2 = $this->useCase->execute($userId, $request2);
        $this->assertEquals(0, $response2->availabilities['Fri May 15']);

        // Third update - available again
        $request3 = new UpdateAvailabilityRequest([
            ['eventId' => 'Fri May 15', 'isAvailable' => true]
        ]);
        $response3 = $this->useCase->execute($userId, $request3);
        $this->assertEquals(3, $response3->availabilities['Fri May 15']);

        // Verify final state in database
        $this->assertEquals(3, $this->getBoatAvailability($boatKey, 'Fri May 15'));
    }

    public function testUpdateAllSeasonEvents(): void
    {
        // Arrange
        $userId = $this->createTestUser();
        $boatKey = $this->createBoatProfileForUser($userId, ['maxBerths' => 4]);

        $request = new UpdateAvailabilityRequest([
            ['eventId' => 'Fri May 15', 'isAvailable' => true],
            ['eventId' => 'Fri May 22', 'isAvailable' => true],
            ['eventId' => 'Fri May 29', 'isAvailable' => true],
            ['eventId' => 'Fri Jun 05', 'isAvailable' => true],
        ]);

        // Act
        $response = $this->useCase->execute($userId, $request);

        // Assert - all events should have maxBerths
        foreach (['Fri May 15', 'Fri May 22', 'Fri May 29', 'Fri Jun 05'] as $eventId) {
            $this->assertEquals(4, $response->availabilities[$eventId]);
            $this->assertEquals(4, $this->getBoatAvailability($boatKey, $eventId));
        }
    }

    public function testBerthsCalculationWithDifferentCapacities(): void
    {
        // Test with min=2, max=5
        $userId1 = $this->createTestUser('owner3@example.com');
        $boatKey1 = $this->createBoatProfileForUser($userId1, [
            'key' => 'boat_large',
            'minBerths' => 2,
            'maxBerths' => 5
        ]);

        $request1 = new UpdateAvailabilityRequest([
            ['eventId' => 'Fri May 15', 'isAvailable' => true]
        ]);
        $response1 = $this->useCase->execute($userId1, $request1);
        $this->assertEquals(5, $response1->availabilities['Fri May 15']);

        // Test with min=1, max=2
        $userId2 = $this->createTestUser('owner4@example.com');
        $boatKey2 = $this->createBoatProfileForUser($userId2, [
            'key' => 'boat_small',
            'minBerths' => 1,
            'maxBerths' => 2
        ]);

        $request2 = new UpdateAvailabilityRequest([
            ['eventId' => 'Fri May 15', 'isAvailable' => true]
        ]);
        $response2 = $this->useCase->execute($userId2, $request2);
        $this->assertEquals(2, $response2->availabilities['Fri May 15']);
    }

    // ==================== VALIDATION TESTS ====================

    public function testValidationErrorForInvalidDataStructure(): void
    {
        // Arrange
        $userId = $this->createTestUser();
        $this->createBoatProfileForUser($userId);

        // Invalid: not an array of objects
        $request = new UpdateAvailabilityRequest([
            'invalid_structure'
        ]);

        // Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Each availability must be an object');

        // Act
        $this->useCase->execute($userId, $request);
    }

    public function testValidationErrorForMissingEventId(): void
    {
        // Arrange
        $userId = $this->createTestUser();
        $this->createBoatProfileForUser($userId);

        $request = new UpdateAvailabilityRequest([
            ['isAvailable' => true] // Missing eventId
        ]);

        // Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Event ID is required');

        // Act
        $this->useCase->execute($userId, $request);
    }

    public function testValidationErrorForMissingIsAvailable(): void
    {
        // Arrange
        $userId = $this->createTestUser();
        $this->createBoatProfileForUser($userId);

        $request = new UpdateAvailabilityRequest([
            ['eventId' => 'Fri May 15'] // Missing isAvailable
        ]);

        // Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('isAvailable is required');

        // Act
        $this->useCase->execute($userId, $request);
    }

    public function testValidationErrorForNonBooleanIsAvailable(): void
    {
        // Arrange
        $userId = $this->createTestUser();
        $this->createBoatProfileForUser($userId);

        $request = new UpdateAvailabilityRequest([
            ['eventId' => 'Fri May 15', 'isAvailable' => 'yes'] // String instead of boolean
        ]);

        // Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('must be a boolean');

        // Act
        $this->useCase->execute($userId, $request);
    }

    public function testValidationErrorForNonStringEventId(): void
    {
        // Arrange
        $userId = $this->createTestUser();
        $this->createBoatProfileForUser($userId);

        $request = new UpdateAvailabilityRequest([
            ['eventId' => 123, 'isAvailable' => true] // Integer instead of string
        ]);

        // Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('must be a string');

        // Act
        $this->useCase->execute($userId, $request);
    }

    public function testValidationErrorForZeroCapacity(): void
    {
        // Arrange
        $userId = $this->createTestUser();
        $this->createBoatProfileForUser($userId, ['maxBerths' => 0]);

        $request = new UpdateAvailabilityRequest([
            ['eventId' => 'Fri May 15', 'isAvailable' => true]
        ]);

        // Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Boat has no capacity configured');

        // Act
        $this->useCase->execute($userId, $request);
    }

    public function testValidationErrorForNegativeCapacity(): void
    {
        // Arrange
        $userId = $this->createTestUser();
        $this->createBoatProfileForUser($userId, ['maxBerths' => -1]);

        $request = new UpdateAvailabilityRequest([
            ['eventId' => 'Fri May 15', 'isAvailable' => true]
        ]);

        // Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Boat has no capacity configured');

        // Act
        $this->useCase->execute($userId, $request);
    }

    // ==================== ERROR CONDITION TESTS ====================

    public function testBoatNotFoundThrowsException(): void
    {
        // Arrange
        $nonExistentUserId = 99999;

        $request = new UpdateAvailabilityRequest([
            ['eventId' => 'Fri May 15', 'isAvailable' => true]
        ]);

        // Assert
        $this->expectException(BoatNotFoundException::class);
        $this->expectExceptionMessage('Boat not found for user ID: 99999');

        // Act
        $this->useCase->execute($nonExistentUserId, $request);
    }

    public function testEventNotFoundThrowsException(): void
    {
        // Arrange
        $userId = $this->createTestUser();
        $this->createBoatProfileForUser($userId);

        $request = new UpdateAvailabilityRequest([
            ['eventId' => 'Fri Jul 99', 'isAvailable' => true] // Non-existent event
        ]);

        // Assert
        $this->expectException(EventNotFoundException::class);
        $this->expectExceptionMessage('Event not found');

        // Act
        $this->useCase->execute($userId, $request);
    }

    public function testUserWithoutBoatProfileThrowsException(): void
    {
        // Arrange
        $userId = $this->createTestUser('noboat@example.com');
        // Don't create boat profile

        $request = new UpdateAvailabilityRequest([
            ['eventId' => 'Fri May 15', 'isAvailable' => true]
        ]);

        // Assert
        $this->expectException(BoatNotFoundException::class);

        // Act
        $this->useCase->execute($userId, $request);
    }

    // ==================== EDGE CASE TESTS ====================

    public function testEmptyAvailabilityArrayIsValid(): void
    {
        // Arrange
        $userId = $this->createTestUser();
        $boatKey = $this->createBoatProfileForUser($userId);

        // Empty array is allowed (validation is currently commented out in the DTO)
        $request = new UpdateAvailabilityRequest([]);

        // Act
        $response = $this->useCase->execute($userId, $request);

        // Assert - should succeed without updating anything
        $this->assertEquals($boatKey, $response->key);
    }

    public function testPartialValidationErrorsInBatch(): void
    {
        // Arrange
        $userId = $this->createTestUser();
        $this->createBoatProfileForUser($userId);

        // One valid, one invalid
        $request = new UpdateAvailabilityRequest([
            ['eventId' => 'Fri May 15', 'isAvailable' => true], // Valid
            ['eventId' => 'Fri May 22'] // Invalid - missing isAvailable
        ]);

        // Assert - should fail validation before any database updates
        $this->expectException(ValidationException::class);

        // Act
        $this->useCase->execute($userId, $request);
    }

    public function testMixedEventValidityInBatch(): void
    {
        // Arrange
        $userId = $this->createTestUser();
        $boatKey = $this->createBoatProfileForUser($userId);

        // First event valid, second invalid
        $request = new UpdateAvailabilityRequest([
            ['eventId' => 'Fri May 15', 'isAvailable' => true], // Valid event
            ['eventId' => 'Invalid Event', 'isAvailable' => true] // Invalid event
        ]);

        // Assert - should fail on second event
        $this->expectException(EventNotFoundException::class);
        $this->expectExceptionMessage('Event not found');

        // Act
        $this->useCase->execute($userId, $request);
    }

    public function testPreservesExistingBoatData(): void
    {
        // Arrange
        $userId = $this->createTestUser();
        $boatKey = $this->createBoatProfileForUser($userId, [
            'displayName' => 'Original Boat Name',
            'ownerMobile' => '555-1234',
            'maxBerths' => 5
        ]);

        $request = new UpdateAvailabilityRequest([
            ['eventId' => 'Fri May 15', 'isAvailable' => true]
        ]);

        // Act
        $response = $this->useCase->execute($userId, $request);

        // Assert - boat data should remain unchanged
        $this->assertEquals('Original Boat Name', $response->displayName);
        $this->assertEquals('555-1234', $response->ownerMobile);
        $this->assertEquals(5, $response->maxBerths);
    }

    public function testResponseContainsCompleteBoatData(): void
    {
        // Arrange
        $userId = $this->createTestUser();
        $boatKey = $this->createBoatProfileForUser($userId);

        $request = new UpdateAvailabilityRequest([
            ['eventId' => 'Fri May 15', 'isAvailable' => true]
        ]);

        // Act
        $response = $this->useCase->execute($userId, $request);

        // Assert - response should contain all boat fields
        $this->assertNotNull($response->key);
        $this->assertNotNull($response->ownerFirstName);
        $this->assertNotNull($response->ownerLastName);
        $this->assertNotNull($response->ownerEmail);
        $this->assertNotNull($response->ownerMobile);
        $this->assertIsInt($response->minBerths);
        $this->assertIsInt($response->maxBerths);
        $this->assertIsBool($response->assistanceRequired);
        $this->assertIsBool($response->socialPreference);
        $this->assertIsArray($response->availabilities);
        $this->assertIsArray($response->rank);
        $this->assertIsArray($response->history);
    }

    public function testConcurrentAvailabilityUpdatesForDifferentBoats(): void
    {
        // Arrange
        $userId1 = $this->createTestUser('owner1@example.com');
        $boatKey1 = $this->createBoatProfileForUser($userId1, ['key' => 'boat_1', 'maxBerths' => 3]);

        $userId2 = $this->createTestUser('owner2@example.com');
        $boatKey2 = $this->createBoatProfileForUser($userId2, ['key' => 'boat_2', 'maxBerths' => 4]);

        $request1 = new UpdateAvailabilityRequest([
            ['eventId' => 'Fri May 15', 'isAvailable' => true]
        ]);

        $request2 = new UpdateAvailabilityRequest([
            ['eventId' => 'Fri May 15', 'isAvailable' => false]
        ]);

        // Act
        $response1 = $this->useCase->execute($userId1, $request1);
        $response2 = $this->useCase->execute($userId2, $request2);

        // Assert - each boat should have independent availability
        $this->assertEquals(3, $response1->availabilities['Fri May 15']); // boat_1: 3 berths
        $this->assertEquals(0, $response2->availabilities['Fri May 15']); // boat_2: 0 berths

        // Verify in database
        $this->assertEquals(3, $this->getBoatAvailability('boat_1', 'Fri May 15'));
        $this->assertEquals(0, $this->getBoatAvailability('boat_2', 'Fri May 15'));
    }

    public function testZeroCapacityAllowedWhenUnavailable(): void
    {
        // Arrange - boat with zero capacity should be allowed to mark as unavailable
        $userId = $this->createTestUser();
        $boatKey = $this->createBoatProfileForUser($userId, ['maxBerths' => 0]);

        $request = new UpdateAvailabilityRequest([
            ['eventId' => 'Fri May 15', 'isAvailable' => false]
        ]);

        // Act - should not throw exception
        $response = $this->useCase->execute($userId, $request);

        // Assert
        $this->assertEquals(0, $response->availabilities['Fri May 15']);
        $this->assertEquals(0, $this->getBoatAvailability($boatKey, 'Fri May 15'));
    }

    public function testUpdateWithMinimalBoatCapacity(): void
    {
        // Arrange - boat with 1 berth
        $userId = $this->createTestUser();
        $boatKey = $this->createBoatProfileForUser($userId, ['minBerths' => 1, 'maxBerths' => 1]);

        $request = new UpdateAvailabilityRequest([
            ['eventId' => 'Fri May 15', 'isAvailable' => true]
        ]);

        // Act
        $response = $this->useCase->execute($userId, $request);

        // Assert - should use maxBerths (1)
        $this->assertEquals(1, $response->availabilities['Fri May 15']);
    }

    public function testUpdateWithMaximumBoatCapacity(): void
    {
        // Arrange - boat with high capacity
        $userId = $this->createTestUser();
        $boatKey = $this->createBoatProfileForUser($userId, ['minBerths' => 5, 'maxBerths' => 10]);

        $request = new UpdateAvailabilityRequest([
            ['eventId' => 'Fri May 15', 'isAvailable' => true]
        ]);

        // Act
        $response = $this->useCase->execute($userId, $request);

        // Assert - should use maxBerths (10)
        $this->assertEquals(10, $response->availabilities['Fri May 15']);
    }
}
