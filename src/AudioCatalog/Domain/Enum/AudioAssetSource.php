<?php

declare(strict_types=1);

namespace App\AudioCatalog\Domain\Enum;

/**
 * Origin of the audio material behind an AudioAsset. `Legacy` is reserved for a
 * future migration import of the old SAO library; the implemented sources are
 * operator uploads and TTS-generated speech (task 021).
 */
enum AudioAssetSource: string
{
    case Uploaded = 'uploaded';
    case Generated = 'generated';
}
