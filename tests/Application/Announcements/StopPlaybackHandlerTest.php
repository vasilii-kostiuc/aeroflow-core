<?php

declare(strict_types=1);

namespace App\Tests\Application\Announcements;

use App\Announcements\Application\Playback\StopAnnouncementPlayback;
use App\Announcements\Application\StopPlayback\StopPlaybackCommand;
use App\Announcements\Application\StopPlayback\StopPlaybackHandler;
use App\Shared\Application\Uuid\Exception\InvalidUuidException;
use App\Shared\Application\Uuid\UuidParser;
use App\Tests\Support\RecordingPlaybackCommandPublisher;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class StopPlaybackHandlerTest extends TestCase
{
    public function testPublishesNeutralStopCommand(): void
    {
        $announcementId = Uuid::v7()->toRfc4122();
        $publisher = new RecordingPlaybackCommandPublisher();

        (new StopPlaybackHandler($publisher, new UuidParser()))(new StopPlaybackCommand($announcementId));

        self::assertCount(1, $publisher->commands);
        $message = $publisher->commands[0];
        self::assertInstanceOf(StopAnnouncementPlayback::class, $message);

        self::assertTrue(Uuid::isValid($message->messageId));
        self::assertSame($announcementId, $message->correlationId);
        self::assertSame($announcementId, $message->announcementId);
        // The body-level discriminator (ADR 002) must survive serialization: without
        // a public `command` property playback would misread the body as a request.
        self::assertSame('announcement_playback.stop', $message->command);
        self::assertArrayHasKey('command', get_object_vars($message));
        self::assertSame(1, $message->schemaVersion);
    }

    public function testRejectsMalformedAnnouncementIdBeforePublishing(): void
    {
        $publisher = new RecordingPlaybackCommandPublisher();
        $handler = new StopPlaybackHandler($publisher, new UuidParser());

        try {
            $handler(new StopPlaybackCommand('not-a-uuid'));
            self::fail('Expected '.InvalidUuidException::class);
        } catch (InvalidUuidException) {
        }

        self::assertSame([], $publisher->commands);
    }
}
