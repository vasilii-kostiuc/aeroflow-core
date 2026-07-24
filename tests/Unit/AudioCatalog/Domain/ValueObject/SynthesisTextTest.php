<?php

declare(strict_types=1);

namespace App\Tests\Unit\AudioCatalog\Domain\ValueObject;

use App\AudioCatalog\Domain\Exception\InvalidSynthesisRequestException;
use App\AudioCatalog\Domain\ValueObject\SynthesisText;
use PHPUnit\Framework\TestCase;

final class SynthesisTextTest extends TestCase
{
    public function testTrimsSurroundingWhitespace(): void
    {
        self::assertSame('Рейс 214', SynthesisText::fromString('  Рейс 214  ')->value);
    }

    public function testRejectsBlankText(): void
    {
        $this->expectException(InvalidSynthesisRequestException::class);
        SynthesisText::fromString("   \n\t ");
    }

    public function testRejectsTextExceedingMaxLength(): void
    {
        $this->expectException(InvalidSynthesisRequestException::class);
        SynthesisText::fromString(str_repeat('a', 2001));
    }

    public function testAcceptsTextAtMaxLength(): void
    {
        $text = str_repeat('a', 2000);

        self::assertSame($text, SynthesisText::fromString($text)->value);
    }
}
