<?php

declare(strict_types=1);

namespace App\SystemStatus\Infrastructure\Persistence;

use App\SystemStatus\Application\Port\HeartbeatRecord;
use App\SystemStatus\Application\Port\HeartbeatStoreInterface;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineHeartbeatStore implements HeartbeatStoreInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function record(HeartbeatRecord $heartbeat): void
    {
        $transports = $heartbeat->transports;
        sort($transports);
        $key = $heartbeat->service.':'.implode(',', $transports);

        $row = $this->entityManager->find(SystemHeartbeat::class, $key);
        if (null === $row) {
            $this->entityManager->persist(new SystemHeartbeat($key, $heartbeat->service, $transports, $heartbeat->lastSeenAt, $heartbeat->details));
        } else {
            $row->refresh($heartbeat->lastSeenAt, $heartbeat->details);
        }
        $this->entityManager->flush();
    }

    public function all(): array
    {
        return array_map(
            static fn (SystemHeartbeat $row): HeartbeatRecord => new HeartbeatRecord(
                $row->getService(),
                $row->getTransports(),
                $row->getLastSeenAt(),
                $row->getDetails(),
            ),
            $this->entityManager->getRepository(SystemHeartbeat::class)->findAll(),
        );
    }
}
