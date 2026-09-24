<?php

declare(strict_types=1);

namespace App\Tests\Unit\Announcements\Domain\Entity;

use App\Announcements\Domain\Entity\EmergencyAnnouncementTemplate;
use App\Announcements\Domain\Event\EmergencyTemplateCreated;
use App\Announcements\Domain\Exception\EmergencyTemplateLanguageNotConfiguredException;
use App\Announcements\Domain\Exception\InvalidEmergencyTemplateException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class EmergencyAnnouncementTemplateTest extends TestCase
{
    public function testCreatesActivePresetWithLanguageVariants(): void
    {
        $roAsset = Uuid::v7()->toRfc4122();
        $ruAsset = Uuid::v7()->toRfc4122();
        $template = EmergencyAnnouncementTemplate::create('  Пожарная тревога  ', [
            ['languageCode' => 'ro-MD', 'audioAssetId' => $roAsset],
            ['languageCode' => 'ru', 'audioAssetId' => $ruAsset],
        ]);

        self::assertSame('Пожарная тревога', $template->getName());
        self::assertTrue($template->isActive());
        self::assertSame(['ro-MD', 'ru'], $template->getLanguageCodes());
        self::assertInstanceOf(EmergencyTemplateCreated::class, $template->pullEvents()[0]);
    }

    public function testAssetsForLanguagesReturnsSelectedSubsetInOrder(): void
    {
        $roAsset = Uuid::v7()->toRfc4122();
        $ruAsset = Uuid::v7()->toRfc4122();
        $template = EmergencyAnnouncementTemplate::create('Эвакуация', [
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
        $template = EmergencyAnnouncementTemplate::create('Эвакуация', [
            ['languageCode' => 'ro-MD', 'audioAssetId' => Uuid::v7()->toRfc4122()],
        ]);

        $this->expectException(EmergencyTemplateLanguageNotConfiguredException::class);
        $template->assetsForLanguages(['en']);
    }

    public function testRejectsEmptyName(): void
    {
        $this->expectException(InvalidEmergencyTemplateException::class);
        EmergencyAnnouncementTemplate::create('  ', [
            ['languageCode' => 'ru', 'audioAssetId' => Uuid::v7()->toRfc4122()],
        ]);
    }

    public function testRejectsNoVariants(): void
    {
        $this->expectException(InvalidEmergencyTemplateException::class);
        EmergencyAnnouncementTemplate::create('Эвакуация', []);
    }

    public function testRejectsDuplicateLanguage(): void
    {
        $this->expectException(InvalidEmergencyTemplateException::class);
        EmergencyAnnouncementTemplate::create('Эвакуация', [
            ['languageCode' => 'ru', 'audioAssetId' => Uuid::v7()->toRfc4122()],
            ['languageCode' => 'ru', 'audioAssetId' => Uuid::v7()->toRfc4122()],
        ]);
    }

    public function testRejectsInvalidAssetId(): void
    {
        $this->expectException(InvalidEmergencyTemplateException::class);
        EmergencyAnnouncementTemplate::create('Эвакуация', [
            ['languageCode' => 'ru', 'audioAssetId' => 'not-a-uuid'],
        ]);
    }

    public function testStatusChangeIsIdempotent(): void
    {
        $template = EmergencyAnnouncementTemplate::create('Эвакуация', [
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
