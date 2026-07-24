<?php

declare(strict_types=1);

namespace App\AudioCatalog\Domain\ValueObject;

use App\AudioCatalog\Domain\Exception\InvalidSynthesisRequestException;

/**
 * Text accepted for speech synthesis: trimmed, non-empty and within the length
 * the TTS service can handle. Owns the validation the generation handler used to
 * perform inline, so a value that exists is already known to be synthesizable.
 */
final readonly class SynthesisText
{
    private const MAX_LENGTH = 2000;

    private function __construct(public string $value)
    {
    }

    public static function fromString(string $text): self
    {
        $trimmed = trim($text);
        if ('' === $trimmed) {
            throw InvalidSynthesisRequestException::emptyText();
        }
        if (mb_strlen($trimmed) > self::MAX_LENGTH) {
            throw InvalidSynthesisRequestException::textTooLong(self::MAX_LENGTH);
        }

        return new self($trimmed);
    }
}
