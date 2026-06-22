<?php

declare(strict_types=1);

namespace App\Tests\Unit\Announcements\Domain\ValueObject;

use App\Announcements\Domain\Exception\InvalidCheckInCounterRangeException;
use App\Announcements\Domain\ValueObject\CheckInCounterRange;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CheckInCounterRangeTest extends TestCase
{
    public function testRepresentsSingleCounter(): void
    {
        $range = CheckInCounterRange::single(5);

        self::assertSame(5, $range->start());
        self::assertSame(5, $range->end());
        self::assertSame('5', $range->toString());
    }

    public function testRepresentsContinuousRange(): void
    {
        $range = CheckInCounterRange::between(5, 8);

        self::assertSame(5, $range->start());
        self::assertSame(8, $range->end());
        self::assertSame('5-8', $range->toString());
        self::assertTrue($range->equals(CheckInCounterRange::between(5, 8)));
    }

    #[DataProvider('invalidRanges')]
    public function testRejectsInvalidRange(int $start, int $end): void
    {
        $this->expectException(InvalidCheckInCounterRangeException::class);

        CheckInCounterRange::between($start, $end);
    }

    /**
     * @return iterable<string, array{int, int}>
     */
    public static function invalidRanges(): iterable
    {
        yield 'zero start' => [0, 1];
        yield 'negative start' => [-1, 2];
        yield 'descending range' => [8, 5];
    }
}
