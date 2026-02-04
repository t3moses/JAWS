<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\CrewKey;
use PHPUnit\Framework\TestCase;

class CrewKeyTest extends TestCase
{
    public function testFromNameCreatesValidKey(): void
    {
        $key = CrewKey::fromName('John', 'Doe');

        $this->assertEquals('johndoe', $key->toString());
    }

    public function testFromNameHandlesSpacesInNames(): void
    {
        $key = CrewKey::fromName('Mary Jane', 'Van Der Berg');

        $this->assertEquals('maryjanevanderberg', $key->toString());
    }

    public function testFromNameTrimsWhitespace(): void
    {
        $key = CrewKey::fromName('  John  ', '  Doe  ');

        $this->assertEquals('johndoe', $key->toString());
    }

    public function testFromNameConvertsToLowercase(): void
    {
        $key = CrewKey::fromName('JOHN', 'DOE');

        $this->assertEquals('johndoe', $key->toString());
    }

    public function testFromStringCreatesValidKey(): void
    {
        $key = CrewKey::fromString('customkey');

        $this->assertEquals('customkey', $key->toString());
    }

    public function testFromStringThrowsExceptionForEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Crew key cannot be empty');

        CrewKey::fromString('');
    }

    public function testFromNameThrowsExceptionForEmptyNames(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Crew key cannot be empty');

        CrewKey::fromName('   ', '   ');
    }

    public function testEqualsReturnsTrueForSameKey(): void
    {
        $key1 = CrewKey::fromString('johndoe');
        $key2 = CrewKey::fromString('johndoe');

        $this->assertTrue($key1->equals($key2));
    }

    public function testEqualsReturnsFalseForDifferentKeys(): void
    {
        $key1 = CrewKey::fromString('johndoe');
        $key2 = CrewKey::fromString('janedoe');

        $this->assertFalse($key1->equals($key2));
    }

    public function testToStringReturnsKeyValue(): void
    {
        $key = CrewKey::fromString('testcrew');

        $this->assertEquals('testcrew', (string) $key);
    }

    public function testImmutability(): void
    {
        $key1 = CrewKey::fromName('John', 'Doe');
        $key2 = CrewKey::fromName('John', 'Doe');

        // Both should have the same value but be different instances
        $this->assertEquals($key1->toString(), $key2->toString());
        $this->assertTrue($key1->equals($key2));
    }

    public function testSpecialCharactersInName(): void
    {
        $key = CrewKey::fromName("O'Brien", "D'Angelo");

        $this->assertEquals("o'briend'angelo", $key->toString());
    }

    public function testHyphensInName(): void
    {
        $key = CrewKey::fromName('Jean-Pierre', 'Saint-Exupéry');

        $this->assertEquals('jean-pierresaint-exupéry', $key->toString());
    }
}
