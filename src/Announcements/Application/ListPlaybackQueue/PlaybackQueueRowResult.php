<?php

declare(strict_types=1);

namespace App\Announcements\Application\ListPlaybackQueue;

/**
 * One row of the dispatcher's queue screen: a playback job described in the
 * language the dispatcher thinks in — flight, announcement type, parameters.
 */
final readonly class PlaybackQueueRowResult
{
    /**
     * @param list<string>                          $languages
     * @param list<array{id:string,code:string}>    $checkInCounters
     * @param array{id:string,code:string}|null     $gate
     */
    public function __construct(
        public string $announcementId,
        public string $jobId,
        public ?string $flightNumber,
        public string $announcementType,
        public array $languages,
        public array $checkInCounters,
        public ?array $gate,
        public string $state,
        public ?string $queuedAt,
        public ?string $startedAt,
        public ?string $finishedAt,
        public ?string $failureReason,
    ) {
    }
}
