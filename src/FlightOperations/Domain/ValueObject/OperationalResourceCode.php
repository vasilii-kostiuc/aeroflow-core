<?php

declare(strict_types=1);

namespace App\FlightOperations\Domain\ValueObject;

use App\FlightOperations\Domain\Exception\InvalidOperationalResourceException;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final readonly class OperationalResourceCode
{
    #[ORM\Column(name: 'code', length: 16)]
    private string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function fromString(string $value): self
    {
        $normalized = strtoupper(trim((string) preg_replace('/\s+/', ' ', $value)));
        if (!preg_match('/^[A-Z0-9][A-Z0-9 -]{0,15}$/', $normalized)) {
            throw InvalidOperationalResourceException::invalidCode($value);
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
