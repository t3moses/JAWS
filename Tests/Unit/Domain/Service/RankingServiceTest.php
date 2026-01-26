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
        return new Boat(
            key: BoatKey::fromString($key),
            displayName: 'Test Boat',
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

    private function createCrew(string $key): Crew
    {
        return new Crew(
            key: CrewKey::fromString($key),
            displayName: 'Test Crew',
            firstName: 'John',
            lastName: 'Doe',
            partnerKey: null,
            email: 'john@example.com',
            mobile: '555-1234',
            socialPreference: true,
            membershipNumber: '12345',
            skill: SkillLevel::INTERMEDIATE,
            experience: '5 years'
        );
    }

    public function testUpdateBoatAbsenceRanksWithNoAbsences(): void
    {
        $boat = $this->createBoat('sailaway');
        $boat->setHistory(EventId::fromString('Fri May 29'), 'Y');
        $boat->setHistory(EventId::fromString('Sat May 30'), 'Y');

        $this->service->updateBoatAbsenceRanks(
            [$boat],
            ['Fri May 29', 'Sat May 30']
        );

        $this->assertEquals(0, $boat->getRank()->getDimension(BoatRankDimension::ABSENCE));
    }

    public function testUpdateBoatAbsenceRanksWithAbsences(): void
    {
        $boat = $this->createBoat('sailaway');
        $boat->setHistory(EventId::fromString('Fri May 29'), 'Y');
        $boat->setHistory(EventId::fromString('Sat May 30'), '');
        $boat->setHistory(EventId::fromString('Sun May 31'), '');

        $this->service->updateBoatAbsenceRanks(
            [$boat],
            ['Fri May 29', 'Sat May 30', 'Sun May 31']
        );

        $this->assertEquals(2, $boat->getRank()->getDimension(BoatRankDimension::ABSENCE));
    }

    public function testUpdateBoatAbsenceRanksWithMultipleBoats(): void
    {
        $boat1 = $this->createBoat('sailaway');
        $boat1->setHistory(EventId::fromString('Fri May 29'), 'Y');

        $boat2 = $this->createBoat('seabreeze');
        $boat2->setHistory(EventId::fromString('Fri May 29'), '');

        $this->service->updateBoatAbsenceRanks(
            [$boat1, $boat2],
            ['Fri May 29']
        );

        $this->assertEquals(0, $boat1->getRank()->getDimension(BoatRankDimension::ABSENCE));
        $this->assertEquals(1, $boat2->getRank()->getDimension(BoatRankDimension::ABSENCE));
    }

    public function testUpdateCrewAbsenceRanksWithNoAbsences(): void
    {
        $crew = $this->createCrew('johndoe');
        $crew->setHistory(EventId::fromString('Fri May 29'), 'sailaway');
        $crew->setHistory(EventId::fromString('Sat May 30'), 'seabreeze');

        $this->service->updateCrewAbsenceRanks(
            [$crew],
            ['Fri May 29', 'Sat May 30']
        );

        $this->assertEquals(0, $crew->getRank()->getDimension(CrewRankDimension::ABSENCE));
    }

    public function testUpdateCrewAbsenceRanksWithAbsences(): void
    {
        $crew = $this->createCrew('johndoe');
        $crew->setHistory(EventId::fromString('Fri May 29'), 'sailaway');
        $crew->setHistory(EventId::fromString('Sat May 30'), '');
        $crew->setHistory(EventId::fromString('Sun May 31'), '');

        $this->service->updateCrewAbsenceRanks(
            [$crew],
            ['Fri May 29', 'Sat May 30', 'Sun May 31']
        );

        $this->assertEquals(2, $crew->getRank()->getDimension(CrewRankDimension::ABSENCE));
    }

    public function testUpdateCrewAbsenceRanksWithMultipleCrews(): void
    {
        $crew1 = $this->createCrew('johndoe');
        $crew1->setHistory(EventId::fromString('Fri May 29'), 'sailaway');

        $crew2 = $this->createCrew('janedoe');
        $crew2->setHistory(EventId::fromString('Fri May 29'), '');

        $this->service->updateCrewAbsenceRanks(
            [$crew1, $crew2],
            ['Fri May 29']
        );

        $this->assertEquals(0, $crew1->getRank()->getDimension(CrewRankDimension::ABSENCE));
        $this->assertEquals(1, $crew2->getRank()->getDimension(CrewRankDimension::ABSENCE));
    }

    public function testUpdateCrewCommitmentRanksWithGuaranteed(): void
    {
        $crew = $this->createCrew('johndoe');
        $eventId = EventId::fromString('Fri May 29');
        $crew->setAvailability($eventId, AvailabilityStatus::GUARANTEED);

        $this->service->updateCrewCommitmentRanks([$crew], $eventId);

        $this->assertEquals(0, $crew->getRank()->getDimension(CrewRankDimension::COMMITMENT));
    }

    public function testUpdateCrewCommitmentRanksWithAvailable(): void
    {
        $crew = $this->createCrew('johndoe');
        $eventId = EventId::fromString('Fri May 29');
        $crew->setAvailability($eventId, AvailabilityStatus::AVAILABLE);

        $this->service->updateCrewCommitmentRanks([$crew], $eventId);

        $this->assertEquals(1, $crew->getRank()->getDimension(CrewRankDimension::COMMITMENT));
    }

    public function testUpdateCrewCommitmentRanksWithWithdrawn(): void
    {
        $crew = $this->createCrew('johndoe');
        $eventId = EventId::fromString('Fri May 29');
        $crew->setAvailability($eventId, AvailabilityStatus::WITHDRAWN);

        $this->service->updateCrewCommitmentRanks([$crew], $eventId);

        $this->assertEquals(2, $crew->getRank()->getDimension(CrewRankDimension::COMMITMENT));
    }

    public function testUpdateCrewCommitmentRanksWithUnavailable(): void
    {
        $crew = $this->createCrew('johndoe');
        $eventId = EventId::fromString('Fri May 29');
        $crew->setAvailability($eventId, AvailabilityStatus::UNAVAILABLE);

        $this->service->updateCrewCommitmentRanks([$crew], $eventId);

        $this->assertEquals(3, $crew->getRank()->getDimension(CrewRankDimension::COMMITMENT));
    }

    public function testUpdateCrewCommitmentRanksWithMultipleCrews(): void
    {
        $crew1 = $this->createCrew('johndoe');
        $crew2 = $this->createCrew('janedoe');
        $eventId = EventId::fromString('Fri May 29');

        $crew1->setAvailability($eventId, AvailabilityStatus::GUARANTEED);
        $crew2->setAvailability($eventId, AvailabilityStatus::UNAVAILABLE);

        $this->service->updateCrewCommitmentRanks([$crew1, $crew2], $eventId);

        $this->assertEquals(0, $crew1->getRank()->getDimension(CrewRankDimension::COMMITMENT));
        $this->assertEquals(3, $crew2->getRank()->getDimension(CrewRankDimension::COMMITMENT));
    }

    public function testUpdateCrewMembershipRankWithMember(): void
    {
        $crew = $this->createCrew('johndoe');
        $crew->setMembershipNumber('12345');

        $this->service->updateCrewMembershipRank($crew);

        $this->assertEquals(1, $crew->getRank()->getDimension(CrewRankDimension::MEMBERSHIP));
    }

    public function testUpdateCrewMembershipRankWithoutMember(): void
    {
        $crew = $this->createCrew('johndoe');
        $crew->setMembershipNumber(null);

        $this->service->updateCrewMembershipRank($crew);

        $this->assertEquals(0, $crew->getRank()->getDimension(CrewRankDimension::MEMBERSHIP));
    }

    public function testUpdateAllBoatRanks(): void
    {
        $boat = $this->createBoat('sailaway');
        $boat->setHistory(EventId::fromString('Fri May 29'), '');

        $this->service->updateAllBoatRanks([$boat], ['Fri May 29']);

        $this->assertEquals(1, $boat->getRank()->getDimension(BoatRankDimension::ABSENCE));
    }

    public function testUpdateAllCrewRanks(): void
    {
        $crew = $this->createCrew('johndoe');
        $eventId = EventId::fromString('Fri May 29');
        $crew->setHistory(EventId::fromString('Sat May 30'), '');
        $crew->setAvailability($eventId, AvailabilityStatus::AVAILABLE);

        $this->service->updateAllCrewRanks([$crew], ['Sat May 30'], $eventId);

        $this->assertEquals(1, $crew->getRank()->getDimension(CrewRankDimension::ABSENCE));
        $this->assertEquals(1, $crew->getRank()->getDimension(CrewRankDimension::COMMITMENT));
    }

    public function testUpdateAbsenceRanksWithEmptyPastEvents(): void
    {
        $boat = $this->createBoat('sailaway');
        $crew = $this->createCrew('johndoe');

        $this->service->updateBoatAbsenceRanks([$boat], []);
        $this->service->updateCrewAbsenceRanks([$crew], []);

        $this->assertEquals(0, $boat->getRank()->getDimension(BoatRankDimension::ABSENCE));
        $this->assertEquals(0, $crew->getRank()->getDimension(CrewRankDimension::ABSENCE));
    }
}
