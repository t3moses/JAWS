<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Enum;

use App\Domain\Enum\SkillLevel;
use PHPUnit\Framework\TestCase;

class SkillLevelTest extends TestCase
{
    public function testEnumValuesAreCorrect(): void
    {
        $this->assertEquals(0, SkillLevel::NOVICE->value);
        $this->assertEquals(1, SkillLevel::INTERMEDIATE->value);
        $this->assertEquals(2, SkillLevel::ADVANCED->value);
    }

    public function testIsHighReturnsTrueForAdvanced(): void
    {
        $skill = SkillLevel::ADVANCED;

        $this->assertTrue($skill->isHigh());
    }

    public function testIsHighReturnsFalseForIntermediate(): void
    {
        $skill = SkillLevel::INTERMEDIATE;

        $this->assertFalse($skill->isHigh());
    }

    public function testIsHighReturnsFalseForNovice(): void
    {
        $skill = SkillLevel::NOVICE;

        $this->assertFalse($skill->isHigh());
    }

    public function testIsLowReturnsTrueForNovice(): void
    {
        $skill = SkillLevel::NOVICE;

        $this->assertTrue($skill->isLow());
    }

    public function testIsLowReturnsFalseForIntermediate(): void
    {
        $skill = SkillLevel::INTERMEDIATE;

        $this->assertFalse($skill->isLow());
    }

    public function testIsLowReturnsFalseForAdvanced(): void
    {
        $skill = SkillLevel::ADVANCED;

        $this->assertFalse($skill->isLow());
    }

    public function testFromIntCreatesNovice(): void
    {
        $skill = SkillLevel::fromInt(0);

        $this->assertEquals(SkillLevel::NOVICE, $skill);
    }

    public function testFromIntCreatesIntermediate(): void
    {
        $skill = SkillLevel::fromInt(1);

        $this->assertEquals(SkillLevel::INTERMEDIATE, $skill);
    }

    public function testFromIntCreatesAdvanced(): void
    {
        $skill = SkillLevel::fromInt(2);

        $this->assertEquals(SkillLevel::ADVANCED, $skill);
    }

    public function testFromIntThrowsExceptionForInvalidValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid skill level: 99');

        SkillLevel::fromInt(99);
    }

    public function testFromIntThrowsExceptionForNegativeValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid skill level: -1');

        SkillLevel::fromInt(-1);
    }

    public function testEnumFromInt(): void
    {
        $this->assertEquals(SkillLevel::NOVICE, SkillLevel::from(0));
        $this->assertEquals(SkillLevel::INTERMEDIATE, SkillLevel::from(1));
        $this->assertEquals(SkillLevel::ADVANCED, SkillLevel::from(2));
    }

    public function testEnumFromIntThrowsExceptionForInvalidValue(): void
    {
        $this->expectException(\ValueError::class);

        SkillLevel::from(99);
    }

    public function testAllEnumCasesExist(): void
    {
        $cases = SkillLevel::cases();

        $this->assertCount(3, $cases);
        $this->assertContains(SkillLevel::NOVICE, $cases);
        $this->assertContains(SkillLevel::INTERMEDIATE, $cases);
        $this->assertContains(SkillLevel::ADVANCED, $cases);
    }
}
