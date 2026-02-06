<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCase\Auth;

use App\Application\DTO\Request\RegisterRequest;
use App\Application\Port\Repository\BoatRepositoryInterface;
use App\Application\Port\Repository\CrewRepositoryInterface;
use App\Application\Port\Repository\UserRepositoryInterface;
use App\Application\Port\Service\PasswordServiceInterface;
use App\Application\Port\Service\TokenServiceInterface;
use App\Application\UseCase\Auth\RegisterUseCase;
use App\Domain\Entity\Boat;
use App\Domain\Entity\Crew;
use App\Domain\Service\RankingService;
use App\Domain\ValueObject\Rank;
use App\Infrastructure\Persistence\SQLite\Connection;
use PHPUnit\Framework\TestCase;

class RegisterUseCaseTest extends TestCase
{
    private UserRepositoryInterface $userRepository;
    private CrewRepositoryInterface $crewRepository;
    private BoatRepositoryInterface $boatRepository;
    private PasswordServiceInterface $passwordService;
    private TokenServiceInterface $tokenService;
    private RankingService $rankingService;
    private RegisterUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mocks for all dependencies
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->crewRepository = $this->createMock(CrewRepositoryInterface::class);
        $this->boatRepository = $this->createMock(BoatRepositoryInterface::class);
        $this->passwordService = $this->createMock(PasswordServiceInterface::class);
        $this->tokenService = $this->createMock(TokenServiceInterface::class);
        $this->rankingService = $this->createMock(RankingService::class);

        // Setup common mock behaviors
        $this->userRepository->method('emailExists')->willReturn(false);
        $this->passwordService->method('meetsRequirements')->willReturn(true);
        $this->passwordService->method('hash')->willReturn('$2y$10$hashedpassword');
        $this->tokenService->method('generate')->willReturn('mock.jwt.token');
        $this->tokenService->method('getExpirationMinutes')->willReturn(60);

        // Mock user save to set ID
        $this->userRepository->method('save')->willReturnCallback(function ($user) {
            // Set user ID after save
            $reflection = new \ReflectionClass($user);
            $property = $reflection->getProperty('id');
            $property->setAccessible(true);
            $property->setValue($user, 1);
        });

        // Setup ranking service to return valid ranks
        $this->rankingService->method('calculateCrewRank')->willReturn(
            Rank::forCrew(commitment: 0, flexibility: 1, membership: 0, absence: 0)
        );
        $this->rankingService->method('calculateBoatRank')->willReturn(
            Rank::forBoat(flexibility: 1, absence: 0)
        );

        $this->useCase = new RegisterUseCase(
            $this->userRepository,
            $this->crewRepository,
            $this->boatRepository,
            $this->passwordService,
            $this->tokenService,
            $this->rankingService
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Rollback any active transactions to clean up for next test
        try {
            Connection::rollBack();
        } catch (\Exception $e) {
            // Ignore if no transaction is active
        }
    }

