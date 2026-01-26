<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity;

use App\Domain\Entity\Boat;
use App\Domain\ValueObject\BoatKey;
use App\Domain\ValueObject\CrewKey;
use App\Domain\ValueObject\EventId;
use App\Domain\ValueObject\Rank;
use App\Domain\Enum\BoatRankDimension;
use PHPUnit\Framework\TestCase;

class BoatTest extends TestCase
{
    private function createBoat(): Boat
    {
        return new Boat(
            key: BoatKey::fromString('sailaway'),
            displayName: 'Sail Away',
            ownerFirstName: 'John',
            ownerLastName: 'Doe',
            ownerEmail: 'john@example.com',
            ownerMobile: '555-1234',
            minBerths: 1,
            maxBerths: 3,
            assistanceRequired: false,
            socialPreference: true
        );
    }

    public function testConstructorSetsProperties(): void
    {
        $boat = $this->createBoat();

        $this->assertEquals('sailaway', $boat->getKey()->toString());
        $this->assertEquals('Sail Away', $boat->getDisplayName());
        $this->assertEquals('John', $boat->getOwnerFirstName());
        $this->assertEquals('Doe', $boat->getOwnerLastName());
        $this->assertEquals('john@example.com', $boat->getOwnerEmail());
        $this->assertEquals('555-1234', $boat->getOwnerMobile());
        $this->assertEquals(1, $boat->getMinBerths());
        $this->assertEquals(3, $boat->getMaxBerths());
        $this->assertFalse($boat->requiresAssistance());
        $this->assertTrue($boat->hasSocialPreference());
    }

    public function testConstructorInitializesDefaultRank(): void
    {
        $boat = $this->createBoat();

        $rank = $boat->getRank();
        $this->assertEquals(1, $rank->getDimension(BoatRankDimension::FLEXIBILITY));
        $this->assertEquals(0, $rank->getDimension(BoatRankDimension::ABSENCE));
    }

    public function testIdStartsAsNull(): void
    {
        $boat = $this->createBoat();

        $this->assertNull($boat->getId());
    }

    public function testSetIdUpdatesId(): void
    {
        $boat = $this->createBoat();
        $boat->setId(42);

        $this->assertEquals(42, $boat->getId());
    }

    public function testGetOwnerKeyCreatesCorrectCrewKey(): void
    {
        $boat = $this->createBoat();

        $ownerKey = $boat->getOwnerKey();
        $this->assertEquals('johndoe', $ownerKey->toString());
    }

    public function testSetters(): void
    {
        $boat = $this->createBoat();

        $boat->setDisplayName('New Name');
        $boat->setOwnerFirstName('Jane');
        $boat->setOwnerLastName('Smith');
        $boat->setOwnerEmail('jane@example.com');
        $boat->setOwnerMobile('555-5678');
        $boat->setMinBerths(2);
        $boat->setMaxBerths(4);
        $boat->setAssistanceRequired(true);
        $boat->setSocialPreference(false);

        $this->assertEquals('New Name', $boat->getDisplayName());
        $this->assertEquals('Jane', $boat->getOwnerFirstName());
        $this->assertEquals('Smith', $boat->getOwnerLastName());
        $this->assertEquals('jane@example.com', $boat->getOwnerEmail());
        $this->assertEquals('555-5678', $boat->getOwnerMobile());
        $this->assertEquals(2, $boat->getMinBerths());
        $this->assertEquals(4, $boat->getMaxBerths());
        $this->assertTrue($boat->requiresAssistance());
        $this->assertFalse($boat->hasSocialPreference());
    }

    public function testSetAndGetRank(): void
    {
        $boat = $this->createBoat();
        $rank = Rank::forBoat(flexibility: 0, absence: 3);

        $boat->setRank($rank);

        $this->assertEquals($rank, $boat->getRank());
    }

    public function testSetRankDimension(): void
    {
        $boat = $this->createBoat();

        $boat->setRankDimension(BoatRankDimension::ABSENCE, 5);

        $this->assertEquals(5, $boat->getRank()->getDimension(BoatRankDimension::ABSENCE));
    }

    public function testUpdateAbsenceRankWithNoAbsences(): void
    {
        $boat = $this->createBoat();
        $eventId1 = EventId::fromString('Fri May 29');
        $eventId2 = EventId::fromString('Sat May 30');

        $boat->setHistory($eventId1, 'Y');
        $boat->setHistory($eventId2, 'Y');

        $boat->updateAbsenceRank(['Fri May 29', 'Sat May 30']);

        $this->assertEquals(0, $boat->getRank()->getDimension(BoatRankDimension::ABSENCE));
    }

    public function testUpdateAbsenceRankWithAbsences(): void
    {
        $boat = $this->createBoat();
        $eventId1 = EventId::fromString('Fri May 29');
        $eventId2 = EventId::fromString('Sat May 30');
        $eventId3 = EventId::fromString('Sun May 31');

        $boat->setHistory($eventId1, 'Y');
        $boat->setHistory($eventId2, '');
        $boat->setHistory($eventId3, '');

        $boat->updateAbsenceRank(['Fri May 29', 'Sat May 30', 'Sun May 31']);

        $this->assertEquals(2, $boat->getRank()->getDimension(BoatRankDimension::ABSENCE));
    }

