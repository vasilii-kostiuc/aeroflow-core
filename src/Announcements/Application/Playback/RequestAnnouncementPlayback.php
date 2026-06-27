<?php

declare(strict_types=1);

namespace App\Announcements\Application\Playback;

/**
 * Integration command published by the Announcements context to aeroflow-playback
 * after a flight announcement has been created and its local transaction committed.
 *
 * Public, versioned, neutral contract: it carries only the prepared audio sequence
 * and scalars, never aggregates, templates, prompts, gates or counters. Playback
 * turns it into a PlaybackJob and never learns about flights.
 */
final readonly class RequestAnnouncementPlayback
{
    public const int SCHEMA_VERSION = 1;

    /**
     * @param list<array{languageCode:string,sortOrder:int,items:list<array<string,mixed>>}> $audioSequence
     * @param array<string,mixed>|null                                                       $repeatRule
     */
    public function __construct(
        public string $messageId,
        public string $correlationId,
        public string $announcementId,
        public string $type,
        public int $priority,
        public array $audioSequence,
        public ?array $repeatRule,
        public string $occurredAt,
        public int $schemaVersion = self::SCHEMA_VERSION,
    ) {
    }
}
