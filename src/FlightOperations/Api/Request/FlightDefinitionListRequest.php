<?php

declare(strict_types=1);

namespace App\FlightOperations\Api\Request;

use App\Shared\Api\Request\SearchablePaginatedRequest;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class FlightDefinitionListRequest extends SearchablePaginatedRequest
{
    public function __construct(
        public ?bool $active = null,
        #[Assert\Choice(choices: ['departure', 'arrival'])]
        public ?string $direction = null,
        ?string $search = null,
        int $page = 1,
        int $limit = 20,
    ) {
        parent::__construct($search, $page, $limit);
    }
}
