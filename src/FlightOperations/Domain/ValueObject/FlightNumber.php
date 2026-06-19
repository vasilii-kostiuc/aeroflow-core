<?php

declare(strict_types=1);

namespace App\FlightOperations\Domain\ValueObject;

use App\FlightOperations\Domain\Exception\InvalidFlightNumberException;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final class FlightNumber
{
    #[ORM\Column(name: 'flight_number', length: 7)]
    private string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function fromString(string $value): self
    {
        $normalized = strtoupper(trim($value));

        if (!preg_match('/^(?=[A-Z0-9]{2,3}\d{1,4}$)(?=[A-Z0-9]*[A-Z])[A-Z0-9]{2,3}\d{1,4}$/', $normalized)) {
            throw InvalidFlightNumberException::forValue($value);
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
