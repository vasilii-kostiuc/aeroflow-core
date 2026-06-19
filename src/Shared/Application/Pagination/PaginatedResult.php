<?php

declare(strict_types=1);

namespace App\Shared\Application\Pagination;

/**
 * @template T
 */
final readonly class PaginatedResult
{
    /**
     * @param list<T> $items
     */
    public function __construct(
        public array $items,
        public PaginationMetadata $pagination,
    ) {
    }
}
