<?php

declare(strict_types=1);

namespace App\Shared\Application\Pagination;

use App\Shared\Application\Pagination\Exception\InvalidPaginationException;

final readonly class Pagination
{
    private const int MIN_PAGE = 1;
    private const int MIN_LIMIT = 1;
    private const int MAX_LIMIT = 100;

    private function __construct(
        public int $page,
        public int $limit,
    ) {
    }

    public static function fromValues(int $page, int $limit): self
    {
        if ($page < self::MIN_PAGE) {
            throw InvalidPaginationException::invalidPage(self::MIN_PAGE);
        }

        if ($limit < self::MIN_LIMIT || $limit > self::MAX_LIMIT) {
            throw InvalidPaginationException::invalidLimit(self::MIN_LIMIT, self::MAX_LIMIT);
        }

        return new self($page, $limit);
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->limit;
    }

    public function totalPagesFor(int $totalItems): int
    {
        return $totalItems === 0 ? 0 : (int) ceil($totalItems / $this->limit);
    }
}
