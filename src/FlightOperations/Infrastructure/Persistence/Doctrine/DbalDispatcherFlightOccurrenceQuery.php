<?php

declare(strict_types=1);

namespace App\FlightOperations\Infrastructure\Persistence\Doctrine;

use App\FlightOperations\Application\Dispatcher\DispatcherFlightOccurrenceQueryInterface;
use App\FlightOperations\Application\Dispatcher\DispatcherFlightOccurrenceResult;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;

final readonly class DbalDispatcherFlightOccurrenceQuery implements DispatcherFlightOccurrenceQueryInterface
{
    public function __construct(private Connection $connection)
    {
    }

    public function search(
        ?string $operationalDate,
        ?string $announcementType,
        ?string $direction,
        bool $includeUnavailable,
    ): array {
        $query = $this->connection->createQueryBuilder()
            ->select(
                'o.id',
                'o.flight_definition_id',
                'o.flight_number_snapshot',
                'o.direction',
                'o.origin_airport_code_snapshot',
                'o.destination_airport_code_snapshot',
                'o.operational_date',
                'o.status',
                'f.active',
            )
            ->from('flight_occurrence', 'o')
            ->innerJoin('o', 'flight_definition', 'f', 'f.id = o.flight_definition_id')
            ->andWhere("o.status NOT IN ('completed', 'cancelled')");

        if ($operationalDate !== null) {
            $query->andWhere('o.operational_date = :operationalDate')
                ->setParameter('operationalDate', $operationalDate);
        }
        if ($direction !== null) {
            $query->andWhere('o.direction = :direction')
                ->setParameter('direction', $direction);
        }
        if ($announcementType !== null && !$includeUnavailable) {
            [$expectedDirection, $expectedStatus] = $this->eligibility($announcementType);
            $query->andWhere('o.direction = :eligibleDirection')
                ->andWhere('o.status = :eligibleStatus')
                ->setParameter('eligibleDirection', $expectedDirection)
                ->setParameter('eligibleStatus', $expectedStatus);
        }

        $rows = $query
            ->orderBy('o.operational_date', 'ASC')
            ->addOrderBy('o.flight_number_snapshot', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        return array_map(fn (array $row): DispatcherFlightOccurrenceResult => $this->map($row, $announcementType), $rows);
    }

    /** @param array<string,mixed> $row */
    private function map(array $row, ?string $announcementType): DispatcherFlightOccurrenceResult
    {
        $eligible = $announcementType === null || $this->isEligible($announcementType, $row['direction'], $row['status'], filter_var($row['active'], FILTER_VALIDATE_BOOL));

        return new DispatcherFlightOccurrenceResult(
            id: $row['id'],
            flightDefinitionId: $row['flight_definition_id'],
            flightNumber: $row['flight_number_snapshot'],
            direction: $row['direction'],
            airportCode: $row['direction'] === 'arrival' ? $row['origin_airport_code_snapshot'] : $row['destination_airport_code_snapshot'],
            airportName: $row['direction'] === 'arrival' ? $row['origin_airport_code_snapshot'] : $row['destination_airport_code_snapshot'],
            operationalDate: new DateTimeImmutable($row['operational_date'])->format('Y-m-d'),
            status: $row['status'],
            eligible: $eligible,
            unavailableReason: $eligible ? null : $this->unavailableReason($announcementType, $row['direction'], $row['status'], filter_var($row['active'], FILTER_VALIDATE_BOOL)),
            availableLanguages: [],
        );
    }

    /** @return array{0:string,1:string} */
    private function eligibility(string $announcementType): array
    {
        return match ($announcementType) {
            'check_in_opening' => ['departure', 'scheduled'],
            'check_in_closing' => ['departure', 'check_in_open'],
            'boarding_invitation' => ['departure', 'check_in_closed'],
            'arrival' => ['arrival', 'scheduled'],
            default => ['', ''],
        };
    }

    private function isEligible(string $announcementType, string $direction, string $status, bool $flightActive): bool
    {
        if (!$flightActive) {
            return false;
        }
        [$expectedDirection, $expectedStatus] = $this->eligibility($announcementType);

        return $direction === $expectedDirection && $status === $expectedStatus;
    }

    private function unavailableReason(?string $announcementType, string $direction, string $status, bool $flightActive): ?string
    {
        if (!$flightActive) {
            return 'Flight definition is inactive';
        }
        if ($announcementType === null) {
            return null;
        }
        [$expectedDirection, $expectedStatus] = $this->eligibility($announcementType);
        if ($direction !== $expectedDirection) {
            return 'Flight direction is incompatible with selected announcement';
        }

        return sprintf('Current status "%s" does not allow selected announcement', $status);
    }
}
