<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Enum;

use App\Domain\Enum\CrewRankDimension;
use PHPUnit\Framework\TestCase;

class CrewRankDimensionTest extends TestCase
{
    public function testEnumValuesAreCorrect(): void
    {
        // Arrange
        // Assert
        $this->assertEquals(0, CrewRankDimension::COMMITMENT->value);
        $this->assertEquals(1, CrewRankDimension::MEMBERSHIP->value);
        $this->assertEquals(2, CrewRankDimension::ABSENCE->value);
    }

    public function testAllReturnsAllDimensionsInOrder(): void
    {
        // Arrange
        $dimensions = CrewRankDimension::all();

        // Assert
        $this->assertCount(3, $dimensions);
        $this->assertEquals(CrewRankDimension::COMMITMENT, $dimensions[0]);
        $this->assertEquals(CrewRankDimension::MEMBERSHIP, $dimensions[1]);
        $this->assertEquals(CrewRankDimension::ABSENCE, $dimensions[2]);
    }

    public function testAllEnumCasesExist(): void
    {
        // Arrange
        $cases = CrewRankDimension::cases();

        // Assert
        $this->assertCount(3, $cases);
        $this->assertContains(CrewRankDimension::COMMITMENT, $cases);
        $this->assertContains(CrewRankDimension::MEMBERSHIP, $cases);
        $this->assertContains(CrewRankDimension::ABSENCE, $cases);
    }

    public function testEnumFromInt(): void
    {
        // Arrange
        // Assert
        $this->assertEquals(CrewRankDimension::COMMITMENT, CrewRankDimension::from(0));
        $this->assertEquals(CrewRankDimension::MEMBERSHIP, CrewRankDimension::from(1));
        $this->assertEquals(CrewRankDimension::ABSENCE, CrewRankDimension::from(2));
    }

    public function testEnumFromIntThrowsExceptionForInvalidValue(): void
    {
        // Arrange
        // Assert
        $this->expectException(\ValueError::class);

        CrewRankDimension::from(99);
    }
}
