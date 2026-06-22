<?php

declare(strict_types=1);

namespace App\Announcements\Application;

use App\Announcements\Domain\Entity\Announcement;

final readonly class AnnouncementResult
{
    /**
     * @param list<string> $languages
     */
    public function __construct(
        public string $id,
        public string $type,
        public string $flightDefinitionId,
        public ?int $checkInCounterStart,
        public ?int $checkInCounterEnd,
        public ?string $gateCode,
        public array $languages,
        public string $status,
        public string $createdAt,
        public ?string $cancelledAt,
    ) {
    }

    public static function fromEntity(Announcement $announcement): self
    {
        $range = $announcement->getCheckInCounterRange();

        return new self(
            $announcement->getId()->toRfc4122(),
            $announcement->getType()->value,
            $announcement->getFlightDefinitionId()->toRfc4122(),
            $range?->start(),
            $range?->end(),
            $announcement->getGateCode()?->toString(),
            $announcement->getLanguages()->toStrings(),
            $announcement->getStatus()->value,
            $announcement->getCreatedAt()->format(DATE_RFC3339),
            $announcement->getCancelledAt()?->format(DATE_RFC3339),
        );
    }
}
