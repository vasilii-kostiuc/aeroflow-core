<?php

declare(strict_types=1);

namespace App\FlightOperations\Api\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class FlightOccurrenceListRequest
{
    public function __construct(
        #[Assert\Date]
        public ?string $operationalDate = null,
        #[Assert\Uuid]
        public ?string $flightDefinitionId = null,
        #[Assert\Choice(choices: ['departure', 'arrival'])]
        public ?string $direction = null,
        #[Assert\Choice(choices: ['scheduled', 'check_in_open', 'check_in_closed', 'boarding', 'arrival_announced', 'completed', 'cancelled'])]
        public ?string $status = null,
        #[Assert\Choice(choices: ['manual', 'schedule'])]
        public ?string $source = null,
        #[Assert\Positive]
        public int $page = 1,
        #[Assert\Range(min: 1, max: 100)]
        public int $limit = 20,
    ) {
    }
}
