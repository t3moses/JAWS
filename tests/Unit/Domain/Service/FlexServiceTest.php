<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Service;

use App\Domain\Service\FlexService;
use App\Domain\Entity\Boat;
use App\Domain\ValueObject\BoatKey;
use App\Domain\Enum\BoatRankDimension;
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

    // Tests that boat owner is identified as flex when rank_flexibility is 0
    public function testIsBoatOwnerFlexReturnsTrueWhenFlexibilityRankIsZero(): void
    {
        // Arrange
        $boat = $this->createBoat('sailaway', 'John', 'Doe');
        $boat->setRankDimension(BoatRankDimension::FLEXIBILITY, 0);

        // Act
        $result = $this->service->isBoatOwnerFlex($boat);

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
        $result = $this->service->isBoatOwnerFlex($boat);

        // Assert
        $this->assertFalse($result);
    }

    // Tests that flex status does not change regardless of owner name (it's stored in rank, not derived)
    public function testIsBoatOwnerFlexIsBasedOnStoredRankNotOwnerName(): void
    {
        // Arrange — two boats with same owner but different flex ranks
        $flexBoat = $this->createBoat('sailaway', 'John', 'Doe');
        $flexBoat->setRankDimension(BoatRankDimension::FLEXIBILITY, 0);

        $notFlexBoat = $this->createBoat('seabreeze', 'John', 'Doe');
        // Default rank has flexibility=1

        // Act & Assert
        $this->assertTrue($this->service->isBoatOwnerFlex($flexBoat));
        $this->assertFalse($this->service->isBoatOwnerFlex($notFlexBoat));
    }
}
