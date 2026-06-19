<?php

declare(strict_types=1);

namespace App\Tests\Unit\FlightOperations\Domain\ValueObject;

use App\FlightOperations\Domain\Exception\InvalidFlightNumberException;
use App\FlightOperations\Domain\ValueObject\FlightNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FlightNumberTest extends TestCase
{
    #[DataProvider('validNumbers')]
    public function testNormalizesValidFlightNumber(string $input, string $expected): void
    {
        self::assertSame($expected, FlightNumber::fromString($input)->toString());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function validNumbers(): iterable
    {
        yield 'IATA-like' => [' 5f123 ', '5F123'];
        yield 'three-letter carrier' => ['wzz42', 'WZZ42'];
        yield 'one digit' => ['af1', 'AF1'];
    }

    #[DataProvider('invalidNumbers')]
    public function testRejectsInvalidFlightNumber(string $value): void
    {
        $this->expectException(InvalidFlightNumberException::class);

        FlightNumber::fromString($value);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidNumbers(): iterable
    {
        yield 'empty' => [''];
        yield 'no numeric part' => ['WZZ'];
        yield 'missing flight number' => ['A1'];
        yield 'number too long' => ['WZZ12345'];
        yield 'only digits' => ['12345'];
    }
}
