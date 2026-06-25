<?php

declare(strict_types=1);

namespace App\FlightOperations\Infrastructure\Persistence\Doctrine;

use App\FlightOperations\Application\FlightOccurrenceResult;
use App\FlightOperations\Application\ListFlightOccurrences\FlightOccurrenceListQueryInterface;
use App\Shared\Application\Pagination\PaginatedResult;
use App\Shared\Application\Pagination\Pagination;
use App\Shared\Application\Pagination\PaginationMetadata;
use Doctrine\DBAL\Connection;

final readonly class DbalFlightOccurrenceListQuery implements FlightOccurrenceListQueryInterface
{
    public function __construct(private Connection $connection)
    {
    }

    public function search(
        ?string $operationalDate,
        ?string $flightDefinitionId,
        ?string $direction,
        ?string $status,
        ?string $source,
        Pagination $pagination,
    ): PaginatedResult {
        $countQuery = $this->connection->createQueryBuilder()
            ->select('COUNT(*)')
            ->from('flight_occurrence', 'o');
        $itemsQuery = $this->baseItemsQuery();

        foreach ([$countQuery, $itemsQuery] as $queryBuilder) {
            if ($operationalDate !== null) {
                $queryBuilder->andWhere('o.operational_date = :operationalDate')
                    ->setParameter('operationalDate', $operationalDate);
            }
            if ($flightDefinitionId !== null) {
                $queryBuilder->andWhere('o.flight_definition_id = :flightDefinitionId')
                    ->setParameter('flightDefinitionId', $flightDefinitionId);
            }
            if ($direction !== null) {
                $queryBuilder->andWhere('o.direction = :direction')
                    ->setParameter('direction', $direction);
            }
            if ($status !== null) {
                $queryBuilder->andWhere('o.status = :status')
                    ->setParameter('status', $status);
            }
            if ($source !== null) {
                $queryBuilder->andWhere('o.source = :source')
                    ->setParameter('source', $source);
            }
        }

        $totalItems = (int) $countQuery->executeQuery()->fetchOne();
        $rows = $itemsQuery
            ->orderBy('o.operational_date', 'DESC')
            ->addOrderBy('o.flight_number_snapshot', 'ASC')
            ->addOrderBy('o.sequence_number', 'ASC')
            ->setFirstResult($pagination->offset())
            ->setMaxResults($pagination->limit)
            ->executeQuery()
            ->fetchAllAssociative();

        return new PaginatedResult(
            items: array_map(FlightOccurrenceResult::fromRow(...), $rows),
            pagination: new PaginationMetadata(
                page: $pagination->page,
                limit: $pagination->limit,
                totalItems: $totalItems,
                totalPages: $pagination->totalPagesFor($totalItems),
            ),
        );
    }

    private function baseItemsQuery(): \Doctrine\DBAL\Query\QueryBuilder
    {
        return $this->connection->createQueryBuilder()
            ->select(
                'o.id',
                'o.flight_definition_id',
                'o.source',
                'o.direction',
                'o.operational_date',
                'o.sequence_number',
                'o.flight_number_snapshot',
                'o.origin_airport_code_snapshot',
                'o.destination_airport_code_snapshot',
                'o.status',
                'o.check_in_counters',
                'o.gate',
                'o.last_announcement_id',
                'o.created_at',
                'o.updated_at',
                'o.completed_at',
                'o.cancelled_at',
            )
            ->from('flight_occurrence', 'o');
    }
}
