<?php

declare(strict_types=1);

namespace App\Announcements\Domain\Exception;

use App\Shared\Domain\DomainException;

final class MissingAudioPromptsException extends DomainException
{
    /** @param list<string> $keys */
    public static function forKeys(array $keys): self
    {
        sort($keys);

        return new self('Missing active audio prompts: '.implode(', ', $keys).'.');
    }
}
