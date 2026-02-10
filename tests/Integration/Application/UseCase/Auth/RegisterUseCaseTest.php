<?php

declare(strict_types=1);

namespace Tests\Integration\Application\UseCase\Auth;

use App\Application\UseCase\Auth\RegisterUseCase;
use App\Application\DTO\Request\RegisterRequest;
use App\Application\Exception\UserAlreadyExistsException;
use App\Application\Exception\ValidationException;
use App\Application\Exception\WeakPasswordException;
use App\Application\Port\Service\EmailServiceInterface;
use App\Infrastructure\Persistence\SQLite\UserRepository;
use App\Infrastructure\Persistence\SQLite\CrewRepository;
use App\Infrastructure\Persistence\SQLite\BoatRepository;
use App\Infrastructure\Service\PhpPasswordService;
use App\Infrastructure\Service\JwtTokenService;
use App\Domain\Entity\User;
use App\Domain\Enum\SkillLevel;
use Tests\Integration\IntegrationTestCase;

/**
 * Integration tests for RegisterUseCase
 *
 * Tests user registration for both crew and boat_owner accounts.
 */
class RegisterUseCaseTest extends IntegrationTestCase
{
    private RegisterUseCase $useCase;
    private UserRepository $userRepository;
    private CrewRepository $crewRepository;
    private BoatRepository $boatRepository;
    private PhpPasswordService $passwordService;
    private EmailServiceInterface $emailService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = new UserRepository();
        $this->crewRepository = new CrewRepository();
        $this->boatRepository = new BoatRepository();
        $this->passwordService = new PhpPasswordService();
        $tokenService = new JwtTokenService();

        // We need RankingService for the usecase
        $rankingService = new \App\Domain\Service\RankingService();

        // Mock EmailService to avoid sending real emails during tests
        $this->emailService = $this->createMock(EmailServiceInterface::class);
        $this->emailService->method('send')->willReturn(true);

        // Mock config array
        $config = [
            'email' => [
                'admin_notification_email' => 'test-admin@example.com',
            ],
        ];

