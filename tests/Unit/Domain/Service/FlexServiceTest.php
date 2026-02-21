<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Service;

use App\Domain\Service\FlexService;
use App\Domain\Entity\Boat;
use App\Domain\Entity\Crew;
use App\Domain\Collection\Fleet;
use App\Domain\Collection\Squad;
use App\Domain\ValueObject\BoatKey;
use App\Domain\ValueObject\CrewKey;
use App\Domain\Enum\BoatRankDimension;
use App\Domain\Enum\CrewRankDimension;
use App\Domain\Enum\SkillLevel;
use PHPUnit\Framework\TestCase;

class FlexServiceTest extends TestCase
{
    private FlexService $service;

    protected function setUp(): void
    {
        $this->service = new FlexService();
    }

    private function createBoat(string $key, string $ownerFirstName, string $ownerLastName): Boat
    {
        $boat = new Boat(
            key: BoatKey::fromString($key),
            displayName: 'Test Boat',
            ownerFirstName: $ownerFirstName,
            ownerLastName: $ownerLastName,
            ownerMobile: '555-1234',
            minBerths: 1,
            maxBerths: 3,
            assistanceRequired: false,
            socialPreference: true
        );
        $boat->setOwnerEmail('owner@example.com');
        return $boat;
    }

    private function createCrew(string $firstName, string $lastName): Crew
    {
        $crew = new Crew(
            key: CrewKey::fromName($firstName, $lastName),
            displayName: "$firstName $lastName",
            firstName: $firstName,
            lastName: $lastName,
            partnerKey: null,
            mobile: '555-1234',
            socialPreference: true,
            membershipNumber: '12345',
            skill: SkillLevel::INTERMEDIATE,
            experience: '5 years'
        );
        $crew->setEmail(strtolower($firstName) . '@example.com');
        return $crew;
    }

    // Tests that boat owner is identified as flex when rank_flexibility is 0
    public function testIsBoatOwnerFlexReturnsTrueWhenFlexibilityRankIsZero(): void
    {
        // Arrange
        $boat = $this->createBoat('sailaway', 'John', 'Doe');
        $boat->setRankDimension(BoatRankDimension::FLEXIBILITY, 0);

        // Act
        $result = $this->service->isBoatOwnerFlex($boat, new Squad());

        // Assert
        $this->assertTrue($result);
    }

    // Tests that boat owner is not flex when rank_flexibility is 1 (default)
    public function testIsBoatOwnerFlexReturnsFalseWhenFlexibilityRankIsOne(): void
    {
        // Arrange
        $boat = $this->createBoat('sailaway', 'John', 'Doe');
        // Default rank has flexibility=1

        // Act
        $result = $this->service->isBoatOwnerFlex($boat, new Squad());

        // Assert
        $this->assertFalse($result);
    }

    // Tests that crew is identified as flex when they own a boat in the fleet
    public function testIsCrewFlexReturnsTrueWhenCrewOwnsBoat(): void
    {
        // Arrange
        $crew = $this->createCrew('John', 'Doe');
        $boat = $this->createBoat('sailaway', 'John', 'Doe');

        $fleet = new Fleet();
        $fleet->add($boat);

        // Act
        $result = $this->service->isCrewFlex($crew, $fleet);

        // Assert
        $this->assertTrue($result);
    }

    // Tests that crew is not flex when they don't own any boat in the fleet
    public function testIsCrewFlexReturnsFalseWhenCrewDoesNotOwnBoat(): void
    {
        // Arrange
        $crew = $this->createCrew('Jane', 'Smith');
        $boat = $this->createBoat('sailaway', 'John', 'Doe');

        $fleet = new Fleet();
        $fleet->add($boat);

        // Act
        $result = $this->service->isCrewFlex($crew, $fleet);

        // Assert
        $this->assertFalse($result);
    }

    // Tests that crew is not flex when the fleet is empty
    public function testIsCrewFlexReturnsFalseWhenFleetIsEmpty(): void
    {
        // Arrange
        $crew = $this->createCrew('John', 'Doe');
        $fleet = new Fleet();

        // Act
        $result = $this->service->isCrewFlex($crew, $fleet);

        // Assert
        $this->assertFalse($result);
    }

    // Tests that boat flexibility rank stays 0 when rank_flexibility is already 0
    public function testUpdateBoatFlexRankSetsZeroWhenFlex(): void
    {
        // Arrange
        $boat = $this->createBoat('sailaway', 'John', 'Doe');
        $boat->setRankDimension(BoatRankDimension::FLEXIBILITY, 0);

        // Act
        $this->service->updateBoatFlexRank($boat, new Squad());

        // Assert
        $this->assertEquals(0, $boat->getRank()->getDimension(BoatRankDimension::FLEXIBILITY));
    }

    // Tests that boat flexibility rank stays 1 when rank_flexibility is already 1 (default)
    public function testUpdateBoatFlexRankSetsOneWhenNotFlex(): void
    {
        // Arrange
        $boat = $this->createBoat('sailaway', 'John', 'Doe');
        // Default rank has flexibility=1

        // Act
        $this->service->updateBoatFlexRank($boat, new Squad());

        // Assert
        $this->assertEquals(1, $boat->getRank()->getDimension(BoatRankDimension::FLEXIBILITY));
    }

    // Tests that crew flexibility rank is set to 0 when they own a boat in the fleet
    public function testUpdateCrewFlexRankSetsZeroWhenFlex(): void
    {
        // Arrange
        $crew = $this->createCrew('John', 'Doe');
        $boat = $this->createBoat('sailaway', 'John', 'Doe');

        $fleet = new Fleet();
        $fleet->add($boat);

        // Act
        $this->service->updateCrewFlexRank($crew, $fleet);

        // Assert
        $this->assertEquals(0, $crew->getRank()->getDimension(CrewRankDimension::FLEXIBILITY));
    }

