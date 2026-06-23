<?php

declare(strict_types=1);

namespace App\AudioCatalog\Application\EventHandler;

use App\AudioCatalog\Domain\Event\AudioAssetUploaded;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

final class OnAudioAssetUploaded
{
    #[AsMessageHandler(bus: 'event.bus')]
    public function __invoke(AudioAssetUploaded $event): void
    {
    }
}
