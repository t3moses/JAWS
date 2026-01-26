<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\EventId;
use PHPUnit\Framework\TestCase;

class EventIdTest extends TestCase
{
    public function testFromStringCreatesValidEventId(): void
    {
        $eventId = EventId::fromString('Fri May 29');

        $this->assertEquals('Fri May 29', $eventId->toString());
    }

    public function testFromStringThrowsExceptionForEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Event ID cannot be empty');

        EventId::fromString('');
    }

    public function testFromDateCreatesCorrectFormat(): void
    {
        $date = new \DateTime('2025-05-29');
        $eventId = EventId::fromDate($date);

        // Thu May 29 format (Day Mon DD)
        $this->assertEquals('Thu May 29', $eventId->toString());
    }

    public function testFromDateWithDifferentDate(): void
    {
        $date = new \DateTime('2025-12-25');
        $eventId = EventId::fromDate($date);

        $this->assertEquals('Thu Dec 25', $eventId->toString());
    }

    public function testEqualsReturnsTrueForSameEventId(): void
    {
        $eventId1 = EventId::fromString('Fri May 29');
        $eventId2 = EventId::fromString('Fri May 29');

        $this->assertTrue($eventId1->equals($eventId2));
    }

    public function testEqualsReturnsFalseForDifferentEventIds(): void
    {
        $eventId1 = EventId::fromString('Fri May 29');
        $eventId2 = EventId::fromString('Sat May 30');

        $this->assertFalse($eventId1->equals($eventId2));
    }

    public function testToStringReturnsEventIdValue(): void
    {
        $eventId = EventId::fromString('Fri May 29');

        $this->assertEquals('Fri May 29', (string) $eventId);
    }

    public function testGetHashReturnsCrc32Value(): void
    {
        $eventId = EventId::fromString('Fri May 29');
        $expectedHash = crc32('Fri May 29');

        $this->assertEquals($expectedHash, $eventId->getHash());
    }

    public function testGetHashIsDeterministic(): void
    {
        $eventId1 = EventId::fromString('Fri May 29');
        $eventId2 = EventId::fromString('Fri May 29');

        $this->assertEquals($eventId1->getHash(), $eventId2->getHash());
    }

    public function testGetHashDiffersForDifferentEventIds(): void
    {
        $eventId1 = EventId::fromString('Fri May 29');
        $eventId2 = EventId::fromString('Sat May 30');

        $this->assertNotEquals($eventId1->getHash(), $eventId2->getHash());
    }

    public function testImmutability(): void
    {
        $eventId1 = EventId::fromString('Fri May 29');
        $eventId2 = EventId::fromString('Fri May 29');

        // Both should have the same value but be different instances
        $this->assertEquals($eventId1->toString(), $eventId2->toString());
        $this->assertTrue($eventId1->equals($eventId2));
    }

    public function testFromDateWithDateTimeImmutable(): void
    {
        $date = new \DateTimeImmutable('2025-06-15');
        $eventId = EventId::fromDate($date);

        $this->assertEquals('Sun Jun 15', $eventId->toString());
    }
}
