<?php

declare(strict_types=1);

namespace App\FlightOperations\Application;

use App\FlightOperations\Domain\Entity\CheckInCounter;
use App\FlightOperations\Domain\Entity\Gate;

final readonly class OperationalResourceResult
{
    public function __construct(
        public string $id,
        public string $code,
        public string $displayName,
        public int $sortOrder,
        public bool $active,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }

    public static function fromCheckInCounter(CheckInCounter $counter): self
    {
        return new self(
            $counter->getId()->toRfc4122(),
            $counter->getCode()->toString(),
            $counter->getDisplayName(),
            $counter->getSortOrder(),
            $counter->isActive(),
            $counter->getCreatedAt()->format(DATE_ATOM),
            $counter->getUpdatedAt()->format(DATE_ATOM),
        );
    }

    public static function fromGate(Gate $gate): self
    {
        return new self(
            $gate->getId()->toRfc4122(),
            $gate->getCode()->toString(),
            $gate->getDisplayName(),
            $gate->getSortOrder(),
            $gate->isActive(),
            $gate->getCreatedAt()->format(DATE_ATOM),
            $gate->getUpdatedAt()->format(DATE_ATOM),
        );
    }
}
