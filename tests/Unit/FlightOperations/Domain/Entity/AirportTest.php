<?php

declare(strict_types=1);

namespace App\Tests\Unit\FlightOperations\Domain\Entity;

use App\FlightOperations\Domain\Entity\Airport;
use App\FlightOperations\Domain\ValueObject\AirportCode;
use PHPUnit\Framework\TestCase;

final class AirportTest extends TestCase
{
    public function testItCreatesNormalizesAndUpdatesAirport(): void
    {
        $airport = Airport::create(
            AirportCode::fromString('rmo'),
            ' Aeroportul Internațional Chișinău ',
            ' Кишинёв ',
            'md',
        );

        self::assertSame('RMO', $airport->getCode()->toString());
        self::assertSame('Кишинёв', $airport->getCityName());
        self::assertSame('MD', $airport->getCountryCode());
        self::assertTrue($airport->isActive());
        self::assertTrue($airport->updateDetails('Chișinău Airport', 'Кишинёв', 'MD'));
        self::assertFalse($airport->updateDetails('Chișinău Airport', 'Кишинёв', 'MD'));
    }

    public function testActivationIsIdempotent(): void
    {
        $airport = Airport::create(AirportCode::fromString('RMO'), 'Airport', 'Кишинёв', 'MD');

        self::assertTrue($airport->deactivate());
        self::assertFalse($airport->deactivate());
        self::assertTrue($airport->activate());
        self::assertFalse($airport->activate());
    }
}
