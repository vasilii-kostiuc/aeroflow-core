<?php

declare(strict_types=1);

namespace App\Announcements\Application\Playback;

use DateTimeImmutable;

/**
 * Read side of the recorded playback facts, feeding the dispatcher's queue screen
 * (task 017). Local CQRS read inside the Announcements context.
 */
interface PlaybackEventReceiptReaderInterface
{
    /**
     * Receipts received since the given moment, oldest first.
     *
     * @return list<PlaybackEventReceiptView>
     */
    public function listReceivedSince(DateTimeImmutable $since): array;
}
