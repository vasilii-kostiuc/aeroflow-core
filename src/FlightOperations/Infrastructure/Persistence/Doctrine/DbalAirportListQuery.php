<?php

declare(strict_types=1);

namespace App\FlightOperations\Infrastructure\Persistence\Doctrine;

use App\FlightOperations\Application\AirportResult;
use App\FlightOperations\Application\ListAirports\AirportListQueryInterface;
use App\Shared\Application\Pagination\PaginatedResult;
use App\Shared\Application\Pagination\Pagination;
use App\Shared\Application\Pagination\PaginationMetadata;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

final readonly class DbalAirportListQuery implements AirportListQueryInterface
{
    public function __construct(private Connection $connection)
    {
    }

    public function search(?bool $active, ?string $search, Pagination $pagination): PaginatedResult
    {
        $count = $this->connection->createQueryBuilder()->select('COUNT(*)')->from('airport', 'a');
        $items = $this->connection->createQueryBuilder()
            ->select('a.id', 'a.code', 'a.name', 'a.city_name', 'a.country_code', 'a.active', 'a.created_at', 'a.updated_at')
            ->from('airport', 'a');

        foreach ([$count, $items] as $query) {
            if ($active !== null) {
                $query->andWhere('a.active = :active')->setParameter('active', $active, ParameterType::BOOLEAN);
            }

            if ($search !== null) {
                $query
                    ->andWhere('UPPER(a.code) LIKE :search OR UPPER(a.name) LIKE :search OR UPPER(a.city_name) LIKE :search')
                    ->setParameter('search', '%'.mb_strtoupper($search).'%');
            }
        }

        $totalItems = (int) $count->executeQuery()->fetchOne();
        $rows = $items
            ->orderBy('a.city_name', 'ASC')
            ->addOrderBy('a.code', 'ASC')
            ->setFirstResult($pagination->offset())
            ->setMaxResults($pagination->limit)
            ->executeQuery()
            ->fetchAllAssociative();

        return new PaginatedResult(
            array_map(static fn (array $row): AirportResult => AirportResult::fromRow($row), $rows),
            new PaginationMetadata(
                $pagination->page,
                $pagination->limit,
                $totalItems,
                $pagination->totalPagesFor($totalItems),
            ),
        );
    }
}
