<?php

declare(strict_types=1);

namespace App\Announcements\Domain\Exception;

use App\Shared\Domain\DomainException;

final class InvalidAnnouncementTemplateSegmentException extends DomainException
{
    public static function invalidSortOrder(): self
    {
        return new self('Segment sort order must be positive.');
    }

    public static function invalidAudioAsset(): self
    {
        return new self('Audio asset segment requires a valid audioAssetId.');
    }

    public static function invalidPause(): self
    {
        return new self('Pause duration must be between 100 and 10000 milliseconds.');
    }

    public static function invalidText(): self
    {
        return new self('Text segment requires non-empty text.');
    }

    public static function invalidSlot(string $slot): self
    {
        return new self(sprintf('Dynamic slot "%s" is incompatible with this announcement type.', $slot));
    }
}
