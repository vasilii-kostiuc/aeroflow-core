<?php

declare(strict_types=1);

namespace App\Announcements\Infrastructure\Integration\Playback;

use App\Announcements\Application\Playback\PlaybackEventReceiptRecorderInterface;
use App\Announcements\Application\Playback\PlaybackIntegrationEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrinePlaybackEventReceiptRecorder implements PlaybackEventReceiptRecorderInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function recordOnce(PlaybackIntegrationEvent $event): bool
    {
        $existing = $this->entityManager
            ->getRepository(PlaybackEventReceipt::class)
            ->findOneBy(['messageId' => Uuid::fromString($event->messageId)]);
        if ($existing !== null) {
            return false;
        }

        $this->entityManager->persist(PlaybackEventReceipt::record(
            $event->messageId,
            $event->event,
            $event->announcementId,
            $event->jobId,
            $event->occurredAt,
            $event->reason,
            $event->nextAt,
        ));
        $this->entityManager->flush();

        return true;
    }
}
