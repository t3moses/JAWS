<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Enum;

use App\Domain\Enum\AvailabilityStatus;
use PHPUnit\Framework\TestCase;

class AvailabilityStatusTest extends TestCase
{
    public function testEnumValuesAreCorrect(): void
    {
        // Arrange
        // Assert
        $this->assertEquals(0, AvailabilityStatus::UNAVAILABLE->value);
        $this->assertEquals(1, AvailabilityStatus::AVAILABLE->value);
        $this->assertEquals(2, AvailabilityStatus::GUARANTEED->value);
        $this->assertEquals(3, AvailabilityStatus::WITHDRAWN->value);
    }

    public function testCanParticipateReturnsTrueForAvailable(): void
    {
        // Arrange
        $status = AvailabilityStatus::AVAILABLE;

        // Assert
        $this->assertTrue($status->canParticipate());
    }

    public function testCanParticipateReturnsTrueForGuaranteed(): void
    {
        // Arrange
        $status = AvailabilityStatus::GUARANTEED;

        // Assert
        $this->assertTrue($status->canParticipate());
    }

    public function testCanParticipateReturnsFalseForUnavailable(): void
    {
        // Arrange
        $status = AvailabilityStatus::UNAVAILABLE;

        // Assert
        $this->assertFalse($status->canParticipate());
    }

    public function testCanParticipateReturnsFalseForWithdrawn(): void
    {
        // Arrange
        $status = AvailabilityStatus::WITHDRAWN;

        // Assert
        $this->assertFalse($status->canParticipate());
    }

    public function testIsAssignedReturnsTrueForGuaranteed(): void
    {
        // Arrange
        $status = AvailabilityStatus::GUARANTEED;

        // Assert
        $this->assertTrue($status->isAssigned());
    }

    public function testIsAssignedReturnsFalseForAvailable(): void
    {
        // Arrange
        $status = AvailabilityStatus::AVAILABLE;

        // Assert
        $this->assertFalse($status->isAssigned());
    }

    public function testIsAssignedReturnsFalseForUnavailable(): void
    {
        // Arrange
        $status = AvailabilityStatus::UNAVAILABLE;

        // Assert
        $this->assertFalse($status->isAssigned());
    }

    public function testIsAssignedReturnsFalseForWithdrawn(): void
    {
        // Arrange
        $status = AvailabilityStatus::WITHDRAWN;

        // Assert
        $this->assertFalse($status->isAssigned());
    }

    public function testEnumFromInt(): void
    {
        // Arrange
        // Assert
        $this->assertEquals(AvailabilityStatus::UNAVAILABLE, AvailabilityStatus::from(0));
        $this->assertEquals(AvailabilityStatus::AVAILABLE, AvailabilityStatus::from(1));
        $this->assertEquals(AvailabilityStatus::GUARANTEED, AvailabilityStatus::from(2));
        $this->assertEquals(AvailabilityStatus::WITHDRAWN, AvailabilityStatus::from(3));
    }

    public function testEnumFromIntThrowsExceptionForInvalidValue(): void
    {
        // Arrange
        // Assert
        $this->expectException(\ValueError::class);

        AvailabilityStatus::from(99);
    }

    public function testAllEnumCasesExist(): void
    {
        // Arrange
        $cases = AvailabilityStatus::cases();

        // Assert
        $this->assertCount(4, $cases);
        $this->assertContains(AvailabilityStatus::UNAVAILABLE, $cases);
        $this->assertContains(AvailabilityStatus::AVAILABLE, $cases);
        $this->assertContains(AvailabilityStatus::GUARANTEED, $cases);
        $this->assertContains(AvailabilityStatus::WITHDRAWN, $cases);
    }
}
