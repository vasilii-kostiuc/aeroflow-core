<?php

declare(strict_types=1);

namespace App\FlightOperations\Application;

use App\FlightOperations\Domain\Entity\Airport;
use DateTimeImmutable;

final readonly class AirportResult
{
    public function __construct(
        public string $id,
        public string $code,
        public string $name,
        public string $cityName,
        public string $countryCode,
        public bool $active,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }

    public static function fromEntity(Airport $airport): self
    {
        return new self(
            $airport->getId()->toRfc4122(),
            $airport->getCode()->toString(),
            $airport->getName(),
            $airport->getCityName(),
            $airport->getCountryCode(),
            $airport->isActive(),
            $airport->getCreatedAt()->format(DATE_RFC3339),
            $airport->getUpdatedAt()->format(DATE_RFC3339),
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            (string) $row['id'],
            (string) $row['code'],
            (string) $row['name'],
            (string) $row['city_name'],
            (string) $row['country_code'],
            filter_var($row['active'], FILTER_VALIDATE_BOOL),
            new DateTimeImmutable((string) $row['created_at'])->format(DATE_RFC3339),
            new DateTimeImmutable((string) $row['updated_at'])->format(DATE_RFC3339),
        );
    }
}
