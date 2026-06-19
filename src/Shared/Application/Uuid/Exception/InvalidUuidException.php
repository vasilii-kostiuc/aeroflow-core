<?php

declare(strict_types=1);

namespace App\Shared\Application\Uuid\Exception;

use App\Shared\Application\ApplicationException;

final class InvalidUuidException extends ApplicationException
{
    public static function forValue(string $value): self
    {
        return new self(sprintf('Invalid UUID "%s".', $value));
    }
}
