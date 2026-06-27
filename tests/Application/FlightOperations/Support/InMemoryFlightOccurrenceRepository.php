<?php

declare(strict_types=1);

namespace App\Tests\Application\FlightOperations\Support;

use App\FlightOperations\Domain\Entity\FlightOccurrence;
use App\FlightOperations\Domain\Enum\FlightOccurrenceSource;
use App\FlightOperations\Domain\Repository\FlightOccurrenceRepositoryInterface;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

final class InMemoryFlightOccurrenceRepository implements FlightOccurrenceRepositoryInterface
{
    /** @var array<string, FlightOccurrence> */
    private array $items = [];

    public int $saveCalls = 0;

    public function save(FlightOccurrence $occurrence): void
    {
        ++$this->saveCalls;
        $this->items[$occurrence->getId()->toRfc4122()] = $occurrence;
    }

    public function findById(Uuid $id): ?FlightOccurrence
    {
        return $this->items[$id->toRfc4122()] ?? null;
    }

    public function findOneByBusinessKey(
        Uuid $flightDefinitionId,
        DateTimeImmutable $operationalDate,
        FlightOccurrenceSource $source,
        int $sequenceNumber,
    ): ?FlightOccurrence {
        foreach ($this->items as $item) {
            if (
                $item->getFlightDefinitionId()->equals($flightDefinitionId)
                && $item->getOperationalDate()->format('Y-m-d') === $operationalDate->format('Y-m-d')
                && $item->getSource() === $source
                && $item->getSequenceNumber() === $sequenceNumber
            ) {
                return $item;
            }
        }

        return null;
    }

    public function findLatestManualForUpdate(
        Uuid $flightDefinitionId,
        DateTimeImmutable $operationalDate,
    ): ?FlightOccurrence {
        $latest = null;
        foreach ($this->items as $item) {
            if (
                $item->getFlightDefinitionId()->equals($flightDefinitionId)
                && $item->getOperationalDate()->format('Y-m-d') === $operationalDate->format('Y-m-d')
                && $item->getSource() === FlightOccurrenceSource::Manual
                && ($latest === null || $item->getSequenceNumber() > $latest->getSequenceNumber())
            ) {
                $latest = $item;
            }
        }

        return $latest;
    }

    public function add(FlightOccurrence $occurrence): void
    {
        $this->items[$occurrence->getId()->toRfc4122()] = $occurrence;
    }
}
