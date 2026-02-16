<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Service;

use App\Domain\Service\RankingService;
use App\Domain\Entity\Boat;
use App\Domain\Entity\Crew;
use App\Domain\ValueObject\BoatKey;
use App\Domain\ValueObject\CrewKey;
use App\Domain\ValueObject\EventId;
use App\Domain\Enum\BoatRankDimension;
use App\Domain\Enum\CrewRankDimension;
use App\Domain\Enum\AvailabilityStatus;
use App\Domain\Enum\SkillLevel;
use PHPUnit\Framework\TestCase;

class RankingServiceTest extends TestCase
{
    private RankingService $service;

    protected function setUp(): void
    {
        $this->service = new RankingService();
    }

    private function createBoat(string $key): Boat
    {
        $boat = new Boat(
            key: BoatKey::fromString($key),
            displayName: 'Test Boat',
            ownerFirstName: 'John',
            ownerLastName: 'Doe',
            ownerMobile: '555-1234',
            minBerths: 1,
            maxBerths: 3,
            assistanceRequired: false,
            socialPreference: true
        );
        $boat->setOwnerEmail('john@example.com');
        return $boat;
    }

    private function createCrew(string $key): Crew
    {
        $crew = new Crew(
            key: CrewKey::fromString($key),
            displayName: 'Test Crew',
            firstName: 'John',
            lastName: 'Doe',
            partnerKey: null,
            mobile: '555-1234',
            socialPreference: true,
            membershipNumber: '12345',
            skill: SkillLevel::INTERMEDIATE,
            experience: '5 years'
        );
        $crew->setEmail('john@example.com');
        return $crew;
    }

    // Tests that boat with no absences receives absence rank of 0
    public function testUpdateBoatAbsenceRanksWithNoAbsences(): void
    {
        // Arrange
        $boat = $this->createBoat('sailaway');
        $boat->setHistory(EventId::fromString('Fri May 29'), 'Y');
        $boat->setHistory(EventId::fromString('Sat May 30'), 'Y');

        // Act
        $this->service->updateBoatAbsenceRanks(
            [$boat],
            ['Fri May 29', 'Sat May 30']
        );

        // Assert
        $this->assertEquals(0, $boat->getRank()->getDimension(BoatRankDimension::ABSENCE));
    }

    // Tests that boat absence rank equals the count of past event absences
    public function testUpdateBoatAbsenceRanksWithAbsences(): void
    {
        // Arrange
        $boat = $this->createBoat('sailaway');
        $boat->setHistory(EventId::fromString('Fri May 29'), 'Y');
        $boat->setHistory(EventId::fromString('Sat May 30'), '');
        $boat->setHistory(EventId::fromString('Sun May 31'), '');

        // Act
        $this->service->updateBoatAbsenceRanks(
            [$boat],
            ['Fri May 29', 'Sat May 30', 'Sun May 31']
        );

        // Assert
        $this->assertEquals(2, $boat->getRank()->getDimension(BoatRankDimension::ABSENCE));
    }

    // Tests absence ranking calculation across multiple boats with different histories
    public function testUpdateBoatAbsenceRanksWithMultipleBoats(): void
    {
        // Arrange
        $boat1 = $this->createBoat('sailaway');
        $boat1->setHistory(EventId::fromString('Fri May 29'), 'Y');

        $boat2 = $this->createBoat('seabreeze');
        $boat2->setHistory(EventId::fromString('Fri May 29'), '');

        // Act
        $this->service->updateBoatAbsenceRanks(
            [$boat1, $boat2],
            ['Fri May 29']
        );

        // Assert
        $this->assertEquals(0, $boat1->getRank()->getDimension(BoatRankDimension::ABSENCE));
        $this->assertEquals(1, $boat2->getRank()->getDimension(BoatRankDimension::ABSENCE));
    }

    // Tests that crew with no absences receives absence rank of 0
    public function testUpdateCrewAbsenceRanksWithNoAbsences(): void
    {
        // Arrange
        $crew = $this->createCrew('johndoe');
        $crew->setHistory(EventId::fromString('Fri May 29'), 'sailaway');
        $crew->setHistory(EventId::fromString('Sat May 30'), 'seabreeze');

        // Act
        $this->service->updateCrewAbsenceRanks(
            [$crew],
            ['Fri May 29', 'Sat May 30']
        );

        // Assert
        $this->assertEquals(0, $crew->getRank()->getDimension(CrewRankDimension::ABSENCE));
    }

