<?php

declare(strict_types=1);

namespace App\Tests\Functional\SystemStatus;

use App\Announcements\Infrastructure\Integration\Playback\PlaybackEventSerializer;
use App\SystemStatus\Application\Heartbeat\PlaybackHeartbeatReceived;
use App\Tests\Support\ActsAsUser;
use App\UserAccess\Domain\Enum\UserRole;
use DateTimeImmutable;
use DateTimeInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * The status screen end to end (task 034): a playback heartbeat arriving on
 * playback_events is decoded, stored, and reported; access follows the roles.
 */
final class SystemStatusApiTest extends WebTestCase
{
    use ActsAsUser;

    public function testAHeartbeatFromPlaybackShowsItsQueuesAsServed(): void
    {
        $client = static::createClient();
        $this->actAs($client);

        $envelope = static::getContainer()->get(PlaybackEventSerializer::class)->decode([
            'body' => json_encode([
                'event' => 'playback.heartbeat',
                'messageId' => 'c0ffee00-0000-7000-8000-000000000000',
                'occurredAt' => new DateTimeImmutable()->format(DateTimeInterface::ATOM),
                'schemaVersion' => 1,
                'transports' => ['emergency_commands'],
                'pendingJobs' => 0,
                'playingSince' => null,
            ], JSON_THROW_ON_ERROR),
            'headers' => [],
        ]);
        self::assertInstanceOf(PlaybackHeartbeatReceived::class, $envelope->getMessage());
        static::getContainer()->get(MessageBusInterface::class)->dispatch($envelope->getMessage());

        $client->request('GET', '/api/v1/admin/system-status');

        self::assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR)['data'];
        $byKey = array_column($data['components'], 'status', 'key');
        self::assertSame('ok', $byKey['playback_emergency']);
        self::assertSame('ok', $byKey['database']);
        self::assertArrayHasKey('agent', $byKey);
        self::assertContains($data['overall'], ['ok', 'warning', 'down', 'unknown']);
    }

    public function testDispatcherSeesAlertsButNotTheAdminScreen(): void
    {
        $client = static::createClient();
        $this->actAs($client, UserRole::Dispatcher);

        $client->request('GET', '/api/v1/admin/system-status');
        self::assertResponseStatusCodeSame(403);

        $client->request('GET', '/api/v1/dispatcher/system-alerts');
        self::assertResponseIsSuccessful();
        $alerts = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR)['data'];
        foreach ($alerts as $alert) {
            self::assertArrayHasKey('key', $alert);
            self::assertNotSame('', $alert['message']);
        }
    }
}
