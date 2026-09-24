<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Console\AudioLibrary;

/**
 * Where an audio asset is used: an emergency or supplementary preset (label = the
 * preset name) or a flight announcement segment (label = the flight number, text =
 * the segment text the asset was voiced from).
 */
final readonly class AssetUsage
{
    public const string EMERGENCY = 'emergency';
    public const string SUPPLEMENTARY = 'supplementary';
    public const string DEPARTURE = 'departure';
    public const string ARRIVAL = 'arrival';

    public function __construct(
        public string $assetId,
        public string $kind,
        public string $label,
        public ?string $text = null,
    ) {
    }
}
