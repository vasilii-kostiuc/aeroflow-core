<?php

declare(strict_types=1);

namespace App\FlightOperations\Domain\Exception;

use App\Shared\Domain\DomainException;

final class AirportNotFoundException extends DomainException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Airport "%s" was not found.', $id));
    }
}
