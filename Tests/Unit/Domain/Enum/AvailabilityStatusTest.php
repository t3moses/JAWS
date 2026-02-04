<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Enum;

use App\Domain\Enum\AvailabilityStatus;
use PHPUnit\Framework\TestCase;

class AvailabilityStatusTest extends TestCase
{
    public function testEnumValuesAreCorrect(): void
    {
        $this->assertEquals(0, AvailabilityStatus::UNAVAILABLE->value);
        $this->assertEquals(1, AvailabilityStatus::AVAILABLE->value);
        $this->assertEquals(2, AvailabilityStatus::GUARANTEED->value);
        $this->assertEquals(3, AvailabilityStatus::WITHDRAWN->value);
    }

    public function testCanParticipateReturnsTrueForAvailable(): void
    {
        $status = AvailabilityStatus::AVAILABLE;

        $this->assertTrue($status->canParticipate());
    }

    public function testCanParticipateReturnsTrueForGuaranteed(): void
    {
        $status = AvailabilityStatus::GUARANTEED;

        $this->assertTrue($status->canParticipate());
    }

    public function testCanParticipateReturnsFalseForUnavailable(): void
    {
        $status = AvailabilityStatus::UNAVAILABLE;

        $this->assertFalse($status->canParticipate());
    }

    public function testCanParticipateReturnsFalseForWithdrawn(): void
    {
        $status = AvailabilityStatus::WITHDRAWN;

        $this->assertFalse($status->canParticipate());
    }

    public function testIsAssignedReturnsTrueForGuaranteed(): void
    {
        $status = AvailabilityStatus::GUARANTEED;

        $this->assertTrue($status->isAssigned());
    }

    public function testIsAssignedReturnsFalseForAvailable(): void
    {
        $status = AvailabilityStatus::AVAILABLE;

        $this->assertFalse($status->isAssigned());
    }

    public function testIsAssignedReturnsFalseForUnavailable(): void
    {
        $status = AvailabilityStatus::UNAVAILABLE;

        $this->assertFalse($status->isAssigned());
    }

    public function testIsAssignedReturnsFalseForWithdrawn(): void
    {
        $status = AvailabilityStatus::WITHDRAWN;

        $this->assertFalse($status->isAssigned());
    }

    public function testEnumFromInt(): void
    {
        $this->assertEquals(AvailabilityStatus::UNAVAILABLE, AvailabilityStatus::from(0));
        $this->assertEquals(AvailabilityStatus::AVAILABLE, AvailabilityStatus::from(1));
        $this->assertEquals(AvailabilityStatus::GUARANTEED, AvailabilityStatus::from(2));
        $this->assertEquals(AvailabilityStatus::WITHDRAWN, AvailabilityStatus::from(3));
    }

    public function testEnumFromIntThrowsExceptionForInvalidValue(): void
    {
        $this->expectException(\ValueError::class);

        AvailabilityStatus::from(99);
    }

    public function testAllEnumCasesExist(): void
    {
        $cases = AvailabilityStatus::cases();

        $this->assertCount(4, $cases);
        $this->assertContains(AvailabilityStatus::UNAVAILABLE, $cases);
        $this->assertContains(AvailabilityStatus::AVAILABLE, $cases);
        $this->assertContains(AvailabilityStatus::GUARANTEED, $cases);
        $this->assertContains(AvailabilityStatus::WITHDRAWN, $cases);
    }
}
