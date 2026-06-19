<?php

declare(strict_types=1);

namespace App\FlightOperations\Api\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class FlightDefinitionDetailsRequest
{
    public function __construct(
        #[Assert\NotBlank]
        public string $flightNumber,
        #[Assert\NotBlank]
        #[Assert\Choice(choices: ['departure', 'arrival'])]
        public string $direction,
        #[Assert\NotBlank]
        public string $originAirportCode,
        #[Assert\NotBlank]
        public string $destinationAirportCode,
    ) {
    }
}
