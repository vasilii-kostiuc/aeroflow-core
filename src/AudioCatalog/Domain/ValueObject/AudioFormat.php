<?php

declare(strict_types=1);

namespace App\AudioCatalog\Domain\ValueObject;

/**
 * A supported audio format: the detected MIME type paired with the file
 * extension used for storage. Owns the single mapping both the upload and the
 * generation paths relied on separately, and gates the "supported format" rule
 * via {@see tryFromMimeType()} (null for an unsupported MIME type).
 *
 * The original MIME type is preserved as-is (e.g. `audio/x-wav` stays
 * `audio/x-wav`); only the extension is derived.
 */
final readonly class AudioFormat
{
    /**
     * @var array<string, string>
     */
    private const EXTENSION_BY_MIME_TYPE = [
        'audio/wav' => 'wav',
        'audio/x-wav' => 'wav',
        'audio/mpeg' => 'mp3',
        'audio/ogg' => 'ogg',
        'application/ogg' => 'ogg',
    ];

    private function __construct(
        public string $mimeType,
        public string $extension,
    ) {
    }

    public static function tryFromMimeType(string $mimeType): ?self
    {
        $extension = self::EXTENSION_BY_MIME_TYPE[$mimeType] ?? null;

        return null === $extension ? null : new self($mimeType, $extension);
    }
}
