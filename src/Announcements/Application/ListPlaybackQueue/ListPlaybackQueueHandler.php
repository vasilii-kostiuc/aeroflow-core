<?php

declare(strict_types=1);

namespace App\Announcements\Application\ListPlaybackQueue;

use App\Announcements\Application\Playback\PlaybackEventReceiptReaderInterface;
use App\Announcements\Application\Playback\PlaybackEventReceiptView;
use App\Announcements\Application\Port\FlightOperations\FlightDefinitionLookupInterface;
use App\Announcements\Domain\Repository\AnnouncementRepositoryInterface;
use DateTimeImmutable;
use DateTimeInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

/**
 * Builds the dispatcher's queue view from the recorded playback facts (task 017).
 *
 * Playback stays the source of truth for the actual order; this is an eventual
 * read model derived from the Queued/Started/Completed/Failed receipts of task
 * 015/017, enriched with announcement details. No aggregate is loaded for writing
 * and no playback tables are read — receipts belong to this context.
 */
#[AsMessageHandler(bus: 'command.bus')]
final readonly class ListPlaybackQueueHandler
{
    public function __construct(
        private PlaybackEventReceiptReaderInterface $receipts,
        private AnnouncementRepositoryInterface $announcements,
        private FlightDefinitionLookupInterface $flightDefinitions,
    ) {
    }

    public function __invoke(ListPlaybackQueueQuery $query): PlaybackQueueResult
    {
        $receipts = $this->receipts->listReceivedSince(new DateTimeImmutable('today'));

        /** @var array<string,array{announcementId:string,queuedAt:?DateTimeImmutable,startedAt:?DateTimeImmutable,finishedAt:?DateTimeImmutable,failed:bool,cancelled:bool,interrupted:bool,reason:?string}> $jobs */
        $jobs = [];
        foreach ($receipts as $receipt) {
            $job = $jobs[$receipt->jobId] ?? [
                'announcementId' => $receipt->announcementId,
                'queuedAt' => null,
                'startedAt' => null,
                'finishedAt' => null,
                'failed' => false,
                'cancelled' => false,
                'interrupted' => false,
                'reason' => null,
            ];

            match ($receipt->event) {
                'announcement_playback.queued' => $job['queuedAt'] = $receipt->receivedAt,
                'announcement_playback.started' => $job['startedAt'] = $receipt->receivedAt,
                'announcement_playback.completed' => $job['finishedAt'] = $receipt->receivedAt,
                'announcement_playback.failed' => [
                    $job['finishedAt'] = $receipt->receivedAt,
                    $job['failed'] = true,
                    $job['reason'] = $receipt->reason,
                ],
                'announcement_playback.cancelled' => [
                    $job['finishedAt'] = $receipt->receivedAt,
                    $job['cancelled'] = true,
                ],
                'announcement_playback.interrupted' => [
                    $job['finishedAt'] = $receipt->receivedAt,
                    $job['interrupted'] = true,
                ],
                default => null,
            };

            $jobs[$receipt->jobId] = $job;
        }

        $playing = null;
        $waiting = [];
        $recent = [];

        foreach ($jobs as $jobId => $job) {
            $row = $this->row($jobId, $job);
            if ($row === null) {
                continue;
            }

            if ($job['finishedAt'] !== null) {
                $recent[] = [$job['finishedAt'], $row];
            } elseif ($job['startedAt'] !== null) {
                $playing = $row;
            } else {
                $waiting[] = [$job['queuedAt'], $row];
            }
        }

        // All current announcements share one priority (flight = 100), so queue
        // order approximates to FIFO by the moment the Queued fact arrived.
        usort($waiting, static fn (array $a, array $b): int => $a[0] <=> $b[0]);
        usort($recent, static fn (array $a, array $b): int => $b[0] <=> $a[0]);

        return new PlaybackQueueResult(
            playing: $playing,
            waiting: array_map(static fn (array $entry) => $entry[1], $waiting),
            recent: array_map(
                static fn (array $entry) => $entry[1],
                array_slice($recent, 0, max(1, min(50, $query->recentLimit))),
            ),
        );
    }

    /**
     * @param array{announcementId:string,queuedAt:?DateTimeImmutable,startedAt:?DateTimeImmutable,finishedAt:?DateTimeImmutable,failed:bool,cancelled:bool,interrupted:bool,reason:?string} $job
     */
    private function row(string $jobId, array $job): ?PlaybackQueueRowResult
    {
        $announcement = $this->announcements->findById(Uuid::fromString($job['announcementId']));
        if ($announcement === null) {
            return null;
        }

        $flight = $this->flightDefinitions->findById($announcement->getFlightDefinitionId());

        $state = match (true) {
            $job['failed'] => 'failed',
            $job['cancelled'] => 'cancelled',
            $job['interrupted'] => 'interrupted',
            $job['finishedAt'] !== null => 'completed',
            $job['startedAt'] !== null => 'playing',
            default => 'waiting',
        };

        return new PlaybackQueueRowResult(
            announcementId: $job['announcementId'],
            jobId: $jobId,
            flightNumber: $flight?->flightNumber,
            announcementType: $announcement->getType()->value,
            languages: $announcement->getLanguages()->toStrings(),
            checkInCounters: $announcement->getCheckInCounters(),
            gate: $announcement->getGate(),
            state: $state,
            queuedAt: $job['queuedAt']?->format(DateTimeInterface::ATOM),
            startedAt: $job['startedAt']?->format(DateTimeInterface::ATOM),
            finishedAt: $job['finishedAt']?->format(DateTimeInterface::ATOM),
            failureReason: $job['reason'],
        );
    }
}
