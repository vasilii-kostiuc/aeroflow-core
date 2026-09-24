<?php

declare(strict_types=1);

namespace App\Announcements\Infrastructure\Integration\Playback;

use App\Announcements\Application\Playback\EmergencyModeIntegrationEvent;
use App\Announcements\Application\Playback\EmergencyModeStateInterface;
use App\Announcements\Application\Playback\EmergencyModeView;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineEmergencyModeState implements EmergencyModeStateInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function apply(EmergencyModeIntegrationEvent $event): void
    {
        $record = $this->find() ?? EmergencyModeRecord::initial();
        // persist() on an already-managed record is a no-op; on a first report it
        // creates the singleton row.
        $this->entityManager->persist($record);
        $record->apply($event->isActive(), $event->occurredAt);
        $this->entityManager->flush();
    }

    public function current(): EmergencyModeView
    {
        $record = $this->find();
        if ($record === null) {
            return new EmergencyModeView(active: false, since: null);
        }

        return new EmergencyModeView(
            active: $record->isActive(),
            since: '' === $record->getOccurredAt() ? null : $record->getOccurredAt(),
        );
    }

    private function find(): ?EmergencyModeRecord
    {
        return $this->entityManager->find(
            EmergencyModeRecord::class,
            Uuid::fromString(EmergencyModeRecord::SINGLETON_ID),
        );
    }
}
