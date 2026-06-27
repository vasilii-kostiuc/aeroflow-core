<?php

declare(strict_types=1);

namespace App\FlightOperations\Domain\Exception;

use App\Shared\Domain\DomainException;

/**
 * Raised when a dispatcher action conflicts with the current lifecycle state or
 * direction of a FlightOccurrence (e.g. boarding before check-in is closed).
 *
 * Distinct from InvalidFlightOccurrenceTransitionException, which covers
 * create-time input validation. This one maps to HTTP 409 Conflict.
 */
final class FlightOccurrenceTransitionConflictException extends DomainException
{
    public static function forStatus(string $action, string $status): self
    {
        return new self(sprintf('Cannot apply "%s" while flight occurrence is "%s".', $action, $status));
    }

    public static function incompatibleDirection(string $action, string $direction): self
    {
        return new self(sprintf('Cannot apply "%s" to "%s" flight occurrence.', $action, $direction));
    }

    public static function noPreviousManualRun(): self
    {
        return new self('Cannot start the next run: no previous manual run exists for this card and date.');
    }

    public static function previousManualRunNotFinished(string $status): self
    {
        return new self(sprintf('Cannot start the next run while the previous run is "%s".', $status));
    }
}
