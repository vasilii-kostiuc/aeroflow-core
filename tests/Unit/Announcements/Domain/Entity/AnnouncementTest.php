<?php

declare(strict_types=1);

namespace App\Tests\Unit\Announcements\Domain\Entity;

use App\Announcements\Domain\Entity\Announcement;
use App\Announcements\Domain\Enum\AnnouncementStatus;
use App\Announcements\Domain\Enum\AnnouncementType;
use App\Announcements\Domain\Event\AnnouncementCancelled;
use App\Announcements\Domain\Event\AnnouncementCreated;
use App\Announcements\Domain\Exception\InvalidAnnouncementResourcesException;
use App\Announcements\Domain\ValueObject\AnnouncementLanguages;
use App\Shared\Domain\ValueObject\LanguageCode;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class AnnouncementTest extends TestCase
{
    public function testStoresOperationalSnapshotAndPreparedSequence(): void
    {
        $flightId = Uuid::v7()->toRfc4122();
        $counterId = Uuid::v7()->toRfc4122();
        $assetId = Uuid::v7()->toRfc4122();
        $announcement = Announcement::createPrepared(
            AnnouncementType::CheckInOpening,
            $flightId,
            AnnouncementLanguages::fromCodes(LanguageCode::fromString('en')),
            [['id' => $counterId, 'code' => '3']],
            null,
            [['languageCode' => 'en', 'sortOrder' => 1, 'items' => [['type' => 'audio_asset', 'audioAssetId' => $assetId]]]],
        );

        self::assertSame([['id' => $counterId, 'code' => '3']], $announcement->getCheckInCounters());
        self::assertSame($assetId, $announcement->getAudioSequence()[0]['items'][0]['audioAssetId']);
        self::assertInstanceOf(AnnouncementCreated::class, $announcement->pullEvents()[0]);
    }

    public function testCancellationIsIdempotent(): void
    {
        $announcement = $this->arrival();
        $announcement->pullEvents();
        self::assertTrue($announcement->cancel());
        self::assertFalse($announcement->cancel());
        self::assertSame(AnnouncementStatus::Cancelled, $announcement->getStatus());
        self::assertInstanceOf(AnnouncementCancelled::class, $announcement->pullEvents()[0]);
    }

    public function testCheckInAnnouncementRequiresCounters(): void
    {
        $this->expectException(InvalidAnnouncementResourcesException::class);

        Announcement::createPrepared(
            AnnouncementType::CheckInOpening,
            Uuid::v7()->toRfc4122(),
            AnnouncementLanguages::fromCodes(LanguageCode::fromString('en')),
            [],
            null,
            [['languageCode' => 'en', 'sortOrder' => 1, 'items' => []]],
        );
    }

    public function testBoardingAnnouncementRequiresGate(): void
    {
        $this->expectException(InvalidAnnouncementResourcesException::class);

        Announcement::createPrepared(
            AnnouncementType::BoardingInvitation,
            Uuid::v7()->toRfc4122(),
            AnnouncementLanguages::fromCodes(LanguageCode::fromString('en')),
            [],
            null,
            [['languageCode' => 'en', 'sortOrder' => 1, 'items' => []]],
        );
    }

    private function arrival(): Announcement
    {
        return Announcement::createPrepared(
            AnnouncementType::Arrival,
            Uuid::v7()->toRfc4122(),
            AnnouncementLanguages::fromCodes(LanguageCode::fromString('en')),
            [],
            null,
            [['languageCode' => 'en', 'sortOrder' => 1, 'items' => []]],
        );
    }
}
