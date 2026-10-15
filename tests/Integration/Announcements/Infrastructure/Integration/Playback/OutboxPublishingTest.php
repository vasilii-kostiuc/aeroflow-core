<?php

declare(strict_types=1);

namespace App\Tests\Integration\Announcements\Infrastructure\Integration\Playback;

use App\Announcements\Application\Playback\ActivateEmergencyMode;
use App\Announcements\Application\Playback\EmergencyModePublisherInterface;
use App\Announcements\Application\Playback\PlaybackRequestPublisherInterface;
use App\Announcements\Application\Playback\RequestAnnouncementPlayback;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;

/**
 * The playback publishers write the wire message into the outbox (task 035), and
 * the write belongs to whatever transaction is open: it commits or rolls back with
 * the use case.
 */
final class OutboxPublishingTest extends KernelTestCase
{
    private Connection $connection;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->connection = self::getContainer()->get(Connection::class);
    }

    public function testRequestIsWrittenAsTheContractBody(): void
    {
        $announcementId = Uuid::v7()->toRfc4122();
        self::getContainer()->get(PlaybackRequestPublisherInterface::class)->publish($this->request($announcementId));

        $row = $this->rowFor($announcementId);
        self::assertNotNull($row);
        self::assertSame('announcement_playback', $row['destination']);
        $body = json_decode($row['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('announcement_playback.request', $body['command']);
        self::assertSame(100, $body['priority']);
        self::assertSame(2, $body['schemaVersion']);
    }

    public function testEmergencyCommandsHaveTheirOwnDestination(): void
    {
        $announcementId = Uuid::v7()->toRfc4122();
        self::getContainer()->get(EmergencyModePublisherInterface::class)->publishActivate(
            ActivateEmergencyMode::correlatedWith($announcementId, new DateTimeImmutable()),
        );

        self::assertSame('emergency_commands', $this->rowFor($announcementId)['destination'] ?? null);
    }

    public function testTheWriteRollsBackWithTheTransaction(): void
    {
        $announcementId = Uuid::v7()->toRfc4122();
        $this->connection->beginTransaction();
        self::getContainer()->get(PlaybackRequestPublisherInterface::class)->publish($this->request($announcementId));
        $this->connection->rollBack();

        self::assertNull($this->rowFor($announcementId));
    }

    /** @return array{destination: string, body: string}|null */
    private function rowFor(string $announcementId): ?array
    {
        $row = $this->connection->fetchAssociative(
            'SELECT destination, body FROM outbox_message WHERE body LIKE :id',
            ['id' => '%'.$announcementId.'%'],
        );

        return false === $row ? null : $row;
    }

    private function request(string $announcementId): RequestAnnouncementPlayback
    {
        return new RequestAnnouncementPlayback(
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
            occurredAt: '2026-10-01T10:00:00+00:00',
        );
    }
}
