<?php

declare(strict_types=1);

namespace App\Shared\Application\Pagination\Exception;

use App\Shared\Application\ApplicationException;

final class InvalidPaginationException extends ApplicationException
{
    public static function invalidPage(int $minimum = 1): self
    {
        return new self(sprintf('Page must be an integer greater than or equal to %d.', $minimum));
    }

    public static function invalidLimit(int $minimum, int $maximum): self
    {
        return new self(sprintf('Limit must be an integer between %d and %d.', $minimum, $maximum));
    }
}
