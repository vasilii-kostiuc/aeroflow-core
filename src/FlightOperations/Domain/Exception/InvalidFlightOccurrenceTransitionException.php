<?php

declare(strict_types=1);

namespace App\FlightOperations\Domain\Exception;

use App\Shared\Domain\DomainException;

final class InvalidFlightOccurrenceTransitionException extends DomainException
{
    public static function forAction(string $action, string $status): self
    {
        return new self(sprintf('Cannot apply "%s" while flight occurrence is "%s".', $action, $status));
    }

    public static function incompatibleDirection(string $action, string $direction): self
    {
        return new self(sprintf('Cannot apply "%s" to "%s" flight occurrence.', $action, $direction));
    }
}
