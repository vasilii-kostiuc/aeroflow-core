<?php

declare(strict_types=1);

namespace App\Tests\Unit\Announcements\Domain\Entity;

use App\Announcements\Domain\Entity\SupplementaryAnnouncementTemplate;
use App\Announcements\Domain\Event\SupplementaryTemplateCreated;
use App\Announcements\Domain\Exception\InvalidSupplementaryTemplateException;
use App\Announcements\Domain\Exception\SupplementaryTemplateLanguageNotConfiguredException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class SupplementaryAnnouncementTemplateTest extends TestCase
{
    public function testCreatesActivePresetWithLanguageVariants(): void
    {
        $roAsset = Uuid::v7()->toRfc4122();
        $ruAsset = Uuid::v7()->toRfc4122();
        $template = SupplementaryAnnouncementTemplate::create('  Безопасность  ', [
            ['languageCode' => 'ro-MD', 'audioAssetId' => $roAsset],
            ['languageCode' => 'ru', 'audioAssetId' => $ruAsset],
        ]);

        self::assertSame('Безопасность', $template->getName());
        self::assertTrue($template->isActive());
        self::assertSame(['ro-MD', 'ru'], $template->getLanguageCodes());
        self::assertInstanceOf(SupplementaryTemplateCreated::class, $template->pullEvents()[0]);
    }

    public function testAssetsForLanguagesReturnsSelectedSubsetInOrder(): void
    {
        $roAsset = Uuid::v7()->toRfc4122();
        $ruAsset = Uuid::v7()->toRfc4122();
        $template = SupplementaryAnnouncementTemplate::create('Такси', [
            ['languageCode' => 'ro-MD', 'audioAssetId' => $roAsset],
            ['languageCode' => 'ru', 'audioAssetId' => $ruAsset],
        ]);

        self::assertSame(
            [['languageCode' => 'ru', 'audioAssetId' => $ruAsset]],
            $template->assetsForLanguages(['ru']),
        );
    }

    public function testAssetsForUnconfiguredLanguageIsRejected(): void
    {
        $template = SupplementaryAnnouncementTemplate::create('Такси', [
            ['languageCode' => 'ro-MD', 'audioAssetId' => Uuid::v7()->toRfc4122()],
        ]);

        $this->expectException(SupplementaryTemplateLanguageNotConfiguredException::class);
        $template->assetsForLanguages(['en']);
    }

    public function testRejectsEmptyName(): void
    {
        $this->expectException(InvalidSupplementaryTemplateException::class);
        SupplementaryAnnouncementTemplate::create('  ', [
            ['languageCode' => 'ru', 'audioAssetId' => Uuid::v7()->toRfc4122()],
        ]);
    }

    public function testRejectsNoVariants(): void
    {
        $this->expectException(InvalidSupplementaryTemplateException::class);
        SupplementaryAnnouncementTemplate::create('Такси', []);
    }

    public function testRejectsDuplicateLanguage(): void
    {
        $this->expectException(InvalidSupplementaryTemplateException::class);
        SupplementaryAnnouncementTemplate::create('Такси', [
            ['languageCode' => 'ru', 'audioAssetId' => Uuid::v7()->toRfc4122()],
            ['languageCode' => 'ru', 'audioAssetId' => Uuid::v7()->toRfc4122()],
        ]);
    }

    public function testRejectsInvalidAssetId(): void
    {
        $this->expectException(InvalidSupplementaryTemplateException::class);
        SupplementaryAnnouncementTemplate::create('Такси', [
            ['languageCode' => 'ru', 'audioAssetId' => 'not-a-uuid'],
        ]);
    }

    public function testStatusChangeIsIdempotent(): void
    {
        $template = SupplementaryAnnouncementTemplate::create('Такси', [
            ['languageCode' => 'ru', 'audioAssetId' => Uuid::v7()->toRfc4122()],
        ]);
        $template->pullEvents();

        self::assertTrue($template->deactivate());
        self::assertFalse($template->deactivate());
        self::assertFalse($template->isActive());
        self::assertTrue($template->activate());
        self::assertTrue($template->isActive());
    }
}
