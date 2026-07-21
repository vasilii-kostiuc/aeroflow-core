<?php

declare(strict_types=1);

namespace App\AudioCatalog\Domain\Exception;

use App\Shared\Domain\DomainException;

final class UnsupportedTtsVoiceException extends DomainException
{
    public static function forLanguage(string $languageCode): self
    {
        return new self(sprintf('No TTS voice is available for language "%s".', $languageCode));
    }

    public static function forVoice(string $voice, string $languageCode): self
    {
        return new self(sprintf('TTS voice "%s" is not available for language "%s".', $voice, $languageCode));
    }
}
