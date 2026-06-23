<?php

declare(strict_types=1);

namespace App\AudioCatalog\Domain\Exception;

use App\Shared\Domain\DomainException;

final class InvalidAudioAssetUploadException extends DomainException
{
    public static function emptyFile(): self
    {
        return new self('Audio file cannot be empty.');
    }

    public static function tooLarge(int $maxSizeBytes): self
    {
        return new self(sprintf('Audio file cannot exceed %d MB.', intdiv($maxSizeBytes, 1024 * 1024)));
    }

    public static function unsupportedFormat(string $mimeType): self
    {
        return new self(sprintf('Unsupported audio format "%s". Allowed formats: WAV, MP3 and OGG.', $mimeType));
    }

    public static function unreadable(): self
    {
        return new self('Uploaded audio file cannot be read.');
    }

    public static function invalidName(): self
    {
        return new self('Audio file name must contain between 1 and 255 characters.');
    }
}
