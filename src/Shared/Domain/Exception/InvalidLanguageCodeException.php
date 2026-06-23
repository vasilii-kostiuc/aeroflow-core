<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

use App\Shared\Domain\DomainException;

final class InvalidLanguageCodeException extends DomainException
{
    public static function forValue(string $value): self
    {
        return new self(sprintf('Invalid language code "%s".', $value));
    }
}
