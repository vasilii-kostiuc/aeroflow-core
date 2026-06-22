<?php

declare(strict_types=1);

namespace App\Tests\Unit\Announcements\Domain\ValueObject;

use App\Announcements\Domain\Exception\InvalidGateCodeException;
use App\Announcements\Domain\ValueObject\GateCode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class GateCodeTest extends TestCase
{
    #[DataProvider('validCodes')]
    public function testNormalizesValidGateCode(string $input, string $expected): void
    {
        self::assertSame($expected, GateCode::fromString($input)->toString());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function validCodes(): iterable
    {
        yield 'letter and number' => [' a12 ', 'A12'];
        yield 'compound designation' => ['north  4-b', 'NORTH 4-B'];
        yield 'slash designation' => ['a/b', 'A/B'];
    }

    #[DataProvider('invalidCodes')]
    public function testRejectsInvalidGateCode(string $value): void
    {
        $this->expectException(InvalidGateCodeException::class);

        GateCode::fromString($value);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidCodes(): iterable
    {
        yield 'empty' => [''];
        yield 'unsupported punctuation' => ['A#1'];
        yield 'too long' => ['ABCDEFGHIJKLMNOPQ'];
    }
}