    // Tests that crew flexibility rank is set to 1 when they don't own any boat in the fleet
    public function testUpdateCrewFlexRankSetsOneWhenNotFlex(): void
    {
        // Arrange
        $crew = $this->createCrew('Jane', 'Smith');
        $boat = $this->createBoat('sailaway', 'John', 'Doe');

        $fleet = new Fleet();
        $fleet->add($boat);

        // Act
        $this->service->updateCrewFlexRank($crew, $fleet);

        // Assert
        $this->assertEquals(1, $crew->getRank()->getDimension(CrewRankDimension::FLEXIBILITY));
    }

    // Tests batch updating of flexibility ranks for all boats in the fleet
    public function testUpdateAllBoatFlexRanks(): void
    {
        // Arrange
        $boat1 = $this->createBoat('sailaway', 'John', 'Doe');
        $boat2 = $this->createBoat('seabreeze', 'Jane', 'Smith');

        // boat1 willing to crew, boat2 not
        $boat1->setRankDimension(BoatRankDimension::FLEXIBILITY, 0);
        // boat2 keeps default flexibility=1

        $fleet = new Fleet();
        $fleet->add($boat1);
        $fleet->add($boat2);

        // Act
        $this->service->updateAllBoatFlexRanks($fleet, new Squad());

        // Assert
        $this->assertEquals(0, $boat1->getRank()->getDimension(BoatRankDimension::FLEXIBILITY));
        $this->assertEquals(1, $boat2->getRank()->getDimension(BoatRankDimension::FLEXIBILITY));
    }

    // Tests batch updating of flexibility ranks for all crews in the squad
    public function testUpdateAllCrewFlexRanks(): void
    {
        // Arrange
        $crew1 = $this->createCrew('John', 'Doe');
        $crew2 = $this->createCrew('Jane', 'Smith');

        $boat1 = $this->createBoat('sailaway', 'John', 'Doe');
        $boat2 = $this->createBoat('seabreeze', 'Bob', 'Jones');

        $squad = new Squad();
        $squad->add($crew1);
        $squad->add($crew2);

        $fleet = new Fleet();
        $fleet->add($boat1);
        $fleet->add($boat2);

        // Act
        $this->service->updateAllCrewFlexRanks($squad, $fleet);

        // Assert
        // crew1 owns boat1, so flex
        $this->assertEquals(0, $crew1->getRank()->getDimension(CrewRankDimension::FLEXIBILITY));
        // crew2 doesn't own any boat, so not flex
        $this->assertEquals(1, $crew2->getRank()->getDimension(CrewRankDimension::FLEXIBILITY));
    }

    // Tests updating flexibility ranks for both boats and crews simultaneously
    public function testUpdateAllFlexRanks(): void
    {
        // Arrange
        $boat = $this->createBoat('sailaway', 'John', 'Doe');
        $boat->setRankDimension(BoatRankDimension::FLEXIBILITY, 0); // owner willing to crew
        $crew = $this->createCrew('John', 'Doe');

        $fleet = new Fleet();
        $fleet->add($boat);

        $squad = new Squad();
        $squad->add($crew);

        // Act
        $this->service->updateAllFlexRanks($fleet, $squad);

        // Assert
        // Boat: retains flexibility=0 (willing to crew)
        $this->assertEquals(0, $boat->getRank()->getDimension(BoatRankDimension::FLEXIBILITY));
        // Crew: owns a boat in the fleet, so flex
        $this->assertEquals(0, $crew->getRank()->getDimension(CrewRankDimension::FLEXIBILITY));
    }

    // Tests flexibility calculation with multiple boats and crews in various scenarios
    public function testFlexWithMultipleBoatsAndCrews(): void
    {
        // Arrange
        // Boat 1: owner willing to crew (flex)
        // Boat 2: owner not willing to crew (not flex)
        // Crew 1: owns boat 1 (flex)
        // Crew 2 & 3: don't own any boat (not flex)

        $boat1 = $this->createBoat('sailaway', 'John', 'Doe');
        $boat2 = $this->createBoat('seabreeze', 'Unknown', 'Owner');

        $boat1->setRankDimension(BoatRankDimension::FLEXIBILITY, 0); // willing to crew
        // boat2 keeps default flexibility=1

        $crew1 = $this->createCrew('John', 'Doe');
        $crew2 = $this->createCrew('Jane', 'Smith');
        $crew3 = $this->createCrew('Bob', 'Jones');

        $fleet = new Fleet();
        $fleet->add($boat1);
        $fleet->add($boat2);

        $squad = new Squad();
        $squad->add($crew1);
        $squad->add($crew2);
        $squad->add($crew3);

        // Act
        $this->service->updateAllFlexRanks($fleet, $squad);

        // Assert
        // Boat 1: willing to crew (flex)
        $this->assertEquals(0, $boat1->getRank()->getDimension(BoatRankDimension::FLEXIBILITY));
        // Boat 2: not willing to crew (not flex)
        $this->assertEquals(1, $boat2->getRank()->getDimension(BoatRankDimension::FLEXIBILITY));

        // Crew 1: owns boat 1 (flex)
        $this->assertEquals(0, $crew1->getRank()->getDimension(CrewRankDimension::FLEXIBILITY));
        // Crew 2: doesn't own any boat (not flex)
        $this->assertEquals(1, $crew2->getRank()->getDimension(CrewRankDimension::FLEXIBILITY));
        // Crew 3: doesn't own any boat (not flex)
        $this->assertEquals(1, $crew3->getRank()->getDimension(CrewRankDimension::FLEXIBILITY));
    }
}
