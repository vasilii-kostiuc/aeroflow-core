<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

use App\Shared\Domain\Exception\InvalidLanguageCodeException;

final readonly class LanguageCode
{
    private function __construct(private string $value)
    {
    }

    public static function fromString(string $value): self
    {
        $trimmed = trim($value);

        if (!preg_match('/^[A-Za-z]{2,8}(?:-[A-Za-z0-9]{1,8})*$/', $trimmed)) {
            throw InvalidLanguageCodeException::forValue($value);
        }

        $subtags = explode('-', $trimmed);
        $normalized = [strtolower(array_shift($subtags))];

        foreach ($subtags as $subtag) {
            $normalized[] = self::normalizeSubtag($subtag);
        }

        return new self(implode('-', $normalized));
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    private static function normalizeSubtag(string $subtag): string
    {
        if (4 === strlen($subtag) && ctype_alpha($subtag)) {
            return ucfirst(strtolower($subtag));
        }

        if (
            (2 === strlen($subtag) && ctype_alpha($subtag))
            || (3 === strlen($subtag) && ctype_digit($subtag))
        ) {
            return strtoupper($subtag);
        }

        return strtolower($subtag);
    }
}
