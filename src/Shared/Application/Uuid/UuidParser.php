<?php

declare(strict_types=1);

namespace App\Shared\Application\Uuid;

use App\Shared\Application\Uuid\Exception\InvalidUuidException;
use Symfony\Component\Uid\Uuid;

final class UuidParser
{
    public static function parse(string $value): Uuid
    {
        if (!Uuid::isValid($value)) {
            throw InvalidUuidException::forValue($value);
        }

        return Uuid::fromString($value);
    }
}
