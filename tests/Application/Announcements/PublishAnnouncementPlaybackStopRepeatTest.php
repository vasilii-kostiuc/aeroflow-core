<?php

declare(strict_types=1);

namespace App\Tests\Application\Announcements;

use App\Announcements\Application\EventHandler\PublishAnnouncementPlaybackStopRepeat;
use App\Announcements\Application\Playback\CancelAnnouncementPlayback;
use App\Announcements\Application\Playback\PlaybackRequestPublisherInterface;
use App\Announcements\Application\Playback\RequestAnnouncementPlayback;
use App\Announcements\Application\Playback\StopAnnouncementPlayback;
use App\Announcements\Application\Playback\StopAnnouncementRepeat;
use App\Announcements\Domain\Event\AnnouncementRepeatEnded;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class PublishAnnouncementPlaybackStopRepeatTest extends TestCase
{
    public function testPublishesStopRepeatForEndedSeries(): void
    {
        $publisher = new RecordingStopRepeatPublisher();
        $announcementId = Uuid::v7()->toRfc4122();

        (new PublishAnnouncementPlaybackStopRepeat($publisher))(
            new AnnouncementRepeatEnded($announcementId, new DateTimeImmutable('2026-07-20T10:00:00+00:00')),
        );

        self::assertCount(1, $publisher->stopRepeats);
        $message = $publisher->stopRepeats[0];
        self::assertSame($announcementId, $message->announcementId);
        self::assertSame($announcementId, $message->correlationId);
        self::assertSame('announcement_playback.stop_repeat', $message->command);
    }
}

final class RecordingStopRepeatPublisher implements PlaybackRequestPublisherInterface
{
    /** @var list<StopAnnouncementRepeat> */
    public array $stopRepeats = [];

    public function publish(RequestAnnouncementPlayback $message): void
    {
    }

    public function publishCancel(CancelAnnouncementPlayback $message): void
    {
    }

    public function publishStop(StopAnnouncementPlayback $message): void
    {
    }

    public function publishStopRepeat(StopAnnouncementRepeat $message): void
    {
        $this->stopRepeats[] = $message;
    }
}
