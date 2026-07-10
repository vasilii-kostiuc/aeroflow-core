<?php

declare(strict_types=1);

namespace App\Announcements\Application\Playback;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handles inbound playback integration events. Idempotent by messageId: a
 * redelivered event is recognised by the recorder and simply acknowledged.
 */
#[AsMessageHandler]
final readonly class RecordPlaybackEventReceipt
{
    public function __construct(private PlaybackEventReceiptRecorderInterface $receipts)
    {
    }

    public function __invoke(PlaybackIntegrationEvent $event): void
    {
        $this->receipts->recordOnce($event);
    }
}