    public function testCrewDisplayNameGeneratedWhenNull(): void
    {
        // Arrange
        $this->crewRepository->method('findByKey')->willReturn(null);

        $capturedCrew = null;
        $this->crewRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Crew $crew) use (&$capturedCrew) {
                $capturedCrew = $crew;
                return true;
            }));

        $request = new RegisterRequest(
            email: 'john.smith@example.com',
            password: 'SecurePass123!',
            accountType: 'crew',
            profile: [
                'firstName' => 'John',
                'lastName' => 'Smith',
                // No displayName provided
            ]
        );

        // Act
        $this->useCase->execute($request);

        // Assert
        $this->assertNotNull($capturedCrew);
        $this->assertEquals('JohnS', $capturedCrew->getDisplayName());
    }

    public function testCrewDisplayNameGeneratedWhenEmptyString(): void
    {
        // Arrange
        $this->crewRepository->method('findByKey')->willReturn(null);

        $capturedCrew = null;
        $this->crewRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Crew $crew) use (&$capturedCrew) {
                $capturedCrew = $crew;
                return true;
            }));

        $request = new RegisterRequest(
            email: 'jane.doe@example.com',
            password: 'SecurePass123!',
            accountType: 'crew',
            profile: [
                'firstName' => 'Jane',
                'lastName' => 'Doe',
                'displayName' => '',  // Empty string
            ]
        );

        // Act
        $this->useCase->execute($request);

        // Assert
        $this->assertNotNull($capturedCrew);
        $this->assertEquals('JaneD', $capturedCrew->getDisplayName());
    }

    public function testCrewDisplayNamePreservedWhenProvided(): void
    {
        // Arrange
        $this->crewRepository->method('findByKey')->willReturn(null);

        $capturedCrew = null;
        $this->crewRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Crew $crew) use (&$capturedCrew) {
                $capturedCrew = $crew;
                return true;
            }));

        $request = new RegisterRequest(
            email: 'custom.user@example.com',
            password: 'SecurePass123!',
            accountType: 'crew',
            profile: [
                'firstName' => 'Sarah',
                'lastName' => 'Johnson',
                'displayName' => 'Skipper Sarah',  // Custom displayName
            ]
        );

        // Act
        $this->useCase->execute($request);

        // Assert
        $this->assertNotNull($capturedCrew);
        $this->assertEquals('Skipper Sarah', $capturedCrew->getDisplayName());
    }

    public function testCrewDisplayNameHandlesUnicodeCharacters(): void
    {
        // Arrange
        $this->crewRepository->method('findByKey')->willReturn(null);

        $capturedCrew = null;
        $this->crewRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Crew $crew) use (&$capturedCrew) {
                $capturedCrew = $crew;
                return true;
            }));

        $request = new RegisterRequest(
            email: 'li.ming@example.com',
            password: 'SecurePass123!',
            accountType: 'crew',
            profile: [
                'firstName' => '李',
                'lastName' => '明',
                // No displayName provided
            ]
        );

        // Act
        $this->useCase->execute($request);

        // Assert
        $this->assertNotNull($capturedCrew);
        $this->assertEquals('李明', $capturedCrew->getDisplayName());
    }

    public function testBoatDisplayNameGeneratedWhenNull(): void
    {
        // Arrange
        $this->boatRepository->method('findByKey')->willReturn(null);

        $capturedBoat = null;
        $this->boatRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Boat $boat) use (&$capturedBoat) {
                $capturedBoat = $boat;
                return true;
            }));

        $request = new RegisterRequest(
            email: 'bob.boat@example.com',
            password: 'SecurePass123!',
            accountType: 'boat_owner',
            profile: [
                'ownerFirstName' => 'Bob',
                'ownerLastName' => 'Johnson',
                'ownerMobile' => '555-1234',
                'minBerths' => 2,
                'maxBerths' => 4,
                // No displayName provided
            ]
        );

        // Act
        $this->useCase->execute($request);

        // Assert
        $this->assertNotNull($capturedBoat);
        $this->assertEquals('BobJ', $capturedBoat->getDisplayName());
    }

    public function testBoatDisplayNameGeneratedWhenEmptyString(): void
    {
        // Arrange
        $this->boatRepository->method('findByKey')->willReturn(null);

        $capturedBoat = null;
        $this->boatRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Boat $boat) use (&$capturedBoat) {
                $capturedBoat = $boat;
                return true;
            }));

        $request = new RegisterRequest(
            email: 'alice.boat@example.com',
            password: 'SecurePass123!',
            accountType: 'boat_owner',
            profile: [
                'ownerFirstName' => 'Alice',
                'ownerLastName' => 'Williams',
                'ownerMobile' => '555-5678',
                'minBerths' => 3,
                'maxBerths' => 6,
                'displayName' => '   ',  // Whitespace only
            ]
        );

        // Act
        $this->useCase->execute($request);

        // Assert
        $this->assertNotNull($capturedBoat);
        $this->assertEquals('AliceW', $capturedBoat->getDisplayName());
    }

    public function testBoatDisplayNamePreservedWhenProvided(): void
    {
        // Arrange
        $this->boatRepository->method('findByKey')->willReturn(null);

        $capturedBoat = null;
        $this->boatRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Boat $boat) use (&$capturedBoat) {
                $capturedBoat = $boat;
                return true;
            }));

        $request = new RegisterRequest(
            email: 'custom.boat@example.com',
            password: 'SecurePass123!',
            accountType: 'boat_owner',
            profile: [
                'ownerFirstName' => 'Mike',
                'ownerLastName' => 'Davis',
                'ownerMobile' => '555-9012',
                'minBerths' => 2,
                'maxBerths' => 5,
                'displayName' => 'Sea Breeze',  // Custom boat name
            ]
        );

        // Act
        $this->useCase->execute($request);

        // Assert
        $this->assertNotNull($capturedBoat);
        $this->assertEquals('Sea Breeze', $capturedBoat->getDisplayName());
    }

    public function testCrewDisplayNameHandlesWhitespaceInFirstName(): void
    {
        // Arrange
        $this->crewRepository->method('findByKey')->willReturn(null);

        $capturedCrew = null;
        $this->crewRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Crew $crew) use (&$capturedCrew) {
                $capturedCrew = $crew;
                return true;
            }));

        $request = new RegisterRequest(
            email: 'whitespace@example.com',
            password: 'SecurePass123!',
            accountType: 'crew',
            profile: [
                'firstName' => '  Mary  ',  // Whitespace around name
                'lastName' => 'Brown',
            ]
        );

        // Act
        $this->useCase->execute($request);

        // Assert
        $this->assertNotNull($capturedCrew);
        $this->assertEquals('MaryB', $capturedCrew->getDisplayName());
    }

    public function testBoatDisplayNameUsedForBoatKey(): void
    {
        // Arrange
        $this->boatRepository->method('findByKey')->willReturn(null);

        $capturedBoat = null;
        $this->boatRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Boat $boat) use (&$capturedBoat) {
                $capturedBoat = $boat;
                return true;
            }));

        $request = new RegisterRequest(
            email: 'boat.key@example.com',
            password: 'SecurePass123!',
            accountType: 'boat_owner',
            profile: [
                'ownerFirstName' => 'Chris',
                'ownerLastName' => 'Taylor',
                'ownerMobile' => '555-3456',
                'minBerths' => 2,
                'maxBerths' => 4,
            ]
        );

        // Act
        $this->useCase->execute($request);

        // Assert
        $this->assertNotNull($capturedBoat);
        $this->assertEquals('ChrisT', $capturedBoat->getDisplayName());
        // Verify the boat key is also based on the displayName (normalized: lowercase, spaces removed)
        $this->assertEquals('christ', $capturedBoat->getKey()->toString());
    }
}
