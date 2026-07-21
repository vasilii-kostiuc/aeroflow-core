<?php

declare(strict_types=1);

namespace App\Tests\Application\Announcements;

use App\Announcements\Application\EventHandler\PublishAnnouncementPlaybackCancel;
use App\Announcements\Application\Playback\CancelAnnouncementPlayback;
use App\Announcements\Application\Playback\PlaybackRequestPublisherInterface;
use App\Announcements\Application\Playback\StopAnnouncementPlayback;
use App\Announcements\Application\Playback\RequestAnnouncementPlayback;
use App\Announcements\Domain\Event\AnnouncementCancelled;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class PublishAnnouncementPlaybackCancelTest extends TestCase
{
    public function testPublishesNeutralCancelCommandForCancelledAnnouncement(): void
    {
        $announcementId = Uuid::v7()->toRfc4122();

        $publisher = new RecordingCancelPublisher();
        $handler = new PublishAnnouncementPlaybackCancel($publisher);

        $handler(new AnnouncementCancelled(
            $announcementId,
            new DateTimeImmutable('2026-07-17T10:00:00+00:00'),
        ));

        self::assertCount(1, $publisher->cancels);
        $message = $publisher->cancels[0];

        self::assertTrue(Uuid::isValid($message->messageId));
        self::assertSame($announcementId, $message->correlationId);
        self::assertSame($announcementId, $message->announcementId);
        self::assertSame('2026-07-17T10:00:00+00:00', $message->occurredAt);
        self::assertSame('announcement_playback.cancel', $message->command);
        self::assertSame(1, $message->schemaVersion);
    }
}

final class RecordingCancelPublisher implements PlaybackRequestPublisherInterface
{
    /** @var list<CancelAnnouncementPlayback> */
    public array $cancels = [];

    public function publish(RequestAnnouncementPlayback $message): void
    {
    }

    public function publishCancel(CancelAnnouncementPlayback $message): void
    {
        $this->cancels[] = $message;
    }

    public function publishStopRepeat(\App\Announcements\Application\Playback\StopAnnouncementRepeat $message): void
    {
    }

    public function publishStop(StopAnnouncementPlayback $message): void
    {
    }
}