        $this->useCase = new RegisterUseCase(
            $this->userRepository,
            $this->crewRepository,
            $this->boatRepository,
            $this->passwordService,
            $tokenService,
            $rankingService,
            $this->emailService,
            $config
        );
    }

    public function testRegisterCrewWithValidDataReturnsAuthResponse(): void
    {
        $request = RegisterRequest::fromArray([
            'email' => 'newcrew@example.com',
            'password' => 'SecurePassword123',
            'accountType' => 'crew',
            'profile' => [
                'firstName' => 'John',
                'lastName' => 'Sailor',
                'skill' => SkillLevel::INTERMEDIATE->value,
            ]
        ]);

        $response = $this->useCase->execute($request);

        $this->assertNotNull($response->token);
        $this->assertNotEmpty($response->token);
        $this->assertEquals('newcrew@example.com', $response->user->email);
        $this->assertEquals('crew', $response->user->accountType);
        $this->assertFalse($response->user->isAdmin);
    }

    public function testRegisterBoatOwnerWithValidDataReturnsAuthResponse(): void
    {
        $request = RegisterRequest::fromArray([
            'email' => 'newboat@example.com',
            'password' => 'SecurePassword123',
            'accountType' => 'boat_owner',
            'profile' => [
                'ownerFirstName' => 'Mary',
                'ownerLastName' => 'Captain',
                'minBerths' => 4,
                'maxBerths' => 8,
                'ownerMobile' => '555-0000',
            ]
        ]);

        $response = $this->useCase->execute($request);

        $this->assertNotNull($response->token);
        $this->assertEquals('newboat@example.com', $response->user->email);
        $this->assertEquals('boat_owner', $response->user->accountType);
    }

    public function testRegisterCrewCreatesCrewProfile(): void
    {
        $request = RegisterRequest::fromArray([
            'email' => 'crew.profile@example.com',
            'password' => 'SecurePassword123',
            'accountType' => 'crew',
            'profile' => [
                'firstName' => 'Alice',
                'lastName' => 'Wonder',
                'skill' => SkillLevel::ADVANCED->value,
                'mobile' => '555-1234',
            ]
        ]);

        $this->useCase->execute($request);

        // Verify crew was created
        $crew = $this->crewRepository->findByName('Alice', 'Wonder');
        $this->assertNotNull($crew);
        $this->assertEquals('Alice', $crew->getFirstName());
        $this->assertEquals('Wonder', $crew->getLastName());
        $this->assertEquals(SkillLevel::ADVANCED, $crew->getSkill());
        $this->assertEquals('555-1234', $crew->getMobile());
    }

    public function testRegisterBoatOwnerCreatesBoatProfile(): void
    {
        $request = RegisterRequest::fromArray([
            'email' => 'boat.profile@example.com',
            'password' => 'SecurePassword123',
            'accountType' => 'boat_owner',
            'profile' => [
                'ownerFirstName' => 'Tom',
                'ownerLastName' => 'Ships',
                'minBerths' => 2,
                'maxBerths' => 6,
                'ownerMobile' => '555-5678',
            ]
        ]);

        $this->useCase->execute($request);

        // Verify boat was created
        $boat = $this->boatRepository->findByOwnerName('Tom', 'Ships');
        $this->assertNotNull($boat);
        $this->assertEquals('Tom', $boat->getOwnerFirstName());
        $this->assertEquals('Ships', $boat->getOwnerLastName());
        $this->assertEquals(2, $boat->getMinBerths());
        $this->assertEquals(6, $boat->getMaxBerths());
        $this->assertEquals('555-5678', $boat->getOwnerMobile());
    }

    public function testRegisterWithDuplicateEmailThrowsException(): void
    {
        // Create first user
        $user = new User(
            email: 'duplicate@example.com',
            passwordHash: $this->passwordService->hash('Password123'),
            accountType: 'crew',
            isAdmin: false
        );
        $this->userRepository->save($user);

        // Attempt to register with same email
        $request = RegisterRequest::fromArray([
            'email' => 'duplicate@example.com',
            'password' => 'SecurePassword123',
            'accountType' => 'crew',
            'profile' => [
                'firstName' => 'John',
                'lastName' => 'Sailor',
            ]
        ]);

        $this->expectException(UserAlreadyExistsException::class);

        $this->useCase->execute($request);
    }

    public function testRegisterWithWeakPasswordThrowsException(): void
    {
        $request = RegisterRequest::fromArray([
            'email' => 'weak@example.com',
            'password' => 'short',  // Too short
            'accountType' => 'crew',
            'profile' => [
                'firstName' => 'John',
                'lastName' => 'Sailor',
            ]
        ]);

        $this->expectException(ValidationException::class);

        $this->useCase->execute($request);
    }

    public function testRegisterWithMissingEmailThrowsValidationException(): void
    {
        $request = RegisterRequest::fromArray([
            'email' => '',
            'password' => 'SecurePassword123',
            'accountType' => 'crew',
            'profile' => [
                'firstName' => 'John',
                'lastName' => 'Sailor',
            ]
        ]);

        $this->expectException(ValidationException::class);

        $this->useCase->execute($request);
    }

    public function testRegisterWithInvalidAccountTypeThrowsValidationException(): void
    {
        $request = RegisterRequest::fromArray([
            'email' => 'invalid@example.com',
            'password' => 'SecurePassword123',
            'accountType' => 'invalid_type',
            'profile' => [
                'firstName' => 'John',
                'lastName' => 'Sailor',
            ]
        ]);

        $this->expectException(ValidationException::class);

        $this->useCase->execute($request);
    }

    public function testRegisterCrewWithMissingFirstNameThrowsValidationException(): void
    {
        $request = RegisterRequest::fromArray([
            'email' => 'missing@example.com',
            'password' => 'SecurePassword123',
            'accountType' => 'crew',
            'profile' => [
                'firstName' => '',
                'lastName' => 'Sailor',
            ]
        ]);

        $this->expectException(ValidationException::class);

        $this->useCase->execute($request);
    }

    public function testRegisterBoatWithMissingOwnerNameThrowsValidationException(): void
    {
        $request = RegisterRequest::fromArray([
            'email' => 'missingowner@example.com',
            'password' => 'SecurePassword123',
            'accountType' => 'boat_owner',
            'profile' => [
                'ownerFirstName' => '',
                'ownerLastName' => 'Captain',
                'minBerths' => 4,
                'maxBerths' => 8,
                'ownerMobile' => '555-0003',
            ]
        ]);

        $this->expectException(ValidationException::class);

        $this->useCase->execute($request);
    }

    public function testRegisterBoatWithInvalidBerthsThrowsValidationException(): void
    {
        $request = RegisterRequest::fromArray([
            'email' => 'invalidberths@example.com',
            'password' => 'SecurePassword123',
            'accountType' => 'boat_owner',
            'profile' => [
                'ownerFirstName' => 'Tom',
                'ownerLastName' => 'Ships',
                'minBerths' => 8,
                'maxBerths' => 4,  // Max less than min
                'ownerMobile' => '555-0004',
            ]
        ]);

        $this->expectException(ValidationException::class);

        $this->useCase->execute($request);
    }

    public function testRegisteredUserCanLogin(): void
    {
        // Register
        $registerRequest = RegisterRequest::fromArray([
            'email' => 'canlogin@example.com',
            'password' => 'SecurePassword123',
            'accountType' => 'crew',
            'profile' => [
                'firstName' => 'Login',
                'lastName' => 'Test',
            ]
        ]);

        $this->useCase->execute($registerRequest);

        // Verify user was created and can be found
        $user = $this->userRepository->findByEmail('canlogin@example.com');
        $this->assertNotNull($user);
        $this->assertTrue($this->passwordService->verify('SecurePassword123', $user->getPasswordHash()));
    }

    public function testRegisterCrewWithAllOptionalFields(): void
    {
        $request = RegisterRequest::fromArray([
            'email' => 'completecrew@example.com',
            'password' => 'SecurePassword123',
            'accountType' => 'crew',
            'profile' => [
                'firstName' => 'Complete',
                'lastName' => 'Sailor',
                'displayName' => 'Complete the Sailor',
                'skill' => SkillLevel::ADVANCED->value,
                'mobile' => '555-9999',
                'membership Number' => 'MEM-12345',
                'socialPreference' => true,
                'experience' => '10 years sailing',
            ]
        ]);

        $response = $this->useCase->execute($request);

        $this->assertNotNull($response->token);
        $crew = $this->crewRepository->findByName('Complete', 'Sailor');
        $this->assertNotNull($crew);
        $this->assertEquals('555-9999', $crew->getMobile());
        $this->assertTrue($crew->hasSocialPreference());
    }

    public function testRegisterBoatWithAllOptionalFields(): void
    {
        $request = RegisterRequest::fromArray([
            'email' => 'completeboat@example.com',
            'password' => 'SecurePassword123',
            'accountType' => 'boat_owner',
            'profile' => [
                'ownerFirstName' => 'Complete',
                'ownerLastName' => 'Owner',
                'displayName' => 'Complete\'s Boat',
                'minBerths' => 2,
                'maxBerths' => 8,
                'ownerMobile' => '555-8888',
                'assistanceRequired' => true,
                'socialPreference' => false,
            ]
        ]);

        $response = $this->useCase->execute($request);

        $this->assertNotNull($response->token);
        $boat = $this->boatRepository->findByOwnerName('Complete', 'Owner');
        $this->assertNotNull($boat);
        $this->assertEquals('555-8888', $boat->getOwnerMobile());
        $this->assertTrue($boat->requiresAssistance());
    }

    public function testRegisterCrewWithDuplicateNameThrowsValidationException(): void
    {
        // Register first crew
        $request1 = RegisterRequest::fromArray([
            'email' => 'first@example.com',
            'password' => 'SecurePassword123',
            'accountType' => 'crew',
            'profile' => [
                'firstName' => 'Duplicate',
                'lastName' => 'Name',
            ]
        ]);
        $this->useCase->execute($request1);

        // Try to register second crew with same name
        $request2 = RegisterRequest::fromArray([
            'email' => 'second@example.com',
            'password' => 'SecurePassword123',
            'accountType' => 'crew',
            'profile' => [
                'firstName' => 'Duplicate',
                'lastName' => 'Name',
            ]
        ]);

        $this->expectException(ValidationException::class);

        $this->useCase->execute($request2);
    }

    public function testRegisterBoatWithDuplicateNameThrowsValidationException(): void
    {
        // Register first boat
        $request1 = RegisterRequest::fromArray([
            'email' => 'boat1@example.com',
            'password' => 'SecurePassword123',
            'accountType' => 'boat_owner',
            'profile' => [
                'ownerFirstName' => 'Duplicate',
                'ownerLastName' => 'Owner',
                'minBerths' => 2,
                'maxBerths' => 6,
                'ownerMobile' => '555-0001',
            ]
        ]);
        $this->useCase->execute($request1);

        // Try to register second boat with same owner (same display name)
        $request2 = RegisterRequest::fromArray([
            'email' => 'boat2@example.com',
            'password' => 'SecurePassword123',
            'accountType' => 'boat_owner',
            'profile' => [
                'ownerFirstName' => 'Duplicate',
                'ownerLastName' => 'Owner',
                'minBerths' => 4,
                'maxBerths' => 8,
                'ownerMobile' => '555-0002',
            ]
        ]);

        $this->expectException(ValidationException::class);

        $this->useCase->execute($request2);
    }
}
