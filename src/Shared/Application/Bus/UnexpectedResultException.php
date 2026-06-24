<?php

declare(strict_types=1);

namespace App\Shared\Application\Bus;

use function get_debug_type;

use LogicException;

use function sprintf;

/**
 * Signals a handler wiring problem: a message produced no result or a result of
 * an unexpected type. This is a programming/configuration error, not a domain
 * or client error, so it intentionally surfaces as a server fault.
 */
final class UnexpectedResultException extends LogicException
{
    public static function expectedType(object $message, string $expectedType, mixed $actual): self
    {
        return new self(sprintf(
            'Handler for "%s" did not return the expected "%s", got "%s".',
            $message::class,
            $expectedType,
            get_debug_type($actual),
        ));
    }

    public static function expectedList(object $message, string $itemType, mixed $actual): self
    {
        return new self(sprintf(
            'Handler for "%s" did not return a list of "%s", got "%s".',
            $message::class,
            $itemType,
            get_debug_type($actual),
        ));
    }
}
