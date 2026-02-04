<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\Rank;
use App\Domain\Enum\BoatRankDimension;
use App\Domain\Enum\CrewRankDimension;
use PHPUnit\Framework\TestCase;

class RankTest extends TestCase
{
    public function testForBoatCreatesValidBoatRank(): void
    {
        $rank = Rank::forBoat(flexibility: 1, absence: 2);

        $this->assertEquals(1, $rank->getDimension(BoatRankDimension::FLEXIBILITY));
        $this->assertEquals(2, $rank->getDimension(BoatRankDimension::ABSENCE));
    }

    public function testForCrewCreatesValidCrewRank(): void
    {
        $rank = Rank::forCrew(
            commitment: 0,
            flexibility: 1,
            membership: 0,
            absence: 3
        );

        $this->assertEquals(0, $rank->getDimension(CrewRankDimension::COMMITMENT));
        $this->assertEquals(1, $rank->getDimension(CrewRankDimension::FLEXIBILITY));
        $this->assertEquals(0, $rank->getDimension(CrewRankDimension::MEMBERSHIP));
        $this->assertEquals(3, $rank->getDimension(CrewRankDimension::ABSENCE));
    }

    public function testFromArrayCreatesValidRank(): void
    {
        $rank = Rank::fromArray([0, 1, 2, 3]);

        $this->assertEquals([0, 1, 2, 3], $rank->toArray());
    }

    public function testGetDimensionReturnsZeroForMissingDimension(): void
    {
        $rank = Rank::forBoat(flexibility: 1, absence: 2);

        // Trying to get a dimension beyond the array bounds returns 0
        // Boat ranks have indices 0 and 1, crew MEMBERSHIP is index 2 which doesn't exist in boat rank
        $this->assertEquals(0, $rank->getDimension(CrewRankDimension::MEMBERSHIP));
    }

    public function testToArrayReturnsAllValues(): void
    {
        $rank = Rank::forBoat(flexibility: 1, absence: 2);

        $expected = [
            BoatRankDimension::FLEXIBILITY->value => 1,
            BoatRankDimension::ABSENCE->value => 2,
        ];

        $this->assertEquals($expected, $rank->toArray());
    }

    public function testWithDimensionCreatesNewRank(): void
    {
        $rank1 = Rank::forBoat(flexibility: 1, absence: 2);
        $rank2 = $rank1->withDimension(BoatRankDimension::ABSENCE, 5);

        // Original should be unchanged
        $this->assertEquals(2, $rank1->getDimension(BoatRankDimension::ABSENCE));

        // New rank should have updated value
        $this->assertEquals(5, $rank2->getDimension(BoatRankDimension::ABSENCE));
    }

    public function testCompareToReturnsNegativeWhenLessThan(): void
    {
        $rank1 = Rank::forBoat(flexibility: 0, absence: 1);
        $rank2 = Rank::forBoat(flexibility: 1, absence: 0);

        // rank1 < rank2 because first dimension (0) < (1)
        $this->assertLessThan(0, $rank1->compareTo($rank2));
    }

    public function testCompareToReturnsPositiveWhenGreaterThan(): void
    {
        $rank1 = Rank::forBoat(flexibility: 1, absence: 0);
        $rank2 = Rank::forBoat(flexibility: 0, absence: 1);

        // rank1 > rank2 because first dimension (1) > (0)
        $this->assertGreaterThan(0, $rank1->compareTo($rank2));
    }

    public function testCompareToReturnsZeroWhenEqual(): void
    {
        $rank1 = Rank::forBoat(flexibility: 1, absence: 2);
        $rank2 = Rank::forBoat(flexibility: 1, absence: 2);

        $this->assertEquals(0, $rank1->compareTo($rank2));
    }

    public function testCompareToUsesLexicographicOrder(): void
    {
        // First dimension determines comparison if different
        $rank1 = Rank::forBoat(flexibility: 0, absence: 5);
        $rank2 = Rank::forBoat(flexibility: 1, absence: 0);

        // rank1 < rank2 even though absence is higher, because flexibility takes precedence
        $this->assertLessThan(0, $rank1->compareTo($rank2));
    }

    public function testCompareToChecksSecondDimensionWhenFirstIsEqual(): void
    {
        $rank1 = Rank::forBoat(flexibility: 1, absence: 2);
        $rank2 = Rank::forBoat(flexibility: 1, absence: 5);

        // First dimension equal, so second dimension determines order
        $this->assertLessThan(0, $rank1->compareTo($rank2));
    }

    public function testIsGreaterThanReturnsTrue(): void
    {
        $rank1 = Rank::forBoat(flexibility: 1, absence: 0);
        $rank2 = Rank::forBoat(flexibility: 0, absence: 1);

        $this->assertTrue($rank1->isGreaterThan($rank2));
    }

    public function testIsGreaterThanReturnsFalse(): void
    {
        $rank1 = Rank::forBoat(flexibility: 0, absence: 1);
        $rank2 = Rank::forBoat(flexibility: 1, absence: 0);

        $this->assertFalse($rank1->isGreaterThan($rank2));
    }

    public function testIsLessThanReturnsTrue(): void
    {
        $rank1 = Rank::forBoat(flexibility: 0, absence: 1);
        $rank2 = Rank::forBoat(flexibility: 1, absence: 0);

        $this->assertTrue($rank1->isLessThan($rank2));
    }

    public function testIsLessThanReturnsFalse(): void
    {
        $rank1 = Rank::forBoat(flexibility: 1, absence: 0);
        $rank2 = Rank::forBoat(flexibility: 0, absence: 1);

        $this->assertFalse($rank1->isLessThan($rank2));
    }

    public function testEqualsReturnsTrue(): void
    {
        $rank1 = Rank::forBoat(flexibility: 1, absence: 2);
        $rank2 = Rank::forBoat(flexibility: 1, absence: 2);

        $this->assertTrue($rank1->equals($rank2));
    }

    public function testEqualsReturnsFalse(): void
    {
        $rank1 = Rank::forBoat(flexibility: 1, absence: 2);
        $rank2 = Rank::forBoat(flexibility: 1, absence: 3);

        $this->assertFalse($rank1->equals($rank2));
    }

    public function testToStringReturnsFormattedArray(): void
    {
        $rank = Rank::forBoat(flexibility: 1, absence: 2);

        $this->assertEquals('[1, 2]', (string) $rank);
    }

    public function testImmutability(): void
    {
        $rank1 = Rank::forBoat(flexibility: 1, absence: 2);
        $rank2 = $rank1->withDimension(BoatRankDimension::ABSENCE, 5);

        // Original rank should be unchanged
        $this->assertEquals(2, $rank1->getDimension(BoatRankDimension::ABSENCE));
        $this->assertEquals(5, $rank2->getDimension(BoatRankDimension::ABSENCE));
    }

    public function testCompareToWithDifferentDimensionCounts(): void
    {
        $boatRank = Rank::forBoat(flexibility: 1, absence: 2);
        $crewRank = Rank::forCrew(
            commitment: 1,
            flexibility: 2,
            membership: 0,
            absence: 3
        );

        // Should compare the dimensions they have in common
        $result = $boatRank->compareTo($crewRank);

        // First dimension: 1 vs 1 (equal), second dimension: 2 vs 2 (equal)
        // But crew has more dimensions, so they're not equal overall
        $this->assertLessThan(0, $result);
    }
}
