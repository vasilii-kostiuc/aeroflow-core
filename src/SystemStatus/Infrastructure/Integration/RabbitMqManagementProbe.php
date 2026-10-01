<?php

declare(strict_types=1);

namespace App\SystemStatus\Infrastructure\Integration;

use App\SystemStatus\Application\Port\MessageBrokerProbeInterface;
use App\SystemStatus\Application\Port\QueueSnapshot;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Reads queue depths and subscriptions from the RabbitMQ management API
 * (`RABBITMQ_MANAGEMENT_URL`, credentials in the URL). Short timeout: the status
 * screen must answer even when the broker hangs.
 */
final readonly class RabbitMqManagementProbe implements MessageBrokerProbeInterface
{
    public function __construct(
        private HttpClientInterface $client,
        #[Autowire(env: 'RABBITMQ_MANAGEMENT_URL')]
        private string $managementUrl,
    ) {
    }

    public function queues(): ?array
    {
        $parts = parse_url($this->managementUrl);
        if (false === $parts || !isset($parts['host'])) {
            return null;
        }
        $base = sprintf('%s://%s%s', $parts['scheme'] ?? 'http', $parts['host'], isset($parts['port']) ? ':'.$parts['port'] : '');

        try {
            $response = $this->client->request('GET', $base.'/api/queues/%2F', [
                'auth_basic' => [urldecode($parts['user'] ?? 'guest'), urldecode($parts['pass'] ?? 'guest')],
                'timeout' => 2,
                'max_duration' => 3,
            ]);
            /** @var list<array<string,mixed>> $queues */
            $queues = $response->toArray();
        } catch (ExceptionInterface) {
            return null;
        }

        return array_map(
            static fn (array $queue): QueueSnapshot => new QueueSnapshot(
                (string) ($queue['name'] ?? ''),
                (int) ($queue['consumers'] ?? 0),
                (int) ($queue['messages_ready'] ?? 0),
            ),
            $queues,
        );
    }
}
