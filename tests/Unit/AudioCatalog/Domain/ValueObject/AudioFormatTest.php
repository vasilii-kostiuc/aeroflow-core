<?php

declare(strict_types=1);

namespace App\Tests\Unit\AudioCatalog\Domain\ValueObject;

use App\AudioCatalog\Domain\ValueObject\AudioFormat;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AudioFormatTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function supportedMimeTypes(): iterable
    {
        yield 'wav' => ['audio/wav', 'wav'];
        yield 'x-wav preserves original mime' => ['audio/x-wav', 'wav'];
        yield 'mpeg' => ['audio/mpeg', 'mp3'];
        yield 'ogg' => ['audio/ogg', 'ogg'];
        yield 'application ogg' => ['application/ogg', 'ogg'];
    }

    #[DataProvider('supportedMimeTypes')]
    public function testResolvesExtensionAndPreservesOriginalMimeType(string $mimeType, string $extension): void
    {
        $format = AudioFormat::tryFromMimeType($mimeType);

        self::assertNotNull($format);
        self::assertSame($mimeType, $format->mimeType);
        self::assertSame($extension, $format->extension);
    }

    public function testReturnsNullForUnsupportedMimeType(): void
    {
        self::assertNull(AudioFormat::tryFromMimeType('application/pdf'));
    }
}
