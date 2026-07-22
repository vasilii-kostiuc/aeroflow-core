<?php

declare(strict_types=1);

namespace App\Announcements\Infrastructure\Integration\AudioCatalog;

use App\Announcements\Application\Port\AudioCatalog\SpeechAssetGeneratorInterface;
use App\AudioCatalog\Application\AudioAssetResult;
use App\AudioCatalog\Application\GenerateAudioAsset\GenerateAudioAssetCommand;
use App\Shared\Application\Bus\ApplicationBus;

final readonly class AudioCatalogSpeechAssetGenerator implements SpeechAssetGeneratorInterface
{
    public function __construct(private ApplicationBus $bus)
    {
    }

    public function generate(string $text, string $languageCode): string
    {
        $command = new GenerateAudioAssetCommand($text, $languageCode);

        $audioAssetResult = $this->bus->handleAs($command, AudioAssetResult::class);

        return $audioAssetResult->id;
    }
}
