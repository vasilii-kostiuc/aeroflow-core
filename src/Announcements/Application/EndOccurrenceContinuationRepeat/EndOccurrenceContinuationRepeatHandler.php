<?php

declare(strict_types=1);

namespace App\Announcements\Application\EndOccurrenceContinuationRepeat;

use App\Announcements\Domain\Repository\AnnouncementRepositoryInterface;
use App\Shared\Application\Event\DomainEventPublisher;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

/**
 * Ends the active continuation repeat series of an occurrence (task 020).
 *
 * Announcements owns the continuation announcement and the AnnouncementRepeatEnded
 * fact; Flight Operations only asks (through its port) for the series to end. Runs in
 * the same transaction as the closing action, so the end commits atomically with the
 * occurrence transition. Idempotent: no active series, or an already-ended one, is a
 * no-op — endRepeat() records the event only on the first transition, so the outbound
 * StopAnnouncementRepeat is never duplicated.
 */
#[AsMessageHandler(bus: 'command.bus')]
final readonly class EndOccurrenceContinuationRepeatHandler
{
    public function __construct(
        private AnnouncementRepositoryInterface $announcements,
        private DomainEventPublisher $events,
    ) {
    }

    public function __invoke(EndOccurrenceContinuationRepeatCommand $command): void
    {
        if (!Uuid::isValid($command->flightOccurrenceId)) {
            return;
        }

        $announcement = $this->announcements->findActiveContinuationByOccurrenceId(
            Uuid::fromString($command->flightOccurrenceId),
        );
        if ($announcement === null || !$announcement->endRepeat()) {
            return;
        }

        $this->announcements->save($announcement);
        $this->events->publish(...$announcement->pullEvents());
    }
}