    // Tests that crew absence rank equals the count of past event absences
    public function testUpdateCrewAbsenceRanksWithAbsences(): void
    {
        // Arrange
        $crew = $this->createCrew('johndoe');
        $crew->setHistory(EventId::fromString('Fri May 29'), 'sailaway');
        $crew->setHistory(EventId::fromString('Sat May 30'), '');
        $crew->setHistory(EventId::fromString('Sun May 31'), '');

        // Act
        $this->service->updateCrewAbsenceRanks(
            [$crew],
            ['Fri May 29', 'Sat May 30', 'Sun May 31']
        );

        // Assert
        $this->assertEquals(2, $crew->getRank()->getDimension(CrewRankDimension::ABSENCE));
    }

    // Tests absence ranking calculation across multiple crews with different histories
    public function testUpdateCrewAbsenceRanksWithMultipleCrews(): void
    {
        // Arrange
        $crew1 = $this->createCrew('johndoe');
        $crew1->setHistory(EventId::fromString('Fri May 29'), 'sailaway');

        $crew2 = $this->createCrew('janedoe');
        $crew2->setHistory(EventId::fromString('Fri May 29'), '');

        // Act
        $this->service->updateCrewAbsenceRanks(
            [$crew1, $crew2],
            ['Fri May 29']
        );

        // Assert
        $this->assertEquals(0, $crew1->getRank()->getDimension(CrewRankDimension::ABSENCE));
        $this->assertEquals(1, $crew2->getRank()->getDimension(CrewRankDimension::ABSENCE));
    }

    // Tests that crew with guaranteed availability receives commitment rank of 0
    public function testUpdateCrewCommitmentRanksWithGuaranteed(): void
    {
        // Arrange
        $crew = $this->createCrew('johndoe');
        $eventId = EventId::fromString('Fri May 29');
        $crew->setAvailability($eventId, AvailabilityStatus::GUARANTEED);

        // Act
        $this->service->updateCrewCommitmentRanks([$crew], $eventId);

        // Assert
        $this->assertEquals(0, $crew->getRank()->getDimension(CrewRankDimension::COMMITMENT));
    }

    // Tests that crew with available status receives commitment rank of 1
    public function testUpdateCrewCommitmentRanksWithAvailable(): void
    {
        // Arrange
        $crew = $this->createCrew('johndoe');
        $eventId = EventId::fromString('Fri May 29');
        $crew->setAvailability($eventId, AvailabilityStatus::AVAILABLE);

        // Act
        $this->service->updateCrewCommitmentRanks([$crew], $eventId);

        // Assert
        $this->assertEquals(1, $crew->getRank()->getDimension(CrewRankDimension::COMMITMENT));
    }

    // Tests that crew with withdrawn status receives commitment rank of 2
    public function testUpdateCrewCommitmentRanksWithWithdrawn(): void
    {
        // Arrange
        $crew = $this->createCrew('johndoe');
        $eventId = EventId::fromString('Fri May 29');
        $crew->setAvailability($eventId, AvailabilityStatus::WITHDRAWN);

        // Act
        $this->service->updateCrewCommitmentRanks([$crew], $eventId);

        // Assert
        $this->assertEquals(2, $crew->getRank()->getDimension(CrewRankDimension::COMMITMENT));
    }

    // Tests that crew with unavailable status receives commitment rank of 3
    public function testUpdateCrewCommitmentRanksWithUnavailable(): void
    {
        // Arrange
        $crew = $this->createCrew('johndoe');
        $eventId = EventId::fromString('Fri May 29');
        $crew->setAvailability($eventId, AvailabilityStatus::UNAVAILABLE);

        // Act
        $this->service->updateCrewCommitmentRanks([$crew], $eventId);

        // Assert
        $this->assertEquals(3, $crew->getRank()->getDimension(CrewRankDimension::COMMITMENT));
    }

    // Tests commitment ranking calculation across multiple crews with different availability
    public function testUpdateCrewCommitmentRanksWithMultipleCrews(): void
    {
        // Arrange
        $crew1 = $this->createCrew('johndoe');
        $crew2 = $this->createCrew('janedoe');
        $eventId = EventId::fromString('Fri May 29');

        $crew1->setAvailability($eventId, AvailabilityStatus::GUARANTEED);
        $crew2->setAvailability($eventId, AvailabilityStatus::UNAVAILABLE);

        // Act
        $this->service->updateCrewCommitmentRanks([$crew1, $crew2], $eventId);

        // Assert
        $this->assertEquals(0, $crew1->getRank()->getDimension(CrewRankDimension::COMMITMENT));
        $this->assertEquals(3, $crew2->getRank()->getDimension(CrewRankDimension::COMMITMENT));
    }

