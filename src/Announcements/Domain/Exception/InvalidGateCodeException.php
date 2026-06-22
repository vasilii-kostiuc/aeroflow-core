<?php

declare(strict_types=1);

namespace App\Announcements\Domain\Exception;

use App\Shared\Domain\DomainException;

final class InvalidGateCodeException extends DomainException
{
    public static function forValue(string $value): self
    {
        return new self(sprintf('Invalid gate code "%s".', $value));
    }
}
