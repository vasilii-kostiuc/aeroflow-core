<?php

declare(strict_types=1);

namespace App\Tests\Unit\Announcements\Domain\Entity;

use App\Announcements\Domain\Entity\Announcement;
use App\Announcements\Domain\Enum\AnnouncementStatus;
use App\Announcements\Domain\Enum\AnnouncementType;
use App\Announcements\Domain\Event\AnnouncementCancelled;
use App\Announcements\Domain\Event\AnnouncementCreated;
use App\Announcements\Domain\Event\AnnouncementLanguagesChanged;
use App\Announcements\Domain\Exception\AnnouncementLanguagesCannotBeChangedException;
use App\Announcements\Domain\Exception\InvalidFlightDefinitionIdException;
use App\Announcements\Domain\ValueObject\AnnouncementLanguages;
use App\Announcements\Domain\ValueObject\CheckInCounterRange;
use App\Announcements\Domain\ValueObject\GateCode;
use App\Announcements\Domain\ValueObject\LanguageCode;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class AnnouncementTest extends TestCase
{
    public function testOpensCheckInWithCountersAndLanguages(): void
    {
        $flightDefinitionId = Uuid::v7()->toRfc4122();
        $languages = $this->languages();
        $range = CheckInCounterRange::between(5, 8);

        $announcement = Announcement::openCheckIn($flightDefinitionId, $range, $languages);

        self::assertSame(AnnouncementType::CheckInOpening, $announcement->getType());
        self::assertTrue($range->equals($announcement->getCheckInCounterRange()));
        self::assertNull($announcement->getGateCode());
        $this->assertCreatedAnnouncement($announcement, $flightDefinitionId, $languages);
    }

    public function testClosesCheckInWithCounters(): void
    {
        $announcement = Announcement::closeCheckIn(
            Uuid::v7()->toRfc4122(),
            CheckInCounterRange::single(4),
            $this->languages(),
        );

        self::assertSame(AnnouncementType::CheckInClosing, $announcement->getType());
        self::assertSame('4', $announcement->getCheckInCounterRange()?->toString());
        self::assertNull($announcement->getGateCode());
    }

    public function testInvitesToBoardWithGate(): void
    {
        $announcement = Announcement::inviteToBoard(
            Uuid::v7()->toRfc4122(),
            GateCode::fromString('a12'),
            $this->languages(),
        );

        self::assertSame(AnnouncementType::BoardingInvitation, $announcement->getType());
        self::assertSame('A12', $announcement->getGateCode()?->toString());
        self::assertNull($announcement->getCheckInCounterRange());
    }

    public function testAnnouncesArrivalWithoutOperationalParameters(): void
    {
        $announcement = Announcement::announceArrival(
            Uuid::v7()->toRfc4122(),
            $this->languages(),
        );

        self::assertSame(AnnouncementType::Arrival, $announcement->getType());
        self::assertNull($announcement->getGateCode());
        self::assertNull($announcement->getCheckInCounterRange());
    }

    public function testRejectsInvalidFlightDefinitionId(): void
    {
        $this->expectException(InvalidFlightDefinitionIdException::class);

        Announcement::announceArrival('not-a-uuid', $this->languages());
    }

    public function testCancellationIsIdempotentAndPublishesEventOnce(): void
    {
        $announcement = Announcement::announceArrival(
            Uuid::v7()->toRfc4122(),
            $this->languages(),
        );
        $announcement->pullEvents();

        self::assertTrue($announcement->cancel());
        self::assertSame(AnnouncementStatus::Cancelled, $announcement->getStatus());
        self::assertNotNull($announcement->getCancelledAt());

        $events = $announcement->pullEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(AnnouncementCancelled::class, $events[0]);
        self::assertSame($announcement->getId()->toRfc4122(), $events[0]->announcementId);

        self::assertFalse($announcement->cancel());
        self::assertSame([], $announcement->pullEvents());
    }

    public function testChangesLanguageCountAndOrderBeforePreparation(): void
    {
        $announcement = Announcement::announceArrival(
            Uuid::v7()->toRfc4122(),
            AnnouncementLanguages::fromCodes(LanguageCode::fromString('ro')),
        );
        $announcement->pullEvents();

        $changedLanguages = AnnouncementLanguages::fromCodes(
            LanguageCode::fromString('en'),
            LanguageCode::fromString('ro'),
            LanguageCode::fromString('ru'),
        );

        self::assertTrue($announcement->changeLanguages($changedLanguages));
        self::assertSame(['en', 'ro', 'ru'], $announcement->getLanguages()->toStrings());

        $events = $announcement->pullEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(AnnouncementLanguagesChanged::class, $events[0]);
        self::assertSame($announcement->getId()->toRfc4122(), $events[0]->announcementId);
        self::assertSame(['en', 'ro', 'ru'], $events[0]->languages);

        self::assertTrue($announcement->changeLanguages(
            AnnouncementLanguages::fromCodes(LanguageCode::fromString('ru')),
        ));
        self::assertSame(['ru'], $announcement->getLanguages()->toStrings());
    }

    public function testChangingLanguagesIsIdempotent(): void
    {
        $announcement = Announcement::announceArrival(
            Uuid::v7()->toRfc4122(),
            $this->languages(),
        );
        $announcement->pullEvents();

        self::assertFalse($announcement->changeLanguages($this->languages()));
        self::assertSame([], $announcement->pullEvents());
    }

    public function testCannotChangeLanguagesAfterCancellation(): void
    {
        $announcement = Announcement::announceArrival(
            Uuid::v7()->toRfc4122(),
            $this->languages(),
        );
        $announcement->cancel();

        $this->expectException(AnnouncementLanguagesCannotBeChangedException::class);

        $announcement->changeLanguages(AnnouncementLanguages::fromCodes(
            LanguageCode::fromString('en'),
        ));
    }

    private function assertCreatedAnnouncement(
        Announcement $announcement,
        string $flightDefinitionId,
        AnnouncementLanguages $languages,
    ): void {
        self::assertSame($flightDefinitionId, $announcement->getFlightDefinitionId()->toRfc4122());
        self::assertTrue($languages->equals($announcement->getLanguages()));
        self::assertSame(AnnouncementStatus::PendingPreparation, $announcement->getStatus());
        self::assertNull($announcement->getCancelledAt());
        self::assertInstanceOf(Uuid::class, $announcement->getId());
        self::assertSame('UTC', $announcement->getCreatedAt()->getTimezone()->getName());

        $events = $announcement->pullEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(AnnouncementCreated::class, $events[0]);
        self::assertSame($announcement->getId()->toRfc4122(), $events[0]->announcementId);
        self::assertSame(AnnouncementType::CheckInOpening->value, $events[0]->type);
        self::assertSame($flightDefinitionId, $events[0]->flightDefinitionId);
        self::assertSame(['ro', 'ru', 'en'], $events[0]->languages);
    }

    private function languages(): AnnouncementLanguages
    {
        return AnnouncementLanguages::fromCodes(
            LanguageCode::fromString('ro'),
            LanguageCode::fromString('ru'),
            LanguageCode::fromString('en'),
        );
    }
}
