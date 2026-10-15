<?php

declare(strict_types=1);

namespace App\Tests\Application\Announcements;

use App\Announcements\Application\EventHandler\PublishAnnouncementPlaybackCancel;
use App\Announcements\Application\Playback\CancelAnnouncementPlayback;
use App\Announcements\Domain\Event\AnnouncementCancelled;
use App\Tests\Support\RecordingPlaybackCommandPublisher;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class PublishAnnouncementPlaybackCancelTest extends TestCase
{
    public function testPublishesNeutralCancelCommandForCancelledAnnouncement(): void
    {
        $announcementId = Uuid::v7()->toRfc4122();

        $publisher = new RecordingPlaybackCommandPublisher();
        $handler = new PublishAnnouncementPlaybackCancel($publisher);

        $handler(new AnnouncementCancelled(
            $announcementId,
            new DateTimeImmutable('2026-07-17T10:00:00+00:00'),
        ));

        self::assertCount(1, $publisher->commands);
        $message = $publisher->commands[0];
        self::assertInstanceOf(CancelAnnouncementPlayback::class, $message);

        self::assertTrue(Uuid::isValid($message->messageId));
        self::assertSame($announcementId, $message->correlationId);
        self::assertSame($announcementId, $message->announcementId);
        self::assertSame('2026-07-17T10:00:00+00:00', $message->occurredAt);
        self::assertSame('announcement_playback.cancel', $message->command);
        self::assertSame(1, $message->schemaVersion);
    }
}
