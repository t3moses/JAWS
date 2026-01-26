<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Enum;

use App\Domain\Enum\CrewRankDimension;
use PHPUnit\Framework\TestCase;

class CrewRankDimensionTest extends TestCase
{
    public function testEnumValuesAreCorrect(): void
    {
        $this->assertEquals(0, CrewRankDimension::COMMITMENT->value);
        $this->assertEquals(1, CrewRankDimension::FLEXIBILITY->value);
        $this->assertEquals(2, CrewRankDimension::MEMBERSHIP->value);
        $this->assertEquals(3, CrewRankDimension::ABSENCE->value);
    }

    public function testAllReturnsAllDimensionsInOrder(): void
    {
        $dimensions = CrewRankDimension::all();

        $this->assertCount(4, $dimensions);
        $this->assertEquals(CrewRankDimension::COMMITMENT, $dimensions[0]);
        $this->assertEquals(CrewRankDimension::FLEXIBILITY, $dimensions[1]);
        $this->assertEquals(CrewRankDimension::MEMBERSHIP, $dimensions[2]);
        $this->assertEquals(CrewRankDimension::ABSENCE, $dimensions[3]);
    }

    public function testAllEnumCasesExist(): void
    {
        $cases = CrewRankDimension::cases();

        $this->assertCount(4, $cases);
        $this->assertContains(CrewRankDimension::COMMITMENT, $cases);
        $this->assertContains(CrewRankDimension::FLEXIBILITY, $cases);
        $this->assertContains(CrewRankDimension::MEMBERSHIP, $cases);
        $this->assertContains(CrewRankDimension::ABSENCE, $cases);
    }

    public function testEnumFromInt(): void
    {
        $this->assertEquals(CrewRankDimension::COMMITMENT, CrewRankDimension::from(0));
        $this->assertEquals(CrewRankDimension::FLEXIBILITY, CrewRankDimension::from(1));
        $this->assertEquals(CrewRankDimension::MEMBERSHIP, CrewRankDimension::from(2));
        $this->assertEquals(CrewRankDimension::ABSENCE, CrewRankDimension::from(3));
    }

    public function testEnumFromIntThrowsExceptionForInvalidValue(): void
    {
        $this->expectException(\ValueError::class);

        CrewRankDimension::from(99);
    }
}
