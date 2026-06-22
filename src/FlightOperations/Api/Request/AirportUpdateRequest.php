<?php

declare(strict_types=1);

namespace App\FlightOperations\Api\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class AirportUpdateRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 160)]
        public string $name,
        #[Assert\NotBlank]
        #[Assert\Length(max: 120)]
        public string $cityName,
        #[Assert\NotBlank]
        #[Assert\Regex('/^[A-Za-z]{2}$/')]
        public string $countryCode,
    ) {
    }
}