    // Tests that crew with membership number receives membership rank of 1
    public function testUpdateCrewMembershipRankWithMember(): void
    {
        // Arrange
        $crew = $this->createCrew('johndoe');
        $crew->setMembershipNumber('12345');

        // Act
        $this->service->updateCrewMembershipRank($crew);

        // Assert
        $this->assertEquals(1, $crew->getRank()->getDimension(CrewRankDimension::MEMBERSHIP));
    }

    // Tests that crew without membership number receives membership rank of 0
    public function testUpdateCrewMembershipRankWithoutMember(): void
    {
        // Arrange
        $crew = $this->createCrew('johndoe');
        $crew->setMembershipNumber(null);

        // Act
        $this->service->updateCrewMembershipRank($crew);

        // Assert
        $this->assertEquals(0, $crew->getRank()->getDimension(CrewRankDimension::MEMBERSHIP));
    }

    // Tests comprehensive boat ranking update including all rank dimensions
    public function testUpdateAllBoatRanks(): void
    {
        // Arrange
        $boat = $this->createBoat('sailaway');
        $boat->setHistory(EventId::fromString('Fri May 29'), '');

        // Act
        $this->service->updateAllBoatRanks([$boat], ['Fri May 29']);

        // Assert
        $this->assertEquals(1, $boat->getRank()->getDimension(BoatRankDimension::ABSENCE));
    }

    // Tests comprehensive crew ranking update including all rank dimensions
    public function testUpdateAllCrewRanks(): void
    {
        // Arrange
        $crew = $this->createCrew('johndoe');
        $eventId = EventId::fromString('Fri May 29');
        $crew->setHistory(EventId::fromString('Sat May 30'), '');
        $crew->setAvailability($eventId, AvailabilityStatus::AVAILABLE);

        // Act
        $this->service->updateAllCrewRanks([$crew], ['Sat May 30'], $eventId);

        // Assert
        $this->assertEquals(1, $crew->getRank()->getDimension(CrewRankDimension::ABSENCE));
        $this->assertEquals(1, $crew->getRank()->getDimension(CrewRankDimension::COMMITMENT));
    }

    // Tests that absence ranking handles empty past events list without errors
    public function testUpdateAbsenceRanksWithEmptyPastEvents(): void
    {
        // Arrange
        $boat = $this->createBoat('sailaway');
        $crew = $this->createCrew('johndoe');

        // Act
        $this->service->updateBoatAbsenceRanks([$boat], []);
        $this->service->updateCrewAbsenceRanks([$crew], []);

        // Assert
        $this->assertEquals(0, $boat->getRank()->getDimension(BoatRankDimension::ABSENCE));
        $this->assertEquals(0, $crew->getRank()->getDimension(CrewRankDimension::ABSENCE));
    }

    // Tests membership number validation with minimum valid length (4 digits)
    public function testUpdateCrewMembershipRankWithMinimumValidLength(): void
    {
        // Arrange
        $crew = $this->createCrew('johndoe');
        $crew->setMembershipNumber('1234');

        // Act
        $this->service->updateCrewMembershipRank($crew);

        // Assert - 4 digits is valid, should get rank 1
        $this->assertEquals(1, $crew->getRank()->getDimension(CrewRankDimension::MEMBERSHIP));
    }

    // Tests membership number validation with maximum valid length (9 digits)
    public function testUpdateCrewMembershipRankWithMaximumValidLength(): void
    {
        // Arrange
        $crew = $this->createCrew('johndoe');
        $crew->setMembershipNumber('123456789');

        // Act
        $this->service->updateCrewMembershipRank($crew);

        // Assert - 9 digits is valid, should get rank 1
        $this->assertEquals(1, $crew->getRank()->getDimension(CrewRankDimension::MEMBERSHIP));
    }

