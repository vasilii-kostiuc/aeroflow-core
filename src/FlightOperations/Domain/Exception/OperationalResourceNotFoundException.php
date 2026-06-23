<?php

declare(strict_types=1);

namespace App\FlightOperations\Domain\Exception;

use App\Shared\Domain\DomainException;

final class OperationalResourceNotFoundException extends DomainException
{
    public static function withId(string $type, string $id): self
    {
        return new self(sprintf('%s "%s" was not found.', $type, $id));
    }
}
