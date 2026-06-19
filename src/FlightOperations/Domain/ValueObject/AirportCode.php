<?php

declare(strict_types=1);

namespace App\FlightOperations\Domain\ValueObject;

use App\FlightOperations\Domain\Exception\InvalidAirportCodeException;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final class AirportCode
{
    #[ORM\Column(name: 'code', length: 3)]
    private string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function fromString(string $value): self
    {
        $normalized = strtoupper(trim($value));

        if (!preg_match('/^[A-Z]{3}$/', $normalized)) {
            throw InvalidAirportCodeException::forValue($value);
        }

        return new self($normalized);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