    public function testGetBerthsReturnsZeroByDefault(): void
    {
        $boat = $this->createBoat();
        $eventId = EventId::fromString('Fri May 29');

        $this->assertEquals(0, $boat->getBerths($eventId));
    }

    public function testSetAndGetBerths(): void
    {
        $boat = $this->createBoat();
        $eventId = EventId::fromString('Fri May 29');

        $boat->setBerths($eventId, 2);

        $this->assertEquals(2, $boat->getBerths($eventId));
    }

    public function testGetAllBerths(): void
    {
        $boat = $this->createBoat();
        $eventId1 = EventId::fromString('Fri May 29');
        $eventId2 = EventId::fromString('Sat May 30');

        $boat->setBerths($eventId1, 2);
        $boat->setBerths($eventId2, 3);

        $berths = $boat->getAllBerths();

        $this->assertEquals(2, $berths['Fri May 29']);
        $this->assertEquals(3, $berths['Sat May 30']);
    }

    public function testSetAllBerths(): void
    {
        $boat = $this->createBoat();
        $eventIds = [
            EventId::fromString('Fri May 29'),
            EventId::fromString('Sat May 30')
        ];

        $boat->setAllBerths($eventIds, 2);

        $this->assertEquals(2, $boat->getBerths($eventIds[0]));
        $this->assertEquals(2, $boat->getBerths($eventIds[1]));
    }

    public function testIsAvailableForReturnsFalseWhenNoBerths(): void
    {
        $boat = $this->createBoat();
        $eventId = EventId::fromString('Fri May 29');

        $this->assertFalse($boat->isAvailableFor($eventId));
    }

    public function testIsAvailableForReturnsTrueWhenBerthsAvailable(): void
    {
        $boat = $this->createBoat();
        $eventId = EventId::fromString('Fri May 29');

        $boat->setBerths($eventId, 2);

        $this->assertTrue($boat->isAvailableFor($eventId));
    }

    public function testGetHistoryReturnsEmptyStringByDefault(): void
    {
        $boat = $this->createBoat();
        $eventId = EventId::fromString('Fri May 29');

        $this->assertEquals('', $boat->getHistory($eventId));
    }

    public function testSetAndGetHistory(): void
    {
        $boat = $this->createBoat();
        $eventId = EventId::fromString('Fri May 29');

        $boat->setHistory($eventId, 'Y');

        $this->assertEquals('Y', $boat->getHistory($eventId));
    }

    public function testGetAllHistory(): void
    {
        $boat = $this->createBoat();
        $eventId1 = EventId::fromString('Fri May 29');
        $eventId2 = EventId::fromString('Sat May 30');

        $boat->setHistory($eventId1, 'Y');
        $boat->setHistory($eventId2, '');

        $history = $boat->getAllHistory();

        $this->assertEquals('Y', $history['Fri May 29']);
        $this->assertEquals('', $history['Sat May 30']);
    }

    public function testSetAllHistory(): void
    {
        $boat = $this->createBoat();
        $eventIds = [
            EventId::fromString('Fri May 29'),
            EventId::fromString('Sat May 30')
        ];

        $boat->setAllHistory($eventIds, 'Y');

        $this->assertEquals('Y', $boat->getHistory($eventIds[0]));
        $this->assertEquals('Y', $boat->getHistory($eventIds[1]));
    }

    public function testDidParticipateReturnsTrueForY(): void
    {
        $boat = $this->createBoat();
        $eventId = EventId::fromString('Fri May 29');

        $boat->setHistory($eventId, 'Y');

        $this->assertTrue($boat->didParticipate($eventId));
    }

    public function testDidParticipateReturnsFalseForEmptyString(): void
    {
        $boat = $this->createBoat();
        $eventId = EventId::fromString('Fri May 29');

        $boat->setHistory($eventId, '');

        $this->assertFalse($boat->didParticipate($eventId));
    }

    public function testDidParticipateReturnsFalseForUnsetHistory(): void
    {
        $boat = $this->createBoat();
        $eventId = EventId::fromString('Fri May 29');

        $this->assertFalse($boat->didParticipate($eventId));
    }

    public function testToArrayReturnsCompleteArray(): void
    {
        $boat = $this->createBoat();
        $boat->setId(1);

        $array = $boat->toArray();

        $this->assertEquals(1, $array['id']);
        $this->assertEquals('sailaway', $array['key']);
        $this->assertEquals('Sail Away', $array['display_name']);
        $this->assertEquals('John', $array['owner_first_name']);
        $this->assertEquals('Doe', $array['owner_last_name']);
        $this->assertEquals('john@example.com', $array['owner_email']);
        $this->assertEquals('555-1234', $array['owner_mobile']);
        $this->assertEquals(1, $array['min_berths']);
        $this->assertEquals(3, $array['max_berths']);
        $this->assertFalse($array['assistance_required']);
        $this->assertTrue($array['social_preference']);
        $this->assertIsArray($array['rank']);
        $this->assertIsArray($array['berths']);
        $this->assertIsArray($array['history']);
    }

    public function testOccupiedBerthsCanBeSetDirectly(): void
    {
        $boat = $this->createBoat();

        $boat->occupied_berths = 2;

        $this->assertEquals(2, $boat->occupied_berths);
    }
}
