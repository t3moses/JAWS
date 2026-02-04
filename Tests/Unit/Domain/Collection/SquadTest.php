<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Collection;

use App\Domain\Collection\Squad;
use App\Domain\Entity\Crew;
use App\Domain\ValueObject\CrewKey;
use App\Domain\ValueObject\EventId;
use App\Domain\Enum\AvailabilityStatus;
use App\Domain\Enum\SkillLevel;
use PHPUnit\Framework\TestCase;

class SquadTest extends TestCase
{
    private function createCrew(string $key, string $firstName, string $lastName): Crew
    {
        $crew = new Crew(
            key: CrewKey::fromString($key),
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

    public function testAddCrewToSquad(): void
    {
        $squad = new Squad();
        $crew = $this->createCrew('johndoe', 'John', 'Doe');

        $squad->add($crew);

        $this->assertEquals(1, $squad->count());
        $this->assertTrue($squad->has(CrewKey::fromString('johndoe')));
    }

    public function testAddMultipleCrews(): void
    {
        $squad = new Squad();
        $crew1 = $this->createCrew('johndoe', 'John', 'Doe');
        $crew2 = $this->createCrew('janedoe', 'Jane', 'Doe');

        $squad->add($crew1);
        $squad->add($crew2);

        $this->assertEquals(2, $squad->count());
    }

    public function testAddOverwritesExistingCrew(): void
    {
        $squad = new Squad();
        $crew1 = $this->createCrew('johndoe', 'John', 'Doe');
        $crew2 = $this->createCrew('johndoe', 'Johnny', 'Doe');

        $squad->add($crew1);
        $squad->add($crew2);

        $this->assertEquals(1, $squad->count());
        $retrieved = $squad->get(CrewKey::fromString('johndoe'));
        $this->assertEquals('Johnny', $retrieved->getFirstName());
    }

    public function testRemoveCrew(): void
    {
        $squad = new Squad();
        $crew = $this->createCrew('johndoe', 'John', 'Doe');

        $squad->add($crew);
        $squad->remove(CrewKey::fromString('johndoe'));

        $this->assertEquals(0, $squad->count());
        $this->assertFalse($squad->has(CrewKey::fromString('johndoe')));
    }

    public function testRemoveNonExistentCrewDoesNotThrow(): void
    {
        $squad = new Squad();

        $squad->remove(CrewKey::fromString('nonexistent'));

        $this->assertEquals(0, $squad->count());
    }

    public function testGetCrew(): void
    {
        $squad = new Squad();
        $crew = $this->createCrew('johndoe', 'John', 'Doe');

        $squad->add($crew);
        $retrieved = $squad->get(CrewKey::fromString('johndoe'));

        $this->assertNotNull($retrieved);
        $this->assertEquals('johndoe', $retrieved->getKey()->toString());
    }

    public function testGetNonExistentCrewReturnsNull(): void
    {
        $squad = new Squad();

        $retrieved = $squad->get(CrewKey::fromString('nonexistent'));

        $this->assertNull($retrieved);
    }

    public function testHasReturnsTrueWhenCrewExists(): void
    {
        $squad = new Squad();
        $crew = $this->createCrew('johndoe', 'John', 'Doe');

        $squad->add($crew);

        $this->assertTrue($squad->has(CrewKey::fromString('johndoe')));
    }

    public function testHasReturnsFalseWhenCrewDoesNotExist(): void
    {
        $squad = new Squad();

        $this->assertFalse($squad->has(CrewKey::fromString('nonexistent')));
    }

    public function testAllReturnsAllCrews(): void
    {
        $squad = new Squad();
        $crew1 = $this->createCrew('johndoe', 'John', 'Doe');
        $crew2 = $this->createCrew('janedoe', 'Jane', 'Doe');

        $squad->add($crew1);
        $squad->add($crew2);

        $crews = $squad->all();

        $this->assertCount(2, $crews);
        $this->assertContains($crew1, $crews);
        $this->assertContains($crew2, $crews);
    }

    public function testAllReturnsIndexedArray(): void
    {
        $squad = new Squad();
        $crew1 = $this->createCrew('johndoe', 'John', 'Doe');
        $crew2 = $this->createCrew('janedoe', 'Jane', 'Doe');

        $squad->add($crew1);
        $squad->add($crew2);

        $crews = $squad->all();

        // Check that keys are 0, 1, 2, etc. (not associative)
        $this->assertEquals([0, 1], array_keys($crews));
    }

    public function testGetAvailableForReturnsOnlyAvailableCrews(): void
    {
        $squad = new Squad();
        $eventId = EventId::fromString('Fri May 29');

        $crew1 = $this->createCrew('johndoe', 'John', 'Doe');
        $crew1->setAvailability($eventId, AvailabilityStatus::AVAILABLE);

        $crew2 = $this->createCrew('janedoe', 'Jane', 'Doe');
        $crew2->setAvailability($eventId, AvailabilityStatus::UNAVAILABLE);

        $crew3 = $this->createCrew('bobsmith', 'Bob', 'Smith');
        $crew3->setAvailability($eventId, AvailabilityStatus::GUARANTEED);

        $squad->add($crew1);
        $squad->add($crew2);
        $squad->add($crew3);

        $available = $squad->getAvailableFor($eventId);

        $this->assertCount(2, $available);
        $this->assertContains($crew1, $available);
        $this->assertContains($crew3, $available);
        $this->assertNotContains($crew2, $available);
    }

    public function testGetAvailableForReturnsEmptyArrayWhenNoneAvailable(): void
    {
        $squad = new Squad();
        $eventId = EventId::fromString('Fri May 29');

        $crew = $this->createCrew('johndoe', 'John', 'Doe');
        $squad->add($crew);

        $available = $squad->getAvailableFor($eventId);

        $this->assertEmpty($available);
    }

    public function testGetAssignedToReturnsOnlyAssignedCrews(): void
    {
        $squad = new Squad();
        $eventId = EventId::fromString('Fri May 29');

        $crew1 = $this->createCrew('johndoe', 'John', 'Doe');
        $crew1->setAvailability($eventId, AvailabilityStatus::AVAILABLE);

        $crew2 = $this->createCrew('janedoe', 'Jane', 'Doe');
        $crew2->setAvailability($eventId, AvailabilityStatus::GUARANTEED);

        $crew3 = $this->createCrew('bobsmith', 'Bob', 'Smith');
        $crew3->setAvailability($eventId, AvailabilityStatus::GUARANTEED);

        $squad->add($crew1);
        $squad->add($crew2);
        $squad->add($crew3);

        $assigned = $squad->getAssignedTo($eventId);

        $this->assertCount(2, $assigned);
        $this->assertContains($crew2, $assigned);
        $this->assertContains($crew3, $assigned);
        $this->assertNotContains($crew1, $assigned);
    }

    public function testGetAssignedToReturnsEmptyArrayWhenNoneAssigned(): void
    {
        $squad = new Squad();
        $eventId = EventId::fromString('Fri May 29');

        $crew = $this->createCrew('johndoe', 'John', 'Doe');
        $crew->setAvailability($eventId, AvailabilityStatus::AVAILABLE);
        $squad->add($crew);

        $assigned = $squad->getAssignedTo($eventId);

        $this->assertEmpty($assigned);
    }

    public function testCountReturnsZeroForEmptySquad(): void
    {
        $squad = new Squad();

        $this->assertEquals(0, $squad->count());
    }

    public function testCountReturnsCorrectNumber(): void
    {
        $squad = new Squad();
        $squad->add($this->createCrew('johndoe', 'John', 'Doe'));
        $squad->add($this->createCrew('janedoe', 'Jane', 'Doe'));
        $squad->add($this->createCrew('bobsmith', 'Bob', 'Smith'));

        $this->assertEquals(3, $squad->count());
    }

    public function testIsEmptyReturnsTrueForNewSquad(): void
    {
        $squad = new Squad();

        $this->assertTrue($squad->isEmpty());
    }

    public function testIsEmptyReturnsFalseWhenCrewsExist(): void
    {
        $squad = new Squad();
        $squad->add($this->createCrew('johndoe', 'John', 'Doe'));

        $this->assertFalse($squad->isEmpty());
    }

    public function testClearRemovesAllCrews(): void
    {
        $squad = new Squad();
        $squad->add($this->createCrew('johndoe', 'John', 'Doe'));
        $squad->add($this->createCrew('janedoe', 'Jane', 'Doe'));

        $squad->clear();

        $this->assertTrue($squad->isEmpty());
        $this->assertEquals(0, $squad->count());
    }

    public function testFilterReturnsMatchingCrews(): void
    {
        $squad = new Squad();
        $crew1 = $this->createCrew('johndoe', 'John', 'Doe');
        $crew1->setSkill(SkillLevel::ADVANCED);

        $crew2 = $this->createCrew('janedoe', 'Jane', 'Doe');
        $crew2->setSkill(SkillLevel::NOVICE);

        $squad->add($crew1);
        $squad->add($crew2);

        $filtered = $squad->filter(fn(Crew $c) => $c->getSkill()->isHigh());

        $this->assertCount(1, $filtered);
        $this->assertContains($crew1, $filtered);
    }

    public function testMapTransformsCrews(): void
    {
        $squad = new Squad();
        $squad->add($this->createCrew('johndoe', 'John', 'Doe'));
        $squad->add($this->createCrew('janedoe', 'Jane', 'Doe'));

        $names = $squad->map(fn(Crew $c) => $c->getDisplayName());

        $this->assertEquals(['John Doe', 'Jane Doe'], $names);
    }

    public function testGetIteratorAllowsForeachLoop(): void
    {
        $squad = new Squad();
        $crew1 = $this->createCrew('johndoe', 'John', 'Doe');
        $crew2 = $this->createCrew('janedoe', 'Jane', 'Doe');

        $squad->add($crew1);
        $squad->add($crew2);

        $count = 0;
        foreach ($squad->getIterator() as $crew) {
            $this->assertInstanceOf(Crew::class, $crew);
            $count++;
        }

        $this->assertEquals(2, $count);
    }
}
