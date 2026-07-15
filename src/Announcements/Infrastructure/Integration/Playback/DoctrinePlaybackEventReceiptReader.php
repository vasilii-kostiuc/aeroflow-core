<?php

declare(strict_types=1);

namespace App\Announcements\Infrastructure\Integration\Playback;

use App\Announcements\Application\Playback\PlaybackEventReceiptReaderInterface;
use App\Announcements\Application\Playback\PlaybackEventReceiptView;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrinePlaybackEventReceiptReader implements PlaybackEventReceiptReaderInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function listReceivedSince(DateTimeImmutable $since): array
    {
        /** @var list<PlaybackEventReceipt> $receipts */
        $receipts = $this->entityManager->createQueryBuilder()
            ->select('receipt')
            ->from(PlaybackEventReceipt::class, 'receipt')
            ->where('receipt.receivedAt >= :since')
            ->setParameter('since', $since)
            ->orderBy('receipt.receivedAt', 'ASC')
            ->addOrderBy('receipt.id', 'ASC')
            ->getQuery()
            ->getResult();

        return array_map(
            static fn (PlaybackEventReceipt $receipt): PlaybackEventReceiptView => new PlaybackEventReceiptView(
                event: $receipt->getEvent(),
                announcementId: $receipt->getAnnouncementId()->toRfc4122(),
                jobId: $receipt->getJobId()->toRfc4122(),
                receivedAt: $receipt->getReceivedAt(),
                reason: $receipt->getReason(),
            ),
            $receipts,
        );
    }
}
