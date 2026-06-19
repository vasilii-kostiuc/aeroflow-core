<?php

declare(strict_types=1);

namespace App\FlightOperations\Infrastructure\Persistence\Doctrine;

use App\FlightOperations\Application\FlightDefinitionResult;
use App\FlightOperations\Application\ListFlightDefinitions\FlightDefinitionListQueryInterface;
use App\FlightOperations\Domain\Enum\FlightDirection;
use App\Shared\Application\Pagination\PaginatedResult;
use App\Shared\Application\Pagination\Pagination;
use App\Shared\Application\Pagination\PaginationMetadata;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

final readonly class DbalFlightDefinitionListQuery implements FlightDefinitionListQueryInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * @return PaginatedResult<FlightDefinitionResult>
     */
    public function search(
        ?bool $active,
        ?FlightDirection $direction,
        ?string $search,
        Pagination $pagination,
    ): PaginatedResult {
        $countQuery = $this->connection->createQueryBuilder()
            ->select('COUNT(*)')
            ->from('flight_definition', 'f');
        $itemsQuery = $this->connection->createQueryBuilder()
            ->select(
                'f.id',
                'f.flight_number',
                'f.direction',
                'f.origin_airport_code',
                'f.destination_airport_code',
                'f.active',
                'f.created_at',
                'f.updated_at',
            )
            ->from('flight_definition', 'f');

        foreach ([$countQuery, $itemsQuery] as $queryBuilder) {
            if ($active !== null) {
                $queryBuilder
                    ->andWhere('f.active = :active')
                    ->setParameter('active', $active, ParameterType::BOOLEAN);
            }

            if ($direction !== null) {
                $queryBuilder
                    ->andWhere('f.direction = :direction')
                    ->setParameter('direction', $direction->value);
            }

            if ($search !== null) {
                $queryBuilder
                    ->andWhere('UPPER(f.flight_number) LIKE :search')
                    ->setParameter('search', '%'.$search.'%');
            }
        }

        $totalItems = (int) $countQuery->executeQuery()->fetchOne();
        $rows = $itemsQuery
            ->orderBy('f.flight_number', 'ASC')
            ->addOrderBy('f.id', 'ASC')
            ->setFirstResult($pagination->offset())
            ->setMaxResults($pagination->limit)
            ->executeQuery()
            ->fetchAllAssociative();

        $items = array_map(
            static fn (array $row): FlightDefinitionResult => FlightDefinitionResult::fromRow($row),
            $rows,
        );

        return new PaginatedResult(
            items: $items,
            pagination: new PaginationMetadata(
                page: $pagination->page,
                limit: $pagination->limit,
                totalItems: $totalItems,
                totalPages: $pagination->totalPagesFor($totalItems),
            ),
        );
    }
}