    // Tests membership number validation with too short length (< 4 digits)
    public function testUpdateCrewMembershipRankWithTooShortNumber(): void
    {
        // Arrange
        $crew = $this->createCrew('johndoe');
        $crew->setMembershipNumber('123');

        // Act
        $this->service->updateCrewMembershipRank($crew);

        // Assert - 3 digits is invalid, should get rank 0
        $this->assertEquals(0, $crew->getRank()->getDimension(CrewRankDimension::MEMBERSHIP));
    }

    // Tests membership number validation with too long length (> 9 digits)
    public function testUpdateCrewMembershipRankWithTooLongNumber(): void
    {
        // Arrange
        $crew = $this->createCrew('johndoe');
        $crew->setMembershipNumber('1234567890');

        // Act
        $this->service->updateCrewMembershipRank($crew);

        // Assert - 10 digits is invalid, should get rank 0
        $this->assertEquals(0, $crew->getRank()->getDimension(CrewRankDimension::MEMBERSHIP));
    }

    // Tests membership number validation with letters after cleaning
    public function testUpdateCrewMembershipRankWithLetters(): void
    {
        // Arrange
        $crew = $this->createCrew('johndoe');
        $crew->setMembershipNumber('12A45');

        // Act
        $this->service->updateCrewMembershipRank($crew);

        // Assert - contains letters, should get rank 0 (invalid)
        $this->assertEquals(0, $crew->getRank()->getDimension(CrewRankDimension::MEMBERSHIP));
    }

    // Tests membership number validation with all letters
    public function testUpdateCrewMembershipRankWithAllLetters(): void
    {
        // Arrange
        $crew = $this->createCrew('johndoe');
        $crew->setMembershipNumber('ABCD');

        // Act
        $this->service->updateCrewMembershipRank($crew);

        // Assert - all letters, should get rank 0 (invalid)
        $this->assertEquals(0, $crew->getRank()->getDimension(CrewRankDimension::MEMBERSHIP));
    }

    // Tests membership number validation with special characters that get cleaned
    public function testUpdateCrewMembershipRankWithDashes(): void
    {
        // Arrange
        $crew = $this->createCrew('johndoe');
        $crew->setMembershipNumber('12-34-56');

        // Act
        $this->service->updateCrewMembershipRank($crew);

        // Assert - after removing dashes → "123456" (6 digits), valid, should get rank 1
        $this->assertEquals(1, $crew->getRank()->getDimension(CrewRankDimension::MEMBERSHIP));
    }

    // Tests membership number validation with spaces that get cleaned
    public function testUpdateCrewMembershipRankWithSpaces(): void
    {
        // Arrange
        $crew = $this->createCrew('johndoe');
        $crew->setMembershipNumber('  12345  ');

        // Act
        $this->service->updateCrewMembershipRank($crew);

        // Assert - after removing spaces → "12345" (5 digits), valid, should get rank 1
        $this->assertEquals(1, $crew->getRank()->getDimension(CrewRankDimension::MEMBERSHIP));
    }

    // Tests membership number validation with only special characters
    public function testUpdateCrewMembershipRankWithOnlySpecialChars(): void
    {
        // Arrange
        $crew = $this->createCrew('johndoe');
        $crew->setMembershipNumber('----');

        // Act
        $this->service->updateCrewMembershipRank($crew);

        // Assert - after cleaning → "", empty, should get rank 0 (invalid)
        $this->assertEquals(0, $crew->getRank()->getDimension(CrewRankDimension::MEMBERSHIP));
    }

    // Tests membership number validation with empty string
    public function testUpdateCrewMembershipRankWithEmptyString(): void
    {
        // Arrange
        $crew = $this->createCrew('johndoe');
        $crew->setMembershipNumber('');

        // Act
        $this->service->updateCrewMembershipRank($crew);

        // Assert - empty string, should get rank 0 (invalid)
        $this->assertEquals(0, $crew->getRank()->getDimension(CrewRankDimension::MEMBERSHIP));
    }

    // Tests membership number validation with mixed alphanumeric that has letters
    public function testUpdateCrewMembershipRankWithMixedAlphanumeric(): void
    {
        // Arrange
        $crew = $this->createCrew('johndoe');
        $crew->setMembershipNumber('ABC-123');

        // Act
        $this->service->updateCrewMembershipRank($crew);

        // Assert - after removing dash → "ABC123", contains letters, should get rank 0 (invalid)
        $this->assertEquals(0, $crew->getRank()->getDimension(CrewRankDimension::MEMBERSHIP));
    }
}
