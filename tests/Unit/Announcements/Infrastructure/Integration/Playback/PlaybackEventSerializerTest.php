<?php

declare(strict_types=1);

namespace App\Tests\Unit\Announcements\Infrastructure\Integration\Playback;

use App\Announcements\Application\Playback\PlaybackIntegrationEvent;
use App\Announcements\Infrastructure\Integration\Playback\PlaybackEventSerializer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Symfony\Component\Uid\Uuid;

final class PlaybackEventSerializerTest extends TestCase
{
    public function testDecodesPlaybackEventFromContractJson(): void
    {
        $announcementId = Uuid::v7()->toRfc4122();
        $jobId = Uuid::v7()->toRfc4122();
        $messageId = Uuid::v7()->toRfc4122();

        $envelope = new PlaybackEventSerializer()->decode(['body' => json_encode([
            'event' => 'announcement_playback.started',
            'messageId' => $messageId,
            'correlationId' => $announcementId,
            'announcementId' => $announcementId,
            'jobId' => $jobId,
            'occurredAt' => '2026-07-09T12:00:00+00:00',
            'schemaVersion' => 1,
        ], JSON_THROW_ON_ERROR)]);

        $message = $envelope->getMessage();
        self::assertInstanceOf(PlaybackIntegrationEvent::class, $message);
        self::assertSame('announcement_playback.started', $message->event);
        self::assertSame($messageId, $message->messageId);
        self::assertSame($announcementId, $message->announcementId);
        self::assertSame($jobId, $message->jobId);
        self::assertSame(1, $message->schemaVersion);
    }

    public function testRejectsBodyWithoutEventDiscriminator(): void
    {
        $this->expectException(MessageDecodingFailedException::class);

        new PlaybackEventSerializer()->decode(['body' => json_encode([
            'messageId' => Uuid::v7()->toRfc4122(),
            'correlationId' => Uuid::v7()->toRfc4122(),
            'announcementId' => Uuid::v7()->toRfc4122(),
            'jobId' => Uuid::v7()->toRfc4122(),
        ], JSON_THROW_ON_ERROR)]);
    }

    public function testRejectsInvalidJson(): void
    {
        $this->expectException(MessageDecodingFailedException::class);

        new PlaybackEventSerializer()->decode(['body' => '{not json']);
    }
}
