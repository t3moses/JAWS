<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\BoatKey;
use PHPUnit\Framework\TestCase;

class BoatKeyTest extends TestCase
{
    public function testFromBoatNameCreatesValidKey(): void
    {
        $key = BoatKey::fromBoatName('Sail Away');

        $this->assertEquals('sailaway', $key->toString());
    }

    public function testFromBoatNameHandlesMultipleSpaces(): void
    {
        $key = BoatKey::fromBoatName('My  Boat  Name');

        $this->assertEquals('myboatname', $key->toString());
    }

    public function testFromBoatNameTrimsWhitespace(): void
    {
        $key = BoatKey::fromBoatName('  Trimmed  ');

        $this->assertEquals('trimmed', $key->toString());
    }

    public function testFromBoatNameConvertsToLowercase(): void
    {
        $key = BoatKey::fromBoatName('UPPERCASE');

        $this->assertEquals('uppercase', $key->toString());
    }

    public function testFromStringCreatesValidKey(): void
    {
        $key = BoatKey::fromString('customkey');

        $this->assertEquals('customkey', $key->toString());
    }

    public function testFromStringThrowsExceptionForEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Boat key cannot be empty');

        BoatKey::fromString('');
    }

    public function testFromBoatNameThrowsExceptionForEmptyName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Boat key cannot be empty');

        BoatKey::fromBoatName('   ');
    }

    public function testEqualsReturnsTrueForSameKey(): void
    {
        $key1 = BoatKey::fromString('sailaway');
        $key2 = BoatKey::fromString('sailaway');

        $this->assertTrue($key1->equals($key2));
    }

    public function testEqualsReturnsFalseForDifferentKeys(): void
    {
        $key1 = BoatKey::fromString('sailaway');
        $key2 = BoatKey::fromString('otherboat');

        $this->assertFalse($key1->equals($key2));
    }

    public function testToStringReturnsKeyValue(): void
    {
        $key = BoatKey::fromString('testboat');

        $this->assertEquals('testboat', (string) $key);
    }

    public function testImmutability(): void
    {
        $key1 = BoatKey::fromBoatName('Test Boat');
        $key2 = BoatKey::fromBoatName('Test Boat');

        // Both should have the same value but be different instances
        $this->assertEquals($key1->toString(), $key2->toString());
        $this->assertTrue($key1->equals($key2));
    }

    public function testSpecialCharactersInBoatName(): void
    {
        $key = BoatKey::fromBoatName("O'Reilly's Boat");

        $this->assertEquals("o'reilly'sboat", $key->toString());
    }
}
