<?php

declare(strict_types=1);

namespace App\Tests\Unit\Announcements\Domain\ValueObject;

use App\Announcements\Domain\Exception\InvalidAnnouncementLanguagesException;
use App\Announcements\Domain\ValueObject\AnnouncementLanguages;
use App\Shared\Domain\ValueObject\LanguageCode;
use PHPUnit\Framework\TestCase;

final class AnnouncementLanguagesTest extends TestCase
{
    public function testPreservesLanguageOrder(): void
    {
        $languages = AnnouncementLanguages::fromCodes(
            LanguageCode::fromString('ro'),
            LanguageCode::fromString('ru'),
            LanguageCode::fromString('en'),
        );

        self::assertSame(['ro', 'ru', 'en'], $languages->toStrings());
        self::assertTrue($languages->equals(AnnouncementLanguages::fromCodes(
            LanguageCode::fromString('ro'),
            LanguageCode::fromString('ru'),
            LanguageCode::fromString('en'),
        )));
    }

    public function testRejectsEmptyCollection(): void
    {
        $this->expectException(InvalidAnnouncementLanguagesException::class);

        AnnouncementLanguages::fromCodes();
    }

    public function testRejectsDuplicateNormalizedLanguage(): void
    {
        $this->expectException(InvalidAnnouncementLanguagesException::class);

        AnnouncementLanguages::fromCodes(
            LanguageCode::fromString('ro-md'),
            LanguageCode::fromString('RO-MD'),
        );
    }
}
