<?php

declare(strict_types=1);

namespace App\FlightOperations\Api\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class DispatcherFlightOccurrenceListRequest
{
    public function __construct(
        #[Assert\Date]
        public ?string $operationalDate = null,
        #[Assert\Choice(choices: ['check_in_opening', 'check_in_closing', 'boarding_invitation', 'arrival'])]
        public ?string $announcementType = null,
        #[Assert\Choice(choices: ['departure', 'arrival'])]
        public ?string $direction = null,
        public bool $includeUnavailable = false,
    ) {
    }
}
