<?php

declare(strict_types=1);

namespace App\AudioCatalog\Domain\Exception;

use App\Shared\Domain\DomainException;
use Throwable;

/**
 * The TTS service could not produce audio (unreachable, timeout, non-2xx, or a
 * malformed response). Maps to 502 Bad Gateway: the failure is upstream, not a
 * client input error. No AudioAsset is created when this is thrown.
 */
final class TextToSpeechUnavailableException extends DomainException
{
    public static function synthesisFailed(string $reason, ?Throwable $previous = null): self
    {
        return new self(sprintf('Text-to-speech synthesis failed: %s', $reason), 0, $previous);
    }
}
