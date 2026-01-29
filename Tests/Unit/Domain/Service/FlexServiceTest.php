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
        return new Boat(
            key: BoatKey::fromString($key),
            displayName: 'Test Boat',
            ownerFirstName: $ownerFirstName,
            ownerLastName: $ownerLastName,
            ownerEmail: 'owner@example.com',
            ownerMobile: '555-1234',
            minBerths: 1,
            maxBerths: 3,
            assistanceRequired: false,
            socialPreference: true
        );
    }

    private function createCrew(string $firstName, string $lastName): Crew
    {
        return new Crew(
            key: CrewKey::fromName($firstName, $lastName),
            displayName: "$firstName $lastName",
            firstName: $firstName,
            lastName: $lastName,
            partnerKey: null,
            email: strtolower($firstName) . '@example.com',
            mobile: '555-1234',
            socialPreference: true,
            membershipNumber: '12345',
            skill: SkillLevel::INTERMEDIATE,
            experience: '5 years'
        );
    }

    // Tests that boat owner is identified as flex when they are also in the crew squad
    public function testIsBoatOwnerFlexReturnsTrueWhenOwnerIsCrew(): void
    {
        $boat = $this->createBoat('sailaway', 'John', 'Doe');
        $crew = $this->createCrew('John', 'Doe');

        $squad = new Squad();
        $squad->add($crew);

        $result = $this->service->isBoatOwnerFlex($boat, $squad);

        $this->assertTrue($result);
    }

    // Tests that boat owner is not flex when they are not in the crew squad
    public function testIsBoatOwnerFlexReturnsFalseWhenOwnerIsNotCrew(): void
    {
        $boat = $this->createBoat('sailaway', 'John', 'Doe');
        $crew = $this->createCrew('Jane', 'Smith');

        $squad = new Squad();
        $squad->add($crew);

        $result = $this->service->isBoatOwnerFlex($boat, $squad);

        $this->assertFalse($result);
    }

    // Tests that boat owner is not flex when the crew squad is empty
    public function testIsBoatOwnerFlexReturnsFalseWhenSquadIsEmpty(): void
    {
        $boat = $this->createBoat('sailaway', 'John', 'Doe');
        $squad = new Squad();

        $result = $this->service->isBoatOwnerFlex($boat, $squad);

        $this->assertFalse($result);
    }

    // Tests that crew is identified as flex when they own a boat in the fleet
    public function testIsCrewFlexReturnsTrueWhenCrewOwnsBoat(): void
    {
        $crew = $this->createCrew('John', 'Doe');
        $boat = $this->createBoat('sailaway', 'John', 'Doe');

        $fleet = new Fleet();
        $fleet->add($boat);

        $result = $this->service->isCrewFlex($crew, $fleet);

        $this->assertTrue($result);
    }

    // Tests that crew is not flex when they don't own any boat in the fleet
    public function testIsCrewFlexReturnsFalseWhenCrewDoesNotOwnBoat(): void
    {
        $crew = $this->createCrew('Jane', 'Smith');
        $boat = $this->createBoat('sailaway', 'John', 'Doe');

        $fleet = new Fleet();
        $fleet->add($boat);

        $result = $this->service->isCrewFlex($crew, $fleet);

        $this->assertFalse($result);
    }

    // Tests that crew is not flex when the fleet is empty
    public function testIsCrewFlexReturnsFalseWhenFleetIsEmpty(): void
    {
        $crew = $this->createCrew('John', 'Doe');
        $fleet = new Fleet();

        $result = $this->service->isCrewFlex($crew, $fleet);

        $this->assertFalse($result);
    }

    // Tests that boat flexibility rank is set to 0 when owner is in the crew squad
    public function testUpdateBoatFlexRankSetsZeroWhenFlex(): void
    {
        $boat = $this->createBoat('sailaway', 'John', 'Doe');
        $crew = $this->createCrew('John', 'Doe');

        $squad = new Squad();
        $squad->add($crew);

        $this->service->updateBoatFlexRank($boat, $squad);

        $this->assertEquals(0, $boat->getRank()->getDimension(BoatRankDimension::FLEXIBILITY));
    }

    // Tests that boat flexibility rank is set to 1 when owner is not in the crew squad
    public function testUpdateBoatFlexRankSetsOneWhenNotFlex(): void
    {
        $boat = $this->createBoat('sailaway', 'John', 'Doe');
        $crew = $this->createCrew('Jane', 'Smith');

        $squad = new Squad();
        $squad->add($crew);

        $this->service->updateBoatFlexRank($boat, $squad);

        $this->assertEquals(1, $boat->getRank()->getDimension(BoatRankDimension::FLEXIBILITY));
    }

    // Tests that crew flexibility rank is set to 0 when they own a boat in the fleet
    public function testUpdateCrewFlexRankSetsZeroWhenFlex(): void
    {
        $crew = $this->createCrew('John', 'Doe');
        $boat = $this->createBoat('sailaway', 'John', 'Doe');

        $fleet = new Fleet();
        $fleet->add($boat);

        $this->service->updateCrewFlexRank($crew, $fleet);

        $this->assertEquals(0, $crew->getRank()->getDimension(CrewRankDimension::FLEXIBILITY));
    }

    // Tests that crew flexibility rank is set to 1 when they don't own any boat in the fleet
    public function testUpdateCrewFlexRankSetsOneWhenNotFlex(): void
    {
        $crew = $this->createCrew('Jane', 'Smith');
        $boat = $this->createBoat('sailaway', 'John', 'Doe');

        $fleet = new Fleet();
        $fleet->add($boat);

        $this->service->updateCrewFlexRank($crew, $fleet);

        $this->assertEquals(1, $crew->getRank()->getDimension(CrewRankDimension::FLEXIBILITY));
    }

    // Tests batch updating of flexibility ranks for all boats in the fleet
    public function testUpdateAllBoatFlexRanks(): void
    {
        $boat1 = $this->createBoat('sailaway', 'John', 'Doe');
        $boat2 = $this->createBoat('seabreeze', 'Jane', 'Smith');

        $crew1 = $this->createCrew('John', 'Doe');
        $crew2 = $this->createCrew('Bob', 'Jones');

        $fleet = new Fleet();
        $fleet->add($boat1);
        $fleet->add($boat2);

        $squad = new Squad();
        $squad->add($crew1);
        $squad->add($crew2);

        $this->service->updateAllBoatFlexRanks($fleet, $squad);

        // boat1 owner is crew1, so flex
        $this->assertEquals(0, $boat1->getRank()->getDimension(BoatRankDimension::FLEXIBILITY));
        // boat2 owner is not in squad, so not flex
        $this->assertEquals(1, $boat2->getRank()->getDimension(BoatRankDimension::FLEXIBILITY));
    }

    // Tests batch updating of flexibility ranks for all crews in the squad
    public function testUpdateAllCrewFlexRanks(): void
    {
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

        $this->service->updateAllCrewFlexRanks($squad, $fleet);

        // crew1 owns boat1, so flex
        $this->assertEquals(0, $crew1->getRank()->getDimension(CrewRankDimension::FLEXIBILITY));
        // crew2 doesn't own any boat, so not flex
        $this->assertEquals(1, $crew2->getRank()->getDimension(CrewRankDimension::FLEXIBILITY));
    }

    // Tests updating flexibility ranks for both boats and crews simultaneously
    public function testUpdateAllFlexRanks(): void
    {
        $boat = $this->createBoat('sailaway', 'John', 'Doe');
        $crew = $this->createCrew('John', 'Doe');

        $fleet = new Fleet();
        $fleet->add($boat);

        $squad = new Squad();
        $squad->add($crew);

        $this->service->updateAllFlexRanks($fleet, $squad);

        // Both should be flex
        $this->assertEquals(0, $boat->getRank()->getDimension(BoatRankDimension::FLEXIBILITY));
        $this->assertEquals(0, $crew->getRank()->getDimension(CrewRankDimension::FLEXIBILITY));
    }

    // Tests flexibility calculation with multiple boats and crews in various ownership scenarios
    public function testFlexWithMultipleBoatsAndCrews(): void
    {
        // Create 2 boats and 3 crews
        // Boat 1 owned by Crew 1 (flex)
        // Boat 2 owned by someone not in crew (not flex)
        // Crew 3 doesn't own any boat (not flex)

        $boat1 = $this->createBoat('sailaway', 'John', 'Doe');
        $boat2 = $this->createBoat('seabreeze', 'Unknown', 'Owner');

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

        $this->service->updateAllFlexRanks($fleet, $squad);

        // Boat 1: owner is crew 1 (flex)
        $this->assertEquals(0, $boat1->getRank()->getDimension(BoatRankDimension::FLEXIBILITY));
        // Boat 2: owner not in squad (not flex)
        $this->assertEquals(1, $boat2->getRank()->getDimension(BoatRankDimension::FLEXIBILITY));

        // Crew 1: owns boat 1 (flex)
        $this->assertEquals(0, $crew1->getRank()->getDimension(CrewRankDimension::FLEXIBILITY));
        // Crew 2: doesn't own any boat (not flex)
        $this->assertEquals(1, $crew2->getRank()->getDimension(CrewRankDimension::FLEXIBILITY));
        // Crew 3: doesn't own any boat (not flex)
        $this->assertEquals(1, $crew3->getRank()->getDimension(CrewRankDimension::FLEXIBILITY));
    }
}
