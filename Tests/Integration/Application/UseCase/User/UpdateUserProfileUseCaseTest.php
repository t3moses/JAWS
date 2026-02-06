<?php

declare(strict_types=1);

namespace Tests\Integration\Application\UseCase\User;

use App\Application\DTO\Request\UpdateProfileRequest;
use App\Application\Exception\ValidationException;
use App\Application\Exception\WeakPasswordException;
use App\Application\Port\Service\PasswordServiceInterface;
use App\Application\UseCase\User\GetUserProfileUseCase;
use App\Application\UseCase\User\UpdateUserProfileUseCase;
use App\Domain\Enum\SkillLevel;
use App\Infrastructure\Persistence\SQLite\BoatRepository;
use App\Infrastructure\Persistence\SQLite\CrewRepository;
use App\Infrastructure\Persistence\SQLite\UserRepository;
use App\Infrastructure\Persistence\SQLite\Connection;
use PDO;
use PHPUnit\Framework\TestCase;
use Phinx\Config\Config;
use Phinx\Migration\Manager;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;

/**
 * Integration tests for UpdateUserProfileUseCase
 *
 * Tests the complete profile update workflow including:
 * - User account updates (email, password)
 * - Crew profile updates (display name, mobile, skill, etc.)
 * - Boat profile updates (display name, mobile, berths, etc.)
 * - Validation scenarios
 * - Edge cases and error conditions
 */
class UpdateUserProfileUseCaseTest extends TestCase
{
    private PDO $pdo;
    private UpdateUserProfileUseCase $useCase;
    private GetUserProfileUseCase $getUserProfileUseCase;
    private UserRepository $userRepository;
    private CrewRepository $crewRepository;
    private BoatRepository $boatRepository;
    private PasswordServiceInterface $passwordService;

    protected function setUp(): void
    {
        // Create in-memory database
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Run Phinx migrations
        $this->runPhinxMigrations();

        // Initialize season config for compatibility
        $this->initializeSeasonConfig();

        // Set test connection
        Connection::setTestConnection($this->pdo);

        // Initialize repositories
        $this->userRepository = new UserRepository();
        $this->crewRepository = new CrewRepository();
        $this->boatRepository = new BoatRepository();

        // Initialize password service mock
        $this->passwordService = $this->createPasswordServiceMock();

        // Initialize use cases
        $this->getUserProfileUseCase = new GetUserProfileUseCase(
            $this->userRepository,
            $this->crewRepository,
            $this->boatRepository
        );

        $this->useCase = new UpdateUserProfileUseCase(
            $this->userRepository,
            $this->crewRepository,
            $this->boatRepository,
            $this->passwordService,
            $this->getUserProfileUseCase
        );
    }

    protected function tearDown(): void
    {
        Connection::resetTestConnection();
    }

    // ==================== HELPER METHODS ====================

