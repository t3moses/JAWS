<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Collection;

use App\Domain\Collection\Fleet;
use App\Domain\Entity\Boat;
use App\Domain\ValueObject\BoatKey;
use App\Domain\ValueObject\EventId;
use PHPUnit\Framework\TestCase;

class FleetTest extends TestCase
{
    private function createBoat(string $key, string $name): Boat
    {
        return new Boat(
            key: BoatKey::fromString($key),
            displayName: $name,
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

    public function testAddBoatToFleet(): void
    {
        $fleet = new Fleet();
        $boat = $this->createBoat('sailaway', 'Sail Away');

        $fleet->add($boat);

        $this->assertEquals(1, $fleet->count());
        $this->assertTrue($fleet->has(BoatKey::fromString('sailaway')));
    }

    public function testAddMultipleBoats(): void
    {
        $fleet = new Fleet();
        $boat1 = $this->createBoat('sailaway', 'Sail Away');
        $boat2 = $this->createBoat('seabreeze', 'Sea Breeze');

        $fleet->add($boat1);
        $fleet->add($boat2);

        $this->assertEquals(2, $fleet->count());
    }

    public function testAddOverwritesExistingBoat(): void
    {
        $fleet = new Fleet();
        $boat1 = $this->createBoat('sailaway', 'Sail Away');
        $boat2 = $this->createBoat('sailaway', 'New Name');

        $fleet->add($boat1);
        $fleet->add($boat2);

        $this->assertEquals(1, $fleet->count());
        $retrieved = $fleet->get(BoatKey::fromString('sailaway'));
        $this->assertEquals('New Name', $retrieved->getDisplayName());
    }

    public function testRemoveBoat(): void
    {
        $fleet = new Fleet();
        $boat = $this->createBoat('sailaway', 'Sail Away');

        $fleet->add($boat);
        $fleet->remove(BoatKey::fromString('sailaway'));

        $this->assertEquals(0, $fleet->count());
        $this->assertFalse($fleet->has(BoatKey::fromString('sailaway')));
    }

    public function testRemoveNonExistentBoatDoesNotThrow(): void
    {
        $fleet = new Fleet();

        $fleet->remove(BoatKey::fromString('nonexistent'));

        $this->assertEquals(0, $fleet->count());
    }

    public function testGetBoat(): void
    {
        $fleet = new Fleet();
        $boat = $this->createBoat('sailaway', 'Sail Away');

        $fleet->add($boat);
        $retrieved = $fleet->get(BoatKey::fromString('sailaway'));

        $this->assertNotNull($retrieved);
        $this->assertEquals('sailaway', $retrieved->getKey()->toString());
    }

    public function testGetNonExistentBoatReturnsNull(): void
    {
        $fleet = new Fleet();

        $retrieved = $fleet->get(BoatKey::fromString('nonexistent'));

        $this->assertNull($retrieved);
    }

    public function testHasReturnsTrueWhenBoatExists(): void
    {
        $fleet = new Fleet();
        $boat = $this->createBoat('sailaway', 'Sail Away');

        $fleet->add($boat);

        $this->assertTrue($fleet->has(BoatKey::fromString('sailaway')));
    }

    public function testHasReturnsFalseWhenBoatDoesNotExist(): void
    {
        $fleet = new Fleet();

        $this->assertFalse($fleet->has(BoatKey::fromString('nonexistent')));
    }

    public function testAllReturnsAllBoats(): void
    {
        $fleet = new Fleet();
        $boat1 = $this->createBoat('sailaway', 'Sail Away');
        $boat2 = $this->createBoat('seabreeze', 'Sea Breeze');

        $fleet->add($boat1);
        $fleet->add($boat2);

        $boats = $fleet->all();

        $this->assertCount(2, $boats);
        $this->assertContains($boat1, $boats);
        $this->assertContains($boat2, $boats);
    }

    public function testAllReturnsIndexedArray(): void
    {
        $fleet = new Fleet();
        $boat1 = $this->createBoat('sailaway', 'Sail Away');
        $boat2 = $this->createBoat('seabreeze', 'Sea Breeze');

        $fleet->add($boat1);
        $fleet->add($boat2);

        $boats = $fleet->all();

        // Check that keys are 0, 1, 2, etc. (not associative)
        $this->assertEquals([0, 1], array_keys($boats));
    }

    public function testGetAvailableForReturnsOnlyAvailableBoats(): void
    {
        $fleet = new Fleet();
        $eventId = EventId::fromString('Fri May 29');

        $boat1 = $this->createBoat('sailaway', 'Sail Away');
        $boat1->setBerths($eventId, 2);

        $boat2 = $this->createBoat('seabreeze', 'Sea Breeze');
        $boat2->setBerths($eventId, 0);

        $boat3 = $this->createBoat('oceandream', 'Ocean Dream');
        $boat3->setBerths($eventId, 1);

        $fleet->add($boat1);
        $fleet->add($boat2);
        $fleet->add($boat3);

        $available = $fleet->getAvailableFor($eventId);

        $this->assertCount(2, $available);
        $this->assertContains($boat1, $available);
        $this->assertContains($boat3, $available);
        $this->assertNotContains($boat2, $available);
    }

    public function testGetAvailableForReturnsEmptyArrayWhenNoneAvailable(): void
    {
        $fleet = new Fleet();
        $eventId = EventId::fromString('Fri May 29');

        $boat = $this->createBoat('sailaway', 'Sail Away');
        $fleet->add($boat);

        $available = $fleet->getAvailableFor($eventId);

        $this->assertEmpty($available);
    }

    public function testCountReturnsZeroForEmptyFleet(): void
    {
        $fleet = new Fleet();

        $this->assertEquals(0, $fleet->count());
    }

    public function testCountReturnsCorrectNumber(): void
    {
        $fleet = new Fleet();
        $fleet->add($this->createBoat('sailaway', 'Sail Away'));
        $fleet->add($this->createBoat('seabreeze', 'Sea Breeze'));
        $fleet->add($this->createBoat('oceandream', 'Ocean Dream'));

        $this->assertEquals(3, $fleet->count());
    }

    public function testIsEmptyReturnsTrueForNewFleet(): void
    {
        $fleet = new Fleet();

        $this->assertTrue($fleet->isEmpty());
    }

    public function testIsEmptyReturnsFalseWhenBoatsExist(): void
    {
        $fleet = new Fleet();
        $fleet->add($this->createBoat('sailaway', 'Sail Away'));

        $this->assertFalse($fleet->isEmpty());
    }

    public function testClearRemovesAllBoats(): void
    {
        $fleet = new Fleet();
        $fleet->add($this->createBoat('sailaway', 'Sail Away'));
        $fleet->add($this->createBoat('seabreeze', 'Sea Breeze'));

        $fleet->clear();

        $this->assertTrue($fleet->isEmpty());
        $this->assertEquals(0, $fleet->count());
    }

    public function testFilterReturnsMatchingBoats(): void
    {
        $fleet = new Fleet();
        $boat1 = $this->createBoat('sailaway', 'Sail Away');
        $boat1->setAssistanceRequired(true);

        $boat2 = $this->createBoat('seabreeze', 'Sea Breeze');
        $boat2->setAssistanceRequired(false);

        $fleet->add($boat1);
        $fleet->add($boat2);

        $filtered = $fleet->filter(fn(Boat $b) => $b->requiresAssistance());

        $this->assertCount(1, $filtered);
        $this->assertContains($boat1, $filtered);
    }

    public function testMapTransformsBoats(): void
    {
        $fleet = new Fleet();
        $fleet->add($this->createBoat('sailaway', 'Sail Away'));
        $fleet->add($this->createBoat('seabreeze', 'Sea Breeze'));

        $names = $fleet->map(fn(Boat $b) => $b->getDisplayName());

        $this->assertEquals(['Sail Away', 'Sea Breeze'], $names);
    }

    public function testGetIteratorAllowsForeachLoop(): void
    {
        $fleet = new Fleet();
        $boat1 = $this->createBoat('sailaway', 'Sail Away');
        $boat2 = $this->createBoat('seabreeze', 'Sea Breeze');

        $fleet->add($boat1);
        $fleet->add($boat2);

        $count = 0;
        foreach ($fleet->getIterator() as $boat) {
            $this->assertInstanceOf(Boat::class, $boat);
            $count++;
        }

        $this->assertEquals(2, $count);
    }
}
