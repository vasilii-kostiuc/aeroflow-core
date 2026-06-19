<?php

declare(strict_types=1);

namespace App\Shared\Api\Request;

use Symfony\Component\Validator\Constraints as Assert;

abstract readonly class SearchablePaginatedRequest
{
    public function __construct(
        public ?string $search = null,
        #[Assert\Positive]
        public int $page = 1,
        #[Assert\Range(min: 1, max: 100)]
        public int $limit = 20,
    ) {
    }
}
