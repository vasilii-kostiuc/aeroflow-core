<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\ValueObject;

use App\Shared\Domain\Exception\InvalidLanguageCodeException;
use App\Shared\Domain\ValueObject\LanguageCode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LanguageCodeTest extends TestCase
{
    #[DataProvider('validCodes')]
    public function testNormalizesValidLanguageCode(string $input, string $expected): void
    {
        self::assertSame($expected, LanguageCode::fromString($input)->toString());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function validCodes(): iterable
    {
        yield 'language' => [' RO ', 'ro'];
        yield 'language and region' => ['ro-md', 'ro-MD'];
        yield 'language and script' => ['zh-hant', 'zh-Hant'];
        yield 'language script and region' => ['sr-latn-rs', 'sr-Latn-RS'];
    }

    #[DataProvider('invalidCodes')]
    public function testRejectsInvalidLanguageCode(string $value): void
    {
        $this->expectException(InvalidLanguageCodeException::class);

        LanguageCode::fromString($value);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidCodes(): iterable
    {
        yield 'empty' => [''];
        yield 'one-letter primary language' => ['r'];
        yield 'underscore separator' => ['ro_MD'];
        yield 'empty subtag' => ['ro--MD'];
    }
}
