<?php

declare(strict_types=1);

namespace App\SystemStatus\Infrastructure\Integration;

use App\SystemStatus\Application\Port\ServiceProbeInterface;
use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

final readonly class ServiceProbe implements ServiceProbeInterface
{
    public function __construct(
        private Connection $connection,
        private HttpClientInterface $client,
        #[Autowire(env: 'TTS_BASE_URL')]
        private string $ttsBaseUrl,
    ) {
    }

    public function databaseReachable(): bool
    {
        try {
            $this->connection->executeQuery('SELECT 1');

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    public function textToSpeechHealthy(): bool
    {
        try {
            return 200 === $this->client->request('GET', rtrim($this->ttsBaseUrl, '/').'/health', [
                'timeout' => 2,
                'max_duration' => 3,
            ])->getStatusCode();
        } catch (ExceptionInterface) {
            return false;
        }
    }
}
