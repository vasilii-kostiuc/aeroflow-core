<?php

declare(strict_types=1);

namespace App\Shared\Application\Pagination;

final readonly class PaginationMetadata
{
    public function __construct(
        public int $page,
        public int $limit,
        public int $totalItems,
        public int $totalPages,
    ) {
    }
}
