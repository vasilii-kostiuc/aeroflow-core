<?php

declare(strict_types=1);

namespace App\Tests\Unit\FlightOperations\Domain\ValueObject;

use App\FlightOperations\Domain\Exception\InvalidAirportCodeException;
use App\FlightOperations\Domain\ValueObject\AirportCode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AirportCodeTest extends TestCase
{
    public function testNormalizesAirportCode(): void
    {
        self::assertSame('KIV', AirportCode::fromString(' kiv ')->toString());
    }

    #[DataProvider('invalidCodes')]
    public function testRejectsInvalidAirportCode(string $value): void
    {
        $this->expectException(InvalidAirportCodeException::class);

        AirportCode::fromString($value);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidCodes(): iterable
    {
        yield 'empty' => [''];
        yield 'too short' => ['KI'];
        yield 'too long' => ['KIVV'];
        yield 'contains digit' => ['K1V'];
    }
}
