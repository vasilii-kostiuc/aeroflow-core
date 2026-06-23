<?php

declare(strict_types=1);

namespace App\Tests\Unit\Announcements\Application\Service;

use App\Announcements\Application\Port\AudioCatalog\AudioAssetAvailabilityInterface;
use App\Announcements\Application\Service\AnnouncementAudioAssetValidator;
use App\Announcements\Domain\Exception\AudioAssetUnavailableException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class AnnouncementAudioAssetValidatorTest extends TestCase
{
    public function testDoesNotCheckAudioAssetForTextVariant(): void
    {
        $availability = $this->createMock(AudioAssetAvailabilityInterface::class);
        $availability->expects(self::never())->method('isAvailable');

        new AnnouncementAudioAssetValidator($availability)->validate('text', null);
    }

    public function testRejectsInvalidAudioAssetId(): void
    {
        $availability = $this->createMock(AudioAssetAvailabilityInterface::class);
        $availability->expects(self::never())->method('isAvailable');

        $this->expectException(AudioAssetUnavailableException::class);

        new AnnouncementAudioAssetValidator($availability)->validate('audio_asset', 'invalid');
    }

    public function testRejectsUnavailableAudioAsset(): void
    {
        $id = Uuid::v7();
        $availability = $this->createMock(AudioAssetAvailabilityInterface::class);
        $availability->expects(self::once())
            ->method('isAvailable')
            ->with($id)
            ->willReturn(false);

        $this->expectException(AudioAssetUnavailableException::class);

        new AnnouncementAudioAssetValidator($availability)->validate('audio_asset', $id->toRfc4122());
    }

    public function testAcceptsAvailableAudioAsset(): void
    {
        $id = Uuid::v7();
        $availability = $this->createMock(AudioAssetAvailabilityInterface::class);
        $availability->expects(self::once())
            ->method('isAvailable')
            ->with($id)
            ->willReturn(true);

        new AnnouncementAudioAssetValidator($availability)->validate('audio_asset', $id->toRfc4122());
    }
}
