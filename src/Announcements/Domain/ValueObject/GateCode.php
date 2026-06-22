<?php

declare(strict_types=1);

namespace App\Announcements\Domain\ValueObject;

use App\Announcements\Domain\Exception\InvalidGateCodeException;

final readonly class GateCode
{
    private function __construct(private string $value)
    {
    }

    public static function fromString(string $value): self
    {
        $normalized = strtoupper(trim((string) preg_replace('/\s+/', ' ', $value)));

        if (!preg_match('/^[A-Z0-9][A-Z0-9 \/-]{0,15}$/', $normalized)) {
            throw InvalidGateCodeException::forValue($value);
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