    /**
     * Run Phinx migrations programmatically
     */
    private function runPhinxMigrations(): void
    {
        // Load schema from archived SQL files
        // Note: Phinx programmatic usage proved complex for in-memory testing
        // Using direct SQL execution as proven approach
        $schemaFile = __DIR__ . '/../../../../../database/migrations/archive/001_initial_schema.sql';
        $userSchemaFile = __DIR__ . '/../../../../../database/migrations/archive/002_add_users_authentication.sql';

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
        // Add last_logout column if it doesn't exist (from later migration)
        try {
            $this->pdo->exec("ALTER TABLE users ADD COLUMN last_logout DATETIME");
        } catch (\PDOException $e) {
            // Column already exists or other error - ignore
        }

        // Make display_name nullable for crews (from later migration)
        // SQLite doesn't support ALTER COLUMN, so we skip this for tests

        $this->pdo->exec("
            INSERT OR REPLACE INTO season_config (id, year, source, simulated_date, start_time, finish_time, blackout_from, blackout_to)
            VALUES (1, 2026, 'simulated', '2026-05-01', '12:45:00', '17:00:00', '10:00:00', '18:00:00')
        ");
    }

    /**
     * Create password service mock with default behavior
     */
    private function createPasswordServiceMock(): PasswordServiceInterface
    {
        $mock = $this->createMock(PasswordServiceInterface::class);

        // Default: all passwords meet requirements
        $mock->method('meetsRequirements')->willReturn(true);

        // Hash any password to a bcrypt-style hash
        $mock->method('hash')->willReturnCallback(
            fn(string $password) => password_hash($password, PASSWORD_BCRYPT)
        );

        // Default requirements message
        $mock->method('getRequirementsMessage')->willReturn(
            'Password must be at least 8 characters long and contain uppercase, lowercase, number, and special character.'
        );

        return $mock;
    }

    /**
     * Create test user with optional crew/boat profile
     */
    private function createTestUser(
        string $email = 'test@example.com',
        string $accountType = 'crew',
        bool $createCrewProfile = false,
        bool $createBoatProfile = false
    ): int {
        // Insert user
        $stmt = $this->pdo->prepare('
            INSERT INTO users (email, password_hash, account_type, is_admin, created_at, updated_at)
            VALUES (?, ?, ?, 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        ');
        $stmt->execute([
            $email,
            password_hash('TestPass123', PASSWORD_BCRYPT),
            $accountType
        ]);

        $userId = (int)$this->pdo->lastInsertId();

        // Create crew profile if requested
        if ($createCrewProfile) {
            $this->createCrewProfileForUser($userId);
        }

        // Create boat profile if requested
        if ($createBoatProfile) {
            $this->createBoatProfileForUser($userId);
        }

        return $userId;
    }

    /**
     * Create crew profile for user
     */
    private function createCrewProfileForUser(int $userId, array $overrides = []): int
    {
        $key = $overrides['key'] ?? 'crew_' . $userId;

        $stmt = $this->pdo->prepare('
            INSERT INTO crews (
                key, display_name, first_name, last_name, skill, mobile, email,
                membership_number, social_preference, experience,
                user_id, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        ');

        $stmt->execute([
            $key,
            $overrides['displayName'] ?? 'TestCrew',
            $overrides['firstName'] ?? 'Test',
            $overrides['lastName'] ?? 'Crew',
            $overrides['skill'] ?? SkillLevel::INTERMEDIATE->value,
            $overrides['mobile'] ?? '555-1234',
            $overrides['email'] ?? 'crew@example.com',
            $overrides['membershipNumber'] ?? '12345',
            $overrides['socialPreference'] ?? 'No', // Must be 'Yes' or 'No'
            $overrides['experience'] ?? 'Some sailing experience',
            $userId
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Create boat profile for user
     */
    private function createBoatProfileForUser(int $userId, array $overrides = []): int
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
            $overrides['displayName'] ?? 'TestBoat',
            $overrides['ownerFirstName'] ?? 'Test',
            $overrides['ownerLastName'] ?? 'Owner',
            $overrides['ownerEmail'] ?? 'boat@example.com',
            $overrides['ownerMobile'] ?? '555-5678',
            $overrides['minBerths'] ?? 2,
            $overrides['maxBerths'] ?? 4,
            $overrides['assistanceRequired'] ?? 'No', // Must be 'Yes' or 'No'
            $overrides['socialPreference'] ?? 'No', // Must be 'Yes' or 'No'
            $userId
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Get user email from database
     */
    private function getUserEmail(int $userId): string
    {
        $stmt = $this->pdo->prepare('SELECT email FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }

    /**
     * Get crew display name from database
     */
    private function getCrewDisplayName(int $userId): ?string
    {
        $stmt = $this->pdo->prepare('SELECT display_name FROM crews WHERE user_id = ?');
        $stmt->execute([$userId]);
        $result = $stmt->fetchColumn();
        return $result !== false ? $result : null;
    }

    /**
     * Get boat display name from database
     */
    private function getBoatDisplayName(int $userId): ?string
    {
        $stmt = $this->pdo->prepare('SELECT display_name FROM boats WHERE owner_user_id = ?');
        $stmt->execute([$userId]);
        $result = $stmt->fetchColumn();
        return $result !== false ? $result : null;
    }

    // ==================== HAPPY PATH TESTS ====================

    public function testUpdateEmailOnly(): void
    {
        // Arrange
        $userId = $this->createTestUser('original@example.com');
        $request = new UpdateProfileRequest(
            email: 'updated@example.com'
        );

        // Act
        $response = $this->useCase->execute($userId, $request);

        // Assert
        $this->assertEquals('updated@example.com', $response->user->email);
        $this->assertEquals('updated@example.com', $this->getUserEmail($userId));
    }

    public function testUpdatePasswordOnly(): void
    {
        // Arrange
        $userId = $this->createTestUser();
        $request = new UpdateProfileRequest(
            password: 'NewSecurePass123!'
        );

        // Act
        $response = $this->useCase->execute($userId, $request);

        // Assert
        $this->assertNotNull($response->user);
        // Verify password was hashed and saved
        $stmt = $this->pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $hash = $stmt->fetchColumn();
        $this->assertTrue(password_verify('NewSecurePass123!', $hash));
    }

    public function testUpdateCrewProfileOnly(): void
    {
        // Arrange
        $userId = $this->createTestUser('crew@example.com', 'crew', createCrewProfile: true);
        $request = new UpdateProfileRequest(
            crewProfile: [
                'displayName' => 'Updated Crew Name',
                'mobile' => '555-9999',
                'skill' => SkillLevel::ADVANCED->value,
                'socialPreference' => false,
                'membershipNumber' => '99999',
                'experience' => 'Updated experience'
            ]
        );

        // Act
        $response = $this->useCase->execute($userId, $request);

        // Assert
        $this->assertNotNull($response->crewProfile);
        $this->assertEquals('Updated Crew Name', $response->crewProfile->displayName);
        $this->assertEquals('555-9999', $response->crewProfile->mobile);
        $this->assertEquals(SkillLevel::ADVANCED->value, $response->crewProfile->skill);
        $this->assertEquals('99999', $response->crewProfile->membershipNumber);
        $this->assertEquals('Updated experience', $response->crewProfile->experience);
    }

    public function testUpdateBoatProfileOnly(): void
    {
        // Arrange
        $userId = $this->createTestUser('boat@example.com', 'boat_owner', createBoatProfile: true);
        $request = new UpdateProfileRequest(
            boatProfile: [
                'displayName' => 'Updated Boat Name',
                'ownerMobile' => '555-8888',
                'minBerths' => 3,
                'maxBerths' => 6,
                'assistanceRequired' => true,
                'socialPreference' => false
            ]
        );

        // Act
        $response = $this->useCase->execute($userId, $request);

        // Assert
        $this->assertNotNull($response->boatProfile);
        $this->assertEquals('Updated Boat Name', $response->boatProfile->displayName);
        $this->assertEquals('555-8888', $response->boatProfile->ownerMobile);
        $this->assertEquals(3, $response->boatProfile->minBerths);
        $this->assertEquals(6, $response->boatProfile->maxBerths);
        $this->assertTrue($response->boatProfile->assistanceRequired);
    }

    public function testUpdateUserAccountAndCrewProfile(): void
    {
        // Arrange
        $userId = $this->createTestUser('user@example.com', 'crew', createCrewProfile: true);
        $request = new UpdateProfileRequest(
            email: 'newemail@example.com',
            password: 'NewPass123!',
            crewProfile: [
                'displayName' => 'New Display Name',
                'mobile' => '555-7777'
            ]
        );

        // Act
        $response = $this->useCase->execute($userId, $request);

        // Assert
        $this->assertEquals('newemail@example.com', $response->user->email);
        $this->assertEquals('New Display Name', $response->crewProfile->displayName);
        $this->assertEquals('555-7777', $response->crewProfile->mobile);
    }

    public function testUpdateUserAccountAndBoatProfile(): void
    {
        // Arrange
        $userId = $this->createTestUser('boat@example.com', 'boat_owner', createBoatProfile: true);
        $request = new UpdateProfileRequest(
            email: 'newboat@example.com',
            password: 'NewPass123!',
            boatProfile: [
                'displayName' => 'New Boat Name',
                'minBerths' => 4
            ]
        );

        // Act
        $response = $this->useCase->execute($userId, $request);

        // Assert
        $this->assertEquals('newboat@example.com', $response->user->email);
        $this->assertEquals('New Boat Name', $response->boatProfile->displayName);
        $this->assertEquals(4, $response->boatProfile->minBerths);
    }

    public function testUpdateAllFieldsSimultaneously(): void
    {
        // Arrange: Create user with both profiles (flex user)
        $userId = $this->createTestUser('flex@example.com', 'crew', createCrewProfile: true);
        // Also add boat profile
        $this->createBoatProfileForUser($userId);

        $request = new UpdateProfileRequest(
            email: 'flexupdated@example.com',
            password: 'NewFlexPass123!',
            crewProfile: [
                'displayName' => 'Flex Crew',
                'skill' => SkillLevel::ADVANCED->value
            ],
            boatProfile: [
                'displayName' => 'Flex Boat',
                'maxBerths' => 5
            ]
        );

        // Act
        $response = $this->useCase->execute($userId, $request);

        // Assert
        $this->assertEquals('flexupdated@example.com', $response->user->email);
        $this->assertEquals('Flex Crew', $response->crewProfile->displayName);
        $this->assertEquals('Flex Boat', $response->boatProfile->displayName);
    }

    public function testUpdateEmailToSameEmail(): void
    {
        // Arrange
        $userId = $this->createTestUser('same@example.com');
        $request = new UpdateProfileRequest(
            email: 'same@example.com' // Same email
        );

        // Act
        $response = $this->useCase->execute($userId, $request);

        // Assert
        $this->assertEquals('same@example.com', $response->user->email);
    }

    // ==================== VALIDATION TESTS ====================

    public function testInvalidEmailFormatThrowsValidationException(): void
    {
        // Arrange
        $userId = $this->createTestUser();
        $request = new UpdateProfileRequest(
            email: 'invalid-email-format' // Missing @
        );

        // Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid email format');

        // Act
        $this->useCase->execute($userId, $request);
    }

    public function testPasswordTooShortThrowsValidationException(): void
    {
        // Arrange
        $userId = $this->createTestUser();
        $request = new UpdateProfileRequest(
            password: 'short' // Less than 8 characters
        );

        // Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Password must be at least 8 characters');

        // Act
        $this->useCase->execute($userId, $request);
    }

    public function testEmptyPasswordIsIgnored(): void
    {
        // Arrange
        $userId = $this->createTestUser();
        $originalHash = $this->pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
        $originalHash->execute([$userId]);
        $originalPasswordHash = $originalHash->fetchColumn();

        $request = new UpdateProfileRequest(
            email: 'newemail@example.com', // Need at least one valid update
            password: '' // Empty password should be ignored
        );

        // Act
        $response = $this->useCase->execute($userId, $request);

        // Assert - Email updated but password unchanged
        $this->assertEquals('newemail@example.com', $response->user->email);

        $stmt = $this->pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $newPasswordHash = $stmt->fetchColumn();

        // Password should remain unchanged
        $this->assertEquals($originalPasswordHash, $newPasswordHash);
    }

    public function testEmailAlreadyTakenThrowsValidationException(): void
    {
        // Arrange
        $user1Id = $this->createTestUser('user1@example.com');
        $user2Id = $this->createTestUser('user2@example.com');

        $request = new UpdateProfileRequest(
            email: 'user2@example.com' // Email already taken by user2
        );

        // Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Email already exists');

        // Act
        $this->useCase->execute($user1Id, $request);
    }

    public function testNoUpdatesProvidedThrowsValidationException(): void
    {
        // Arrange
        $userId = $this->createTestUser();
        $request = new UpdateProfileRequest(); // No fields provided

        // Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('No updates provided');

        // Act
        $this->useCase->execute($userId, $request);
    }

    public function testUpdateCrewProfileWhenNoneExistsThrowsValidationException(): void
    {
        // Arrange
        $userId = $this->createTestUser('nocrew@example.com', 'crew', createCrewProfile: false);
        $request = new UpdateProfileRequest(
            crewProfile: [
                'displayName' => 'Should Fail'
            ]
        );

        // Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('User does not have a crew profile');

        // Act
        $this->useCase->execute($userId, $request);
    }

    public function testUpdateBoatProfileWhenNoneExistsThrowsValidationException(): void
    {
        // Arrange
        $userId = $this->createTestUser('noboat@example.com', 'boat_owner', createBoatProfile: false);
        $request = new UpdateProfileRequest(
            boatProfile: [
                'displayName' => 'Should Fail'
            ]
        );

        // Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('User does not have a boat profile');

        // Act
        $this->useCase->execute($userId, $request);
    }

    // ==================== EDGE CASE TESTS ====================

    public function testUserNotFoundThrowsRuntimeException(): void
    {
        // Arrange
        $nonExistentUserId = 99999;
        $request = new UpdateProfileRequest(
            email: 'test@example.com'
        );

        // Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('User not found');

        // Act
        $this->useCase->execute($nonExistentUserId, $request);
    }

    public function testWeakPasswordThrowsWeakPasswordException(): void
    {
        // Arrange
        $userId = $this->createTestUser();

        // Configure password service to reject this password
        $this->passwordService = $this->createMock(PasswordServiceInterface::class);
        $this->passwordService->method('meetsRequirements')->willReturn(false);
        $this->passwordService->method('getRequirementsMessage')->willReturn(
            'Password must contain uppercase, lowercase, number, and special character'
        );

        // Recreate use case with new password service
        $this->useCase = new UpdateUserProfileUseCase(
            $this->userRepository,
            $this->crewRepository,
            $this->boatRepository,
            $this->passwordService,
            $this->getUserProfileUseCase
        );

        $request = new UpdateProfileRequest(
            password: 'weakpass' // Weak password
        );

        // Assert
        $this->expectException(WeakPasswordException::class);
        $this->expectExceptionMessage('uppercase, lowercase, number, and special character');

        // Act
        $this->useCase->execute($userId, $request);
    }

    public function testUpdateCrewProfileWithNullOptionalFields(): void
    {
        // Arrange
        $userId = $this->createTestUser('crew@example.com', 'crew', createCrewProfile: true);
        $request = new UpdateProfileRequest(
            crewProfile: [
                'displayName' => 'Updated Name',
                'mobile' => null, // Null optional field
                'experience' => null
            ]
        );

        // Act
        $response = $this->useCase->execute($userId, $request);

        // Assert
        $this->assertEquals('Updated Name', $response->crewProfile->displayName);
        $this->assertNotNull($response->crewProfile);
    }

    public function testUpdateCrewProfileWithDifferentSkillLevels(): void
    {
        // Arrange
        $userId = $this->createTestUser('crew@example.com', 'crew', createCrewProfile: true);

        // Test NOVICE
        $request1 = new UpdateProfileRequest(
            crewProfile: ['skill' => SkillLevel::NOVICE->value]
        );
        $response1 = $this->useCase->execute($userId, $request1);
        $this->assertEquals(SkillLevel::NOVICE->value, $response1->crewProfile->skill);

        // Test INTERMEDIATE
        $request2 = new UpdateProfileRequest(
            crewProfile: ['skill' => SkillLevel::INTERMEDIATE->value]
        );
        $response2 = $this->useCase->execute($userId, $request2);
        $this->assertEquals(SkillLevel::INTERMEDIATE->value, $response2->crewProfile->skill);

        // Test ADVANCED
        $request3 = new UpdateProfileRequest(
            crewProfile: ['skill' => SkillLevel::ADVANCED->value]
        );
        $response3 = $this->useCase->execute($userId, $request3);
        $this->assertEquals(SkillLevel::ADVANCED->value, $response3->crewProfile->skill);
    }

    public function testUpdateBoatProfileWithBerthsValidation(): void
    {
        // Arrange
        $userId = $this->createTestUser('boat@example.com', 'boat_owner', createBoatProfile: true);
        $request = new UpdateProfileRequest(
            boatProfile: [
                'minBerths' => 2,
                'maxBerths' => 8
            ]
        );

        // Act
        $response = $this->useCase->execute($userId, $request);

        // Assert
        $this->assertEquals(2, $response->boatProfile->minBerths);
        $this->assertEquals(8, $response->boatProfile->maxBerths);
    }

    public function testUpdateBoatProfileWithAssistanceRequiredToggle(): void
    {
        // Arrange
        $userId = $this->createTestUser('boat@example.com', 'boat_owner', createBoatProfile: true);

        // Toggle to true
        $request1 = new UpdateProfileRequest(
            boatProfile: ['assistanceRequired' => true]
        );
        $response1 = $this->useCase->execute($userId, $request1);
        $this->assertTrue($response1->boatProfile->assistanceRequired);

        // Toggle to false
        $request2 = new UpdateProfileRequest(
            boatProfile: ['assistanceRequired' => false]
        );
        $response2 = $this->useCase->execute($userId, $request2);
        $this->assertFalse($response2->boatProfile->assistanceRequired);
    }

    public function testUpdateCrewSocialPreference(): void
    {
        // Arrange
        $userId = $this->createTestUser('crew@example.com', 'crew', createCrewProfile: true);

        $request = new UpdateProfileRequest(
            crewProfile: ['socialPreference' => true]
        );

        // Act
        $response = $this->useCase->execute($userId, $request);

        // Assert
        $this->assertTrue($response->crewProfile->socialPreference);
    }

    public function testPartialCrewProfileUpdate(): void
    {
        // Arrange
        $userId = $this->createTestUser('crew@example.com', 'crew', createCrewProfile: true);
        $originalDisplayName = $this->getCrewDisplayName($userId);

        $request = new UpdateProfileRequest(
            crewProfile: [
                'mobile' => '555-1111' // Only update mobile
                // displayName not provided, should remain unchanged
            ]
        );

        // Act
        $response = $this->useCase->execute($userId, $request);

        // Assert
        $this->assertEquals('555-1111', $response->crewProfile->mobile);
        $this->assertEquals($originalDisplayName, $response->crewProfile->displayName);
    }

    public function testPartialBoatProfileUpdate(): void
    {
        // Arrange
        $userId = $this->createTestUser('boat@example.com', 'boat_owner', createBoatProfile: true);
        $originalDisplayName = $this->getBoatDisplayName($userId);

        $request = new UpdateProfileRequest(
            boatProfile: [
                'ownerMobile' => '555-2222' // Only update mobile
                // displayName not provided, should remain unchanged
            ]
        );

        // Act
        $response = $this->useCase->execute($userId, $request);

        // Assert
        $this->assertEquals('555-2222', $response->boatProfile->ownerMobile);
        $this->assertEquals($originalDisplayName, $response->boatProfile->displayName);
    }

    public function testPasswordHashingBehavior(): void
    {
        // Arrange
        $userId = $this->createTestUser();
        $plainPassword = 'MyNewPassword123!';
        $request = new UpdateProfileRequest(
            password: $plainPassword
        );

        // Act
        $this->useCase->execute($userId, $request);

        // Assert - Verify password was hashed
        $stmt = $this->pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $hash = $stmt->fetchColumn();

        // Verify raw password NOT stored
        $this->assertNotEquals($plainPassword, $hash);

        // Verify hash can be verified
        $this->assertTrue(password_verify($plainPassword, $hash));
    }
}
