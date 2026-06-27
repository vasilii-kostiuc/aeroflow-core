<?php

declare(strict_types=1);

namespace App\Tests\Integration\Announcements\Infrastructure\Integration\Playback;

use App\Announcements\Application\Playback\PlaybackRequestPublisherInterface;
use App\Announcements\Application\Playback\RequestAnnouncementPlayback;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Symfony\Component\Uid\Uuid;

/**
 * Confirms the publisher routes RequestAnnouncementPlayback onto the async (outbound)
 * transport. In the test environment the transport is in-memory, so no RabbitMQ is
 * needed; publishing also must not require a database (it happens post-commit).
 */
final class PlaybackRequestRoutingTest extends KernelTestCase
{
    public function testPublisherRoutesMessageToAsyncTransport(): void
    {
        self::bootKernel();
        $publisher = self::getContainer()->get(PlaybackRequestPublisherInterface::class);
        $transport = self::getContainer()->get('messenger.transport.async');
        self::assertInstanceOf(PlaybackRequestPublisherInterface::class, $publisher);
        self::assertInstanceOf(InMemoryTransport::class, $transport);

        $announcementId = Uuid::v7()->toRfc4122();
        $publisher->publish(new RequestAnnouncementPlayback(
            messageId: Uuid::v7()->toRfc4122(),
            correlationId: $announcementId,
            announcementId: $announcementId,
            type: 'check_in_opening',
            priority: 100,
            audioSequence: [[
                'languageCode' => 'ro-MD',
                'sortOrder' => 0,
                'items' => [['type' => 'audio_asset', 'audioAssetId' => Uuid::v7()->toRfc4122()]],
            ]],
            repeatRule: null,
            occurredAt: '2026-06-26T10:00:00+00:00',
        ));

        $sent = $transport->getSent();
        self::assertCount(1, $sent);

        $message = $sent[0]->getMessage();
        self::assertInstanceOf(RequestAnnouncementPlayback::class, $message);
        self::assertSame($announcementId, $message->announcementId);
        self::assertSame(100, $message->priority);
        self::assertSame(1, $message->schemaVersion);
    }
}
