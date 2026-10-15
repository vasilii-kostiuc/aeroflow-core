<?php

declare(strict_types=1);

namespace App\Tests\Application\Announcements;

use App\Announcements\Application\EventHandler\PublishAnnouncementPlaybackStopRepeat;
use App\Announcements\Application\Playback\StopAnnouncementRepeat;
use App\Announcements\Domain\Event\AnnouncementRepeatEnded;
use App\Tests\Support\RecordingPlaybackCommandPublisher;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class PublishAnnouncementPlaybackStopRepeatTest extends TestCase
{
    public function testPublishesStopRepeatForEndedSeries(): void
    {
        $publisher = new RecordingPlaybackCommandPublisher();
        $announcementId = Uuid::v7()->toRfc4122();

        (new PublishAnnouncementPlaybackStopRepeat($publisher))(
            new AnnouncementRepeatEnded($announcementId, new DateTimeImmutable('2026-07-20T10:00:00+00:00')),
        );

        self::assertCount(1, $publisher->commands);
        $message = $publisher->commands[0];
        self::assertInstanceOf(StopAnnouncementRepeat::class, $message);
        self::assertSame($announcementId, $message->announcementId);
        self::assertSame($announcementId, $message->correlationId);
        self::assertSame('announcement_playback.stop_repeat', $message->command);
    }
}
