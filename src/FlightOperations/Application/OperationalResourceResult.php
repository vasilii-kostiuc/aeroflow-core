<?php

declare(strict_types=1);

namespace App\FlightOperations\Application;

use App\FlightOperations\Domain\Entity\CheckInCounter;
use App\FlightOperations\Domain\Entity\Gate;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'OperationalResourceResult',
    required: ['id', 'code', 'displayName', 'sortOrder', 'active', 'createdAt', 'updatedAt'],
)]
final readonly class OperationalResourceResult
{
    public function __construct(
        #[OA\Property(format: 'uuid')]
        public string $id,
        #[OA\Property(example: 'A12')]
        public string $code,
        #[OA\Property(example: 'Gate A12')]
        public string $displayName,
        #[OA\Property(minimum: 1)]
        public int $sortOrder,
        public bool $active,
        #[OA\Property(format: 'date-time')]
        public string $createdAt,
        #[OA\Property(format: 'date-time')]
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
