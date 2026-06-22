<?php

declare(strict_types=1);

namespace App\FlightOperations\Api\Request;

use App\Shared\Api\Request\SearchablePaginatedRequest;

final readonly class AirportListRequest extends SearchablePaginatedRequest
{
    public function __construct(
        public ?bool $active = null,
        ?string $search = null,
        int $page = 1,
        int $limit = 100,
    ) {
        parent::__construct($search, $page, $limit);
    }
}
