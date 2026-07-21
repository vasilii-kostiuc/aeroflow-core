<?php

declare(strict_types=1);

namespace App\Tests\Unit\Announcements\Domain\Entity;

use App\Announcements\Domain\Entity\Announcement;
use App\Announcements\Domain\Enum\AnnouncementStatus;
use App\Announcements\Domain\Enum\AnnouncementType;
use App\Announcements\Domain\Event\AnnouncementCancelled;
use App\Announcements\Domain\Event\AnnouncementCreated;
use App\Announcements\Domain\Event\AnnouncementRepeatEnded;
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

    public function testContinuationCarriesRepeatRuleFromInterval(): void
    {
        $continuation = $this->continuation(5);

        self::assertSame(['everyMinutes' => 5], $continuation->repeatRule());
        self::assertNull($continuation->getRepeatEndedAt());
    }

    public function testNonRepeatableTypeIgnoresStrayIntervalAndHasNoRepeatRule(): void
    {
        $arrival = Announcement::createPrepared(
            AnnouncementType::Arrival,
            Uuid::v7()->toRfc4122(),
            AnnouncementLanguages::fromCodes(LanguageCode::fromString('en')),
            [],
            null,
            [['languageCode' => 'en', 'sortOrder' => 1, 'items' => []]],
            null,
            5,
        );

        self::assertNull($arrival->repeatRule());
        self::assertFalse($arrival->endRepeat());
    }

    public function testEndRepeatIsIdempotentAndRecordsEventOnce(): void
    {
        $continuation = $this->continuation(5);
        $continuation->pullEvents();

        self::assertTrue($continuation->endRepeat());
        self::assertFalse($continuation->endRepeat());
        self::assertNotNull($continuation->getRepeatEndedAt());
        // The announcement itself stays Prepared — ending the series is not a cancel.
        self::assertSame(AnnouncementStatus::Prepared, $continuation->getStatus());

        $events = $continuation->pullEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(AnnouncementRepeatEnded::class, $events[0]);
    }

    private function continuation(int $everyMinutes): Announcement
    {
        return Announcement::createPrepared(
            AnnouncementType::CheckInContinuation,
            Uuid::v7()->toRfc4122(),
            AnnouncementLanguages::fromCodes(LanguageCode::fromString('en')),
            [['id' => Uuid::v7()->toRfc4122(), 'code' => '3']],
            null,
            [['languageCode' => 'en', 'sortOrder' => 1, 'items' => []]],
            Uuid::v7()->toRfc4122(),
            $everyMinutes,
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
