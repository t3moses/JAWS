<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity;

use App\Domain\Entity\Crew;
use App\Domain\ValueObject\BoatKey;
use App\Domain\ValueObject\CrewKey;
use App\Domain\ValueObject\EventId;
use App\Domain\ValueObject\Rank;
use App\Domain\Enum\CrewRankDimension;
use App\Domain\Enum\AvailabilityStatus;
use App\Domain\Enum\SkillLevel;
use PHPUnit\Framework\TestCase;

class CrewTest extends TestCase
{
    private function createCrew(): Crew
    {
        $crew = new Crew(
            key: CrewKey::fromString('johndoe'),
            displayName: 'John Doe',
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

    public function testConstructorSetsProperties(): void
    {
        $crew = $this->createCrew();

        $this->assertEquals('johndoe', $crew->getKey()->toString());
        $this->assertEquals('John Doe', $crew->getDisplayName());
        $this->assertEquals('John', $crew->getFirstName());
        $this->assertEquals('Doe', $crew->getLastName());
        $this->assertNull($crew->getPartnerKey());
        $this->assertEquals('john@example.com', $crew->getEmail());
        $this->assertEquals('555-1234', $crew->getMobile());
        $this->assertTrue($crew->hasSocialPreference());
        $this->assertEquals('12345', $crew->getMembershipNumber());
        $this->assertEquals(SkillLevel::INTERMEDIATE, $crew->getSkill());
        $this->assertEquals('5 years', $crew->getExperience());
    }

    public function testConstructorInitializesDefaultRank(): void
    {
        $crew = $this->createCrew();

        $rank = $crew->getRank();
        $this->assertEquals(0, $rank->getDimension(CrewRankDimension::COMMITMENT));
        $this->assertEquals(1, $rank->getDimension(CrewRankDimension::FLEXIBILITY));
        $this->assertEquals(0, $rank->getDimension(CrewRankDimension::MEMBERSHIP)); // Has membership number
        $this->assertEquals(0, $rank->getDimension(CrewRankDimension::ABSENCE));
    }

    public function testConstructorSetsDefaultRankWithoutMembership(): void
    {
        $crew = new Crew(
            key: CrewKey::fromString('johndoe'),
            displayName: 'John Doe',
            firstName: 'John',
            lastName: 'Doe',
            partnerKey: null,
            mobile: null,
            socialPreference: false,
            membershipNumber: null,
            skill: SkillLevel::NOVICE,
            experience: null
        );
        $crew->setEmail('john@example.com');

        $rank = $crew->getRank();
        $this->assertEquals(1, $rank->getDimension(CrewRankDimension::MEMBERSHIP)); // No membership
    }

    public function testIdStartsAsNull(): void
    {
        $crew = $this->createCrew();

        $this->assertNull($crew->getId());
    }

    public function testSetIdUpdatesId(): void
    {
        $crew = $this->createCrew();
        $crew->setId(42);

        $this->assertEquals(42, $crew->getId());
    }

    public function testSetters(): void
    {
        $crew = $this->createCrew();
        $partnerKey = CrewKey::fromString('janedoe');

        $crew->setDisplayName('Jane Doe');
        $crew->setFirstName('Jane');
        $crew->setLastName('Doe');
        $crew->setPartnerKey($partnerKey);
        $crew->setEmail('jane@example.com');
        $crew->setMobile('555-5678');
        $crew->setSocialPreference(false);
        $crew->setMembershipNumber('54321');
        $crew->setSkill(SkillLevel::ADVANCED);
        $crew->setExperience('10 years');

        $this->assertEquals('Jane Doe', $crew->getDisplayName());
        $this->assertEquals('Jane', $crew->getFirstName());
        $this->assertEquals('Doe', $crew->getLastName());
        $this->assertEquals($partnerKey, $crew->getPartnerKey());
        $this->assertEquals('jane@example.com', $crew->getEmail());
        $this->assertEquals('555-5678', $crew->getMobile());
        $this->assertFalse($crew->hasSocialPreference());
        $this->assertEquals('54321', $crew->getMembershipNumber());
        $this->assertEquals(SkillLevel::ADVANCED, $crew->getSkill());
        $this->assertEquals('10 years', $crew->getExperience());
    }

    public function testHasPartnerReturnsFalseWhenNoPartner(): void
    {
        $crew = $this->createCrew();

        $this->assertFalse($crew->hasPartner());
    }

    public function testHasPartnerReturnsTrueWhenPartnerSet(): void
    {
        $crew = $this->createCrew();
        $crew->setPartnerKey(CrewKey::fromString('janedoe'));

        $this->assertTrue($crew->hasPartner());
    }

    public function testIsMemberReturnsTrueWithMembershipNumber(): void
    {
        $crew = $this->createCrew();

        $this->assertTrue($crew->isMember());
    }

    public function testIsMemberReturnsFalseWithoutMembershipNumber(): void
    {
        $crew = $this->createCrew();
        $crew->setMembershipNumber(null);

        $this->assertFalse($crew->isMember());
    }

    public function testSetMembershipNumberUpdatesRank(): void
    {
        $crew = $this->createCrew();

        $crew->setMembershipNumber(null);
        $this->assertEquals(1, $crew->getRank()->getDimension(CrewRankDimension::MEMBERSHIP));

        $crew->setMembershipNumber('12345');
        $this->assertEquals(0, $crew->getRank()->getDimension(CrewRankDimension::MEMBERSHIP));
    }

    public function testSetAndGetRank(): void
    {
        $crew = $this->createCrew();
        $rank = Rank::forCrew(
            commitment: 1,
            flexibility: 0,
            membership: 1,
            absence: 2
        );

        $crew->setRank($rank);

        $this->assertEquals($rank, $crew->getRank());
    }

    public function testSetRankDimension(): void
    {
        $crew = $this->createCrew();

        $crew->setRankDimension(CrewRankDimension::ABSENCE, 5);

        $this->assertEquals(5, $crew->getRank()->getDimension(CrewRankDimension::ABSENCE));
    }

    public function testUpdateAbsenceRankWithNoAbsences(): void
    {
        $crew = $this->createCrew();
        $eventId1 = EventId::fromString('Fri May 29');
        $eventId2 = EventId::fromString('Sat May 30');

        $crew->setHistory($eventId1, 'sailaway');
        $crew->setHistory($eventId2, 'anotherboat');

        $crew->updateAbsenceRank(['Fri May 29', 'Sat May 30']);

        $this->assertEquals(0, $crew->getRank()->getDimension(CrewRankDimension::ABSENCE));
    }

    public function testUpdateAbsenceRankWithAbsences(): void
    {
        $crew = $this->createCrew();
        $eventId1 = EventId::fromString('Fri May 29');
        $eventId2 = EventId::fromString('Sat May 30');
        $eventId3 = EventId::fromString('Sun May 31');

        $crew->setHistory($eventId1, 'sailaway');
        $crew->setHistory($eventId2, '');
        $crew->setHistory($eventId3, '');

        $crew->updateAbsenceRank(['Fri May 29', 'Sat May 30', 'Sun May 31']);

        $this->assertEquals(2, $crew->getRank()->getDimension(CrewRankDimension::ABSENCE));
    }

    public function testGetAvailabilityReturnsUnavailableByDefault(): void
    {
        $crew = $this->createCrew();
        $eventId = EventId::fromString('Fri May 29');

        $this->assertEquals(AvailabilityStatus::UNAVAILABLE, $crew->getAvailability($eventId));
    }

    public function testSetAndGetAvailability(): void
    {
        $crew = $this->createCrew();
        $eventId = EventId::fromString('Fri May 29');

        $crew->setAvailability($eventId, AvailabilityStatus::AVAILABLE);

        $this->assertEquals(AvailabilityStatus::AVAILABLE, $crew->getAvailability($eventId));
    }

    public function testGetAllAvailability(): void
    {
        $crew = $this->createCrew();
        $eventId1 = EventId::fromString('Fri May 29');
        $eventId2 = EventId::fromString('Sat May 30');

        $crew->setAvailability($eventId1, AvailabilityStatus::AVAILABLE);
        $crew->setAvailability($eventId2, AvailabilityStatus::GUARANTEED);

        $availability = $crew->getAllAvailability();

        $this->assertEquals(AvailabilityStatus::AVAILABLE, $availability['Fri May 29']);
        $this->assertEquals(AvailabilityStatus::GUARANTEED, $availability['Sat May 30']);
    }

    public function testSetAllAvailability(): void
    {
        $crew = $this->createCrew();
        $eventIds = [
            EventId::fromString('Fri May 29'),
            EventId::fromString('Sat May 30')
        ];

        $crew->setAllAvailability($eventIds, AvailabilityStatus::AVAILABLE);

        $this->assertEquals(AvailabilityStatus::AVAILABLE, $crew->getAvailability($eventIds[0]));
        $this->assertEquals(AvailabilityStatus::AVAILABLE, $crew->getAvailability($eventIds[1]));
    }

    public function testIsAvailableForReturnsFalseWhenUnavailable(): void
    {
        $crew = $this->createCrew();
        $eventId = EventId::fromString('Fri May 29');

        $this->assertFalse($crew->isAvailableFor($eventId));
    }

    public function testIsAvailableForReturnsTrueWhenAvailable(): void
    {
        $crew = $this->createCrew();
        $eventId = EventId::fromString('Fri May 29');

        $crew->setAvailability($eventId, AvailabilityStatus::AVAILABLE);

        $this->assertTrue($crew->isAvailableFor($eventId));
    }

    public function testIsAvailableForReturnsTrueWhenGuaranteed(): void
    {
        $crew = $this->createCrew();
        $eventId = EventId::fromString('Fri May 29');

        $crew->setAvailability($eventId, AvailabilityStatus::GUARANTEED);

        $this->assertTrue($crew->isAvailableFor($eventId));
    }

    public function testIsAssignedToReturnsFalseWhenNotAssigned(): void
    {
        $crew = $this->createCrew();
        $eventId = EventId::fromString('Fri May 29');

        $crew->setAvailability($eventId, AvailabilityStatus::AVAILABLE);

        $this->assertFalse($crew->isAssignedTo($eventId));
    }

    public function testIsAssignedToReturnsTrueWhenGuaranteed(): void
    {
        $crew = $this->createCrew();
        $eventId = EventId::fromString('Fri May 29');

        $crew->setAvailability($eventId, AvailabilityStatus::GUARANTEED);

        $this->assertTrue($crew->isAssignedTo($eventId));
    }

    public function testGetHistoryReturnsEmptyStringByDefault(): void
    {
        $crew = $this->createCrew();
        $eventId = EventId::fromString('Fri May 29');

        $this->assertEquals('', $crew->getHistory($eventId));
    }

    public function testSetAndGetHistory(): void
    {
        $crew = $this->createCrew();
        $eventId = EventId::fromString('Fri May 29');

        $crew->setHistory($eventId, 'sailaway');

        $this->assertEquals('sailaway', $crew->getHistory($eventId));
    }

    public function testGetAllHistory(): void
    {
        $crew = $this->createCrew();
        $eventId1 = EventId::fromString('Fri May 29');
        $eventId2 = EventId::fromString('Sat May 30');

        $crew->setHistory($eventId1, 'sailaway');
        $crew->setHistory($eventId2, 'anotherboat');

        $history = $crew->getAllHistory();

        $this->assertEquals('sailaway', $history['Fri May 29']);
        $this->assertEquals('anotherboat', $history['Sat May 30']);
    }

    public function testSetAllHistory(): void
    {
        $crew = $this->createCrew();
        $eventIds = [
            EventId::fromString('Fri May 29'),
            EventId::fromString('Sat May 30')
        ];

        $crew->setAllHistory($eventIds, 'sailaway');

        $this->assertEquals('sailaway', $crew->getHistory($eventIds[0]));
        $this->assertEquals('sailaway', $crew->getHistory($eventIds[1]));
    }

    public function testWasAssignedToReturnsNullWhenNoHistory(): void
    {
        $crew = $this->createCrew();
        $eventId = EventId::fromString('Fri May 29');

        $this->assertNull($crew->wasAssignedTo($eventId));
    }

    public function testWasAssignedToReturnsBoatKey(): void
    {
        $crew = $this->createCrew();
        $eventId = EventId::fromString('Fri May 29');

        $crew->setHistory($eventId, 'sailaway');

        $boatKey = $crew->wasAssignedTo($eventId);
        $this->assertNotNull($boatKey);
        $this->assertEquals('sailaway', $boatKey->toString());
    }

    public function testGetWhitelistReturnsEmptyArrayByDefault(): void
    {
        $crew = $this->createCrew();

        $this->assertEmpty($crew->getWhitelist());
    }

    public function testAddToWhitelist(): void
    {
        $crew = $this->createCrew();
        $boatKey = BoatKey::fromString('sailaway');

        $crew->addToWhitelist($boatKey);

        $whitelist = $crew->getWhitelist();
        $this->assertCount(1, $whitelist);
        $this->assertEquals('sailaway', $whitelist[0]);
    }

    public function testAddToWhitelistPreventsDuplicates(): void
    {
        $crew = $this->createCrew();
        $boatKey = BoatKey::fromString('sailaway');

        $crew->addToWhitelist($boatKey);
        $crew->addToWhitelist($boatKey);

        $whitelist = $crew->getWhitelist();
        $this->assertCount(1, $whitelist);
    }

    public function testRemoveFromWhitelist(): void
    {
        $crew = $this->createCrew();
        $boatKey1 = BoatKey::fromString('sailaway');
        $boatKey2 = BoatKey::fromString('anotherboat');

        $crew->addToWhitelist($boatKey1);
        $crew->addToWhitelist($boatKey2);
        $crew->removeFromWhitelist($boatKey1);

        $whitelist = $crew->getWhitelist();
        $this->assertCount(1, $whitelist);
        $this->assertEquals('anotherboat', $whitelist[0]);
    }

    public function testIsWhitelistedReturnsFalseWhenNotInWhitelist(): void
    {
        $crew = $this->createCrew();
        $boatKey = BoatKey::fromString('sailaway');

        $this->assertFalse($crew->isWhitelisted($boatKey));
    }

    public function testIsWhitelistedReturnsTrueWhenInWhitelist(): void
    {
        $crew = $this->createCrew();
        $boatKey = BoatKey::fromString('sailaway');

        $crew->addToWhitelist($boatKey);

        $this->assertTrue($crew->isWhitelisted($boatKey));
    }

    public function testSetWhitelist(): void
    {
        $crew = $this->createCrew();

        $crew->setWhitelist(['sailaway', 'anotherboat']);

        $whitelist = $crew->getWhitelist();
        $this->assertCount(2, $whitelist);
        $this->assertEquals('sailaway', $whitelist[0]);
        $this->assertEquals('anotherboat', $whitelist[1]);
    }

    public function testToArrayReturnsCompleteArray(): void
    {
        $crew = $this->createCrew();
        $crew->setId(1);

        $array = $crew->toArray();

        $this->assertEquals(1, $array['id']);
        $this->assertEquals('johndoe', $array['key']);
        $this->assertEquals('John Doe', $array['display_name']);
        $this->assertEquals('John', $array['first_name']);
        $this->assertEquals('Doe', $array['last_name']);
        $this->assertNull($array['partner_key']);
        $this->assertEquals('john@example.com', $array['email']);
        $this->assertEquals('555-1234', $array['mobile']);
        $this->assertTrue($array['social_preference']);
        $this->assertEquals('12345', $array['membership_number']);
        $this->assertEquals(SkillLevel::INTERMEDIATE->value, $array['skill']);
        $this->assertEquals('5 years', $array['experience']);
        $this->assertIsArray($array['rank']);
        $this->assertIsArray($array['availability']);
        $this->assertIsArray($array['history']);
        $this->assertIsArray($array['whitelist']);
    }
}
