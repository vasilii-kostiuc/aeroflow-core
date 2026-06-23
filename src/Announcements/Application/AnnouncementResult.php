<?php

declare(strict_types=1);

namespace App\Announcements\Application;

use App\Announcements\Domain\Entity\Announcement;

final readonly class AnnouncementResult
{
    /**
     * @param list<array{id:string,code:string}> $checkInCounters
     * @param array{id:string,code:string}|null  $gate
     * @param list<string>                       $languages
     * @param list<array<string,mixed>>          $audioSequence
     */
    public function __construct(
        public string $id,
        public string $type,
        public string $flightDefinitionId,
        public array $checkInCounters,
        public ?array $gate,
        public array $languages,
        public array $audioSequence,
        public string $status,
        public string $createdAt,
        public ?string $cancelledAt,
    ) {
    }

    public static function fromEntity(Announcement $announcement): self
    {
        return new self(
            $announcement->getId()->toRfc4122(),
            $announcement->getType()->value,
            $announcement->getFlightDefinitionId()->toRfc4122(),
            $announcement->getCheckInCounters(),
            $announcement->getGate(),
            $announcement->getLanguages()->toStrings(),
            $announcement->getAudioSequence(),
            $announcement->getStatus()->value,
            $announcement->getCreatedAt()->format(DATE_RFC3339),
            $announcement->getCancelledAt()?->format(DATE_RFC3339),
        );
    }
}
