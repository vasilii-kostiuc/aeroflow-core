<?php

declare(strict_types=1);

namespace App\SystemStatus\Infrastructure\Persistence;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/** Storage row of the last heartbeat of one worker (service + its transports). */
#[ORM\Entity]
#[ORM\Table(name: 'system_heartbeat')]
class SystemHeartbeat
{
    /**
     * @param list<string>        $transports
     * @param array<string,mixed> $details
     */
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(length: 255)]
        private string $workerKey,
        #[ORM\Column(length: 32)]
        private string $service,
        #[ORM\Column(type: 'json')]
        private array $transports,
        #[ORM\Column]
        private DateTimeImmutable $lastSeenAt,
        #[ORM\Column(type: 'json')]
        private array $details,
    ) {
    }

    /**
     * @param array<string,mixed> $details
     */
    public function refresh(DateTimeImmutable $seenAt, array $details): void
    {
        if ($seenAt <= $this->lastSeenAt) {
            return;
        }
        $this->lastSeenAt = $seenAt;
        $this->details = $details;
    }

    public function getService(): string
    {
        return $this->service;
    }

    /** @return list<string> */
    public function getTransports(): array
    {
        return $this->transports;
    }

    public function getLastSeenAt(): DateTimeImmutable
    {
        return $this->lastSeenAt;
    }

    /** @return array<string,mixed> */
    public function getDetails(): array
    {
        return $this->details;
    }
}
