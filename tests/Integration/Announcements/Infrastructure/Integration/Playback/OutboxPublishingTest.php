<?php

declare(strict_types=1);

namespace App\Tests\Integration\Announcements\Infrastructure\Integration\Playback;

use App\Announcements\Application\Playback\ActivateEmergencyMode;
use App\Announcements\Application\Playback\CancelAnnouncementPlayback;
use App\Announcements\Application\Playback\DeactivateEmergencyMode;
use App\Announcements\Application\Playback\PlaybackCommand;
use App\Announcements\Application\Playback\PlaybackCommandPublisherInterface;
use App\Announcements\Application\Playback\RequestAnnouncementPlayback;
use App\Announcements\Application\Playback\StopAnnouncementPlayback;
use App\Announcements\Application\Playback\StopAnnouncementRepeat;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;

/**
 * The playback publisher writes the wire message into the outbox (task 035) on the
 * queue its type belongs to (task 036), and the write belongs to whatever
 * transaction is open: it commits or rolls back with the use case.
 */
final class OutboxPublishingTest extends KernelTestCase
{
    private Connection $connection;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->connection = self::getContainer()->get(Connection::class);
    }

    /**
     * @return iterable<string, array{PlaybackCommand, string, string}>
     */
    public static function commands(): iterable
    {
        $announcementId = Uuid::v7()->toRfc4122();
        $occurredAt = '2026-10-15T10:00:00+00:00';

        yield 'request' => [self::request($announcementId), 'announcement_playback', 'announcement_playback.request'];
        yield 'cancel' => [
            new CancelAnnouncementPlayback(Uuid::v7()->toRfc4122(), $announcementId, $announcementId, $occurredAt),
            'announcement_playback',
            'announcement_playback.cancel',
        ];
        yield 'stop' => [StopAnnouncementPlayback::forAnnouncement($announcementId), 'announcement_playback', 'announcement_playback.stop'];
        yield 'stop repeat' => [
            new StopAnnouncementRepeat(Uuid::v7()->toRfc4122(), $announcementId, $announcementId, $occurredAt),
            'announcement_playback',
            'announcement_playback.stop_repeat',
        ];
        yield 'activate emergency' => [
            ActivateEmergencyMode::correlatedWith($announcementId, new DateTimeImmutable($occurredAt)),
            'emergency_commands',
            'emergency_mode.activate',
        ];
        yield 'deactivate emergency' => [DeactivateEmergencyMode::now(), 'emergency_commands', 'emergency_mode.deactivate'];
    }

    #[DataProvider('commands')]
    public function testEachCommandGoesToItsQueueAsTheContractBody(PlaybackCommand $command, string $queue, string $discriminator): void
    {
        self::getContainer()->get(PlaybackCommandPublisherInterface::class)->publish($command);

        $row = $this->rowFor($command);
        self::assertNotNull($row);
        self::assertSame($queue, $row['destination']);
        self::assertSame($discriminator, json_decode($row['body'], true, 512, JSON_THROW_ON_ERROR)['command']);
    }

    public function testTheWriteRollsBackWithTheTransaction(): void
    {
        $command = self::request(Uuid::v7()->toRfc4122());
        $this->connection->beginTransaction();
        self::getContainer()->get(PlaybackCommandPublisherInterface::class)->publish($command);
        $this->connection->rollBack();

        self::assertNull($this->rowFor($command));
    }

    /** @return array{destination: string, body: string}|null */
    private function rowFor(PlaybackCommand $command): ?array
    {
        self::assertObjectHasProperty('messageId', $command);
        $row = $this->connection->fetchAssociative(
            'SELECT destination, body FROM outbox_message WHERE body LIKE :id',
            ['id' => '%"messageId":"'.$command->messageId.'"%'],
        );

        return false === $row ? null : $row;
    }

    private static function request(string $announcementId): RequestAnnouncementPlayback
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
            occurredAt: '2026-10-15T10:00:00+00:00',
        );
    }
}
