<?php

declare(strict_types=1);

namespace App\Announcements\Application\ListPlaybackQueue;

use App\Announcements\Application\Playback\PlaybackEventReceiptReaderInterface;
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
 * read model derived from the Queued/Started/Completed/Failed/Cancelled/Interrupted
 * /Rescheduled receipts, enriched with announcement details. No aggregate is loaded
 * for writing and no playback tables are read — receipts belong to this context.
 *
 * The state of a row is decided by its **last** receipt, not by accumulating flags
 * (task 023). A repeatable job reuses one jobId for its whole series and alternates
 * started -> rescheduled -> started, so an accumulated `finishedAt` would pin the row
 * to the recent section forever and a later `started` could never lift it back.
 */
#[AsMessageHandler(bus: 'command.bus')]
final readonly class ListPlaybackQueueHandler
{
    /** Facts that end a job for good; anything else leaves it live in the queue. */
    private const array TERMINAL_EVENTS = [
        'announcement_playback.completed',
        'announcement_playback.failed',
        'announcement_playback.cancelled',
        'announcement_playback.interrupted',
    ];

    public function __construct(
        private PlaybackEventReceiptReaderInterface $receipts,
        private AnnouncementRepositoryInterface $announcements,
        private FlightDefinitionLookupInterface $flightDefinitions,
    ) {
    }

    public function __invoke(ListPlaybackQueueQuery $query): PlaybackQueueResult
    {
        // Receipts arrive ordered (receivedAt ASC, id ASC), so folding them in order
        // leaves `lastEvent` holding the latest fact known about each job.
        /** @var array<string,array{announcementId:string,lastEvent:string,queuedAt:?DateTimeImmutable,startedAt:?DateTimeImmutable,finishedAt:?DateTimeImmutable,reason:?string,nextAt:?string}> $jobs */
        $jobs = [];
        foreach ($this->receipts->listReceivedSince(new DateTimeImmutable('today')) as $receipt) {
            $job = $jobs[$receipt->jobId] ?? [
                'announcementId' => $receipt->announcementId,
                'lastEvent' => $receipt->event,
                'queuedAt' => null,
                'startedAt' => null,
                'finishedAt' => null,
                'reason' => null,
                'nextAt' => null,
            ];

            $job['lastEvent'] = $receipt->event;
            $job['finishedAt'] = in_array($receipt->event, self::TERMINAL_EVENTS, true)
                ? $receipt->receivedAt
                : null;

            match ($receipt->event) {
                'announcement_playback.queued' => $job['queuedAt'] = $receipt->receivedAt,
                'announcement_playback.started' => $job['startedAt'] = $receipt->receivedAt,
                'announcement_playback.failed' => $job['reason'] = $receipt->reason,
                'announcement_playback.rescheduled' => $job['nextAt'] = $receipt->nextAt,
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

            match ($row->state) {
                'playing' => $playing = $row,
                'waiting' => $waiting[] = [$job['queuedAt'], $row],
                // A resting repeat series queues by the moment it will sound again,
                // not by the moment its series was originally queued.
                'rescheduled' => $waiting[] = [$this->parse($job['nextAt']) ?? $job['queuedAt'], $row],
                default => $recent[] = [$job['finishedAt'], $row],
            };
        }

        // All current announcements share one priority (flight = 100), so queue
        // order approximates to FIFO by the moment the row became due.
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
     * @param array{announcementId:string,lastEvent:string,queuedAt:?DateTimeImmutable,startedAt:?DateTimeImmutable,finishedAt:?DateTimeImmutable,reason:?string,nextAt:?string} $job
     */
    private function row(string $jobId, array $job): ?PlaybackQueueRowResult
    {
        $announcement = $this->announcements->findById(Uuid::fromString($job['announcementId']));
        if ($announcement === null) {
            return null;
        }

        $flight = $this->flightDefinitions->findById($announcement->getFlightDefinitionId());

        $state = match ($job['lastEvent']) {
            'announcement_playback.completed' => 'completed',
            'announcement_playback.failed' => 'failed',
            'announcement_playback.cancelled' => 'cancelled',
            'announcement_playback.interrupted' => 'interrupted',
            'announcement_playback.started' => 'playing',
            'announcement_playback.rescheduled' => 'rescheduled',
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
            nextAt: $state === 'rescheduled' ? $job['nextAt'] : null,
        );
    }

    /** The contract sends nextAt as an ATOM string; an unparsable value just sorts last. */
    private function parse(?string $moment): ?DateTimeImmutable
    {
        if ($moment === null) {
            return null;
        }

        return DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, $moment) ?: null;
    }
}
