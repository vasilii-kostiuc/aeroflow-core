<?php

declare(strict_types=1);

namespace App\Tests\Application\Announcements;

use App\Announcements\Application\Playback\PlaybackEventReceiptRecorderInterface;
use App\Announcements\Application\Playback\PlaybackIntegrationEvent;
use App\Announcements\Application\Playback\RecordPlaybackEventReceipt;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class RecordPlaybackEventReceiptTest extends TestCase
{
    public function testRecordsInboundPlaybackEvent(): void
    {
        $receipts = new InMemoryPlaybackEventReceiptRecorder();
        $handler = new RecordPlaybackEventReceipt($receipts);

        $event = $this->event('announcement_playback.started');
        $handler($event);

        self::assertSame([$event], $receipts->recorded);
    }

    public function testRedeliveredEventIsRecordedOnce(): void
    {
        $receipts = new InMemoryPlaybackEventReceiptRecorder();
        $handler = new RecordPlaybackEventReceipt($receipts);

        $event = $this->event('announcement_playback.completed');
        $handler($event);
        $handler($event);

        self::assertCount(1, $receipts->recorded);
    }

    private function event(string $name): PlaybackIntegrationEvent
    {
        return new PlaybackIntegrationEvent(
            event: $name,
            messageId: Uuid::v7()->toRfc4122(),
            correlationId: $announcementId = Uuid::v7()->toRfc4122(),
            announcementId: $announcementId,
            jobId: Uuid::v7()->toRfc4122(),
            occurredAt: '2026-07-09T12:00:00+00:00',
            schemaVersion: 1,
        );
    }
}

final class InMemoryPlaybackEventReceiptRecorder implements PlaybackEventReceiptRecorderInterface
{
    /** @var list<PlaybackIntegrationEvent> */
    public array $recorded = [];

    public function recordOnce(PlaybackIntegrationEvent $event): bool
    {
        foreach ($this->recorded as $existing) {
            if ($existing->messageId === $event->messageId) {
                return false;
            }
        }

        $this->recorded[] = $event;

        return true;
    }
}
