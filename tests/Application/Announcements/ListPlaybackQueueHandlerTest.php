<?php

declare(strict_types=1);

namespace App\Tests\Application\Announcements;

use App\Announcements\Application\ListPlaybackQueue\ListPlaybackQueueHandler;
use App\Announcements\Application\ListPlaybackQueue\ListPlaybackQueueQuery;
use App\Announcements\Application\Playback\PlaybackEventReceiptReaderInterface;
use App\Announcements\Application\Playback\PlaybackEventReceiptView;
use App\Announcements\Application\Port\FlightOperations\FlightDefinitionLookupInterface;
use App\Announcements\Application\Port\FlightOperations\FlightDefinitionSnapshot;
use App\Announcements\Domain\Entity\Announcement;
use App\Announcements\Domain\Enum\AnnouncementType;
use App\Announcements\Domain\Enum\FlightDirection;
use App\Announcements\Domain\Repository\AnnouncementRepositoryInterface;
use App\Announcements\Domain\ValueObject\AnnouncementLanguages;
use App\Shared\Domain\ValueObject\LanguageCode;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class ListPlaybackQueueHandlerTest extends TestCase
{
    public function testDerivesPlayingWaitingAndRecentFromReceipts(): void
    {
        $playingAnnouncement = $this->announcement();
        $waitingAnnouncementA = $this->announcement();
        $waitingAnnouncementB = $this->announcement();
        $completedAnnouncement = $this->announcement();
        $failedAnnouncement = $this->announcement();

        $playingJob = Uuid::v7()->toRfc4122();
        $waitingJobA = Uuid::v7()->toRfc4122();
        $waitingJobB = Uuid::v7()->toRfc4122();
        $completedJob = Uuid::v7()->toRfc4122();
        $failedJob = Uuid::v7()->toRfc4122();

        $receipts = [
            $this->receipt('queued', $completedAnnouncement, $completedJob, '09:00:00'),
            $this->receipt('started', $completedAnnouncement, $completedJob, '09:00:01'),
            $this->receipt('completed', $completedAnnouncement, $completedJob, '09:00:30'),
            $this->receipt('queued', $failedAnnouncement, $failedJob, '09:01:00'),
            $this->receipt('started', $failedAnnouncement, $failedJob, '09:01:01'),
            $this->receipt('failed', $failedAnnouncement, $failedJob, '09:01:10', 'player exited 1'),
            $this->receipt('queued', $playingAnnouncement, $playingJob, '09:02:00'),
            $this->receipt('started', $playingAnnouncement, $playingJob, '09:02:01'),
            $this->receipt('queued', $waitingAnnouncementA, $waitingJobA, '09:02:10'),
            $this->receipt('queued', $waitingAnnouncementB, $waitingJobB, '09:02:20'),
        ];

        $result = $this->handler($receipts, [
            $playingAnnouncement,
            $waitingAnnouncementA,
            $waitingAnnouncementB,
            $completedAnnouncement,
            $failedAnnouncement,
        ])(new ListPlaybackQueueQuery());

        self::assertNotNull($result->playing);
        self::assertSame($playingJob, $result->playing->jobId);
        self::assertSame('playing', $result->playing->state);
        self::assertSame('FC123', $result->playing->flightNumber);
        self::assertSame('check_in_opening', $result->playing->announcementType);

        // Waiting keeps queue order (FIFO by the queued fact).
        self::assertSame([$waitingJobA, $waitingJobB], array_map(
            static fn ($row) => $row->jobId,
            $result->waiting,
        ));

        // Recent is newest first; the failure carries its reason.
        self::assertSame([$failedJob, $completedJob], array_map(
            static fn ($row) => $row->jobId,
            $result->recent,
        ));
        self::assertSame('failed', $result->recent[0]->state);
        self::assertSame('player exited 1', $result->recent[0]->failureReason);
        self::assertSame('completed', $result->recent[1]->state);
    }

    public function testCancelledJobMovesToRecent(): void
    {
        $cancelledAnnouncement = $this->announcement();
        $cancelledJob = Uuid::v7()->toRfc4122();

        $receipts = [
            $this->receipt('queued', $cancelledAnnouncement, $cancelledJob, '09:00:00'),
            $this->receipt('cancelled', $cancelledAnnouncement, $cancelledJob, '09:00:10'),
        ];

        $result = $this->handler($receipts, [$cancelledAnnouncement])(new ListPlaybackQueueQuery());

        self::assertNull($result->playing);
        self::assertSame([], $result->waiting);
        self::assertCount(1, $result->recent);
        self::assertSame($cancelledJob, $result->recent[0]->jobId);
        self::assertSame('cancelled', $result->recent[0]->state);
        self::assertNull($result->recent[0]->failureReason);
    }

    public function testRecentIsLimited(): void
    {
        $receipts = [];
        $announcements = [];
        for ($i = 0; $i < 15; ++$i) {
            $announcement = $this->announcement();
            $announcements[] = $announcement;
            $jobId = Uuid::v7()->toRfc4122();
            $minute = str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            $receipts[] = $this->receipt('queued', $announcement, $jobId, "09:{$minute}:00");
            $receipts[] = $this->receipt('started', $announcement, $jobId, "09:{$minute}:01");
            $receipts[] = $this->receipt('completed', $announcement, $jobId, "09:{$minute}:30");
        }

        $result = $this->handler($receipts, $announcements)(new ListPlaybackQueueQuery(recentLimit: 10));

        self::assertNull($result->playing);
        self::assertSame([], $result->waiting);
        self::assertCount(10, $result->recent);
    }

    public function testEmptyReceiptsGiveEmptyScreen(): void
    {
        $result = $this->handler([], [])(new ListPlaybackQueueQuery());

        self::assertNull($result->playing);
        self::assertSame([], $result->waiting);
        self::assertSame([], $result->recent);
    }

    /**
     * @param list<PlaybackEventReceiptView> $receipts
     * @param list<Announcement>             $announcements
     */
    private function handler(array $receipts, array $announcements): ListPlaybackQueueHandler
    {
        $reader = new class($receipts) implements PlaybackEventReceiptReaderInterface {
            /** @param list<PlaybackEventReceiptView> $receipts */
            public function __construct(private readonly array $receipts)
            {
            }

            public function listReceivedSince(DateTimeImmutable $since): array
            {
                return $this->receipts;
            }
        };

        $byId = [];
        foreach ($announcements as $announcement) {
            $byId[$announcement->getId()->toRfc4122()] = $announcement;
        }
        $repository = $this->createStub(AnnouncementRepositoryInterface::class);
        $repository->method('findById')->willReturnCallback(
            static fn (Uuid $id) => $byId[$id->toRfc4122()] ?? null,
        );

        $lookup = $this->createStub(FlightDefinitionLookupInterface::class);
        $lookup->method('findById')->willReturn(
            new FlightDefinitionSnapshot(true, FlightDirection::Departure, 'FC123'),
        );

        return new ListPlaybackQueueHandler($reader, $repository, $lookup);
    }

    private function receipt(
        string $shortEvent,
        Announcement $announcement,
        string $jobId,
        string $time,
        ?string $reason = null,
    ): PlaybackEventReceiptView {
        return new PlaybackEventReceiptView(
            event: 'announcement_playback.'.$shortEvent,
            announcementId: $announcement->getId()->toRfc4122(),
            jobId: $jobId,
            receivedAt: new DateTimeImmutable('2026-07-10 '.$time),
            reason: $reason,
        );
    }

    private function announcement(): Announcement
    {
        return Announcement::createPrepared(
            AnnouncementType::CheckInOpening,
            Uuid::v7()->toRfc4122(),
            AnnouncementLanguages::fromCodes(LanguageCode::fromString('ro-MD')),
            [['id' => Uuid::v7()->toRfc4122(), 'code' => '1']],
            null,
            [[
                'languageCode' => 'ro-MD',
                'sortOrder' => 0,
                'items' => [['type' => 'audio_asset', 'audioAssetId' => Uuid::v7()->toRfc4122()]],
            ]],
            Uuid::v7()->toRfc4122(),
        );
    }
}
