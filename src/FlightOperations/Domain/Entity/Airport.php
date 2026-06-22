<?php

declare(strict_types=1);

namespace App\FlightOperations\Domain\Entity;

use App\FlightOperations\Domain\Exception\InvalidAirportDetailsException;
use App\FlightOperations\Domain\ValueObject\AirportCode;
use App\Shared\Domain\AggregateRoot;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'airport')]
#[ORM\UniqueConstraint(name: 'UNIQ_AIRPORT_CODE', columns: ['code'])]
class Airport extends AggregateRoot
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\Embedded(class: AirportCode::class, columnPrefix: false)]
    private AirportCode $code;

    #[ORM\Column(length: 160)]
    private string $name;

    #[ORM\Column(name: 'city_name', length: 120)]
    private string $cityName;

    #[ORM\Column(name: 'country_code', length: 2)]
    private string $countryCode;

    #[ORM\Column]
    private bool $active;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    #[ORM\Column]
    private DateTimeImmutable $updatedAt;

    private function __construct()
    {
    }

    public static function create(
        AirportCode $code,
        string $name,
        string $cityName,
        string $countryCode,
    ): self {
        $now = self::now();
        $airport = new self();
        $airport->id = Uuid::v7();
        $airport->code = $code;
        $airport->name = '';
        $airport->cityName = '';
        $airport->countryCode = '';
        $airport->active = true;
        $airport->createdAt = $now;
        $airport->updatedAt = $now;
        $airport->updateDetails($name, $cityName, $countryCode);

        return $airport;
    }

    public function updateDetails(string $name, string $cityName, string $countryCode): bool
    {
        $name = self::requiredText($name, 'Airport name');
        $cityName = self::requiredText($cityName, 'City name');
        $countryCode = strtoupper(trim($countryCode));

        if (!preg_match('/^[A-Z]{2}$/', $countryCode)) {
            throw InvalidAirportDetailsException::invalidCountryCode();
        }

        if ($this->name === $name && $this->cityName === $cityName && $this->countryCode === $countryCode) {
            return false;
        }

        $this->name = $name;
        $this->cityName = $cityName;
        $this->countryCode = $countryCode;
        $this->updatedAt = self::now();

        return true;
    }

    public function activate(): bool
    {
        if ($this->active) {
            return false;
        }

        $this->active = true;
        $this->updatedAt = self::now();

        return true;
    }

    public function deactivate(): bool
    {
        if (!$this->active) {
            return false;
        }

        $this->active = false;
        $this->updatedAt = self::now();

        return true;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getCode(): AirportCode
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCityName(): string
    {
        return $this->cityName;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    private static function requiredText(string $value, string $field): string
    {
        $value = trim($value);

        if ($value === '') {
            throw InvalidAirportDetailsException::empty($field);
        }

        return $value;
    }

    private static function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}
