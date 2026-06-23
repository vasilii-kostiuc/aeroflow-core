<?php

declare(strict_types=1);

namespace App\Tests\Unit\FlightOperations\Domain\Entity;

use App\FlightOperations\Domain\Entity\CheckInCounter;
use App\FlightOperations\Domain\Entity\Gate;
use App\FlightOperations\Domain\Exception\InvalidOperationalResourceException;
use App\FlightOperations\Domain\ValueObject\OperationalResourceCode;
use PHPUnit\Framework\TestCase;

final class OperationalResourceTest extends TestCase
{
    public function testSupportsNumericAndCompositeNormalizedCodes(): void
    {
        $counter = CheckInCounter::create(OperationalResourceCode::fromString(' 12 '), 'Counter 12', 2);
        $gate = Gate::create(OperationalResourceCode::fromString('b-2'), 'Gate B-2', 1);
        self::assertSame('12', $counter->getCode()->toString());
        self::assertSame('B-2', $gate->getCode()->toString());
    }

    public function testRejectsInvalidCode(): void
    {
        $this->expectException(InvalidOperationalResourceException::class);
        OperationalResourceCode::fromString('#1');
    }
}
