<?php

declare(strict_types=1);

namespace App\AudioCatalog\Application\EventHandler;

use App\AudioCatalog\Domain\Event\AudioPromptActivated;
use App\AudioCatalog\Domain\Event\AudioPromptCreated;
use App\AudioCatalog\Domain\Event\AudioPromptDeactivated;
use App\AudioCatalog\Domain\Event\AudioPromptUpdated;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

final class OnAudioPromptChanged
{
    #[AsMessageHandler(bus: 'event.bus')]
    public function created(AudioPromptCreated $event): void
    {
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function updated(AudioPromptUpdated $event): void
    {
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function activated(AudioPromptActivated $event): void
    {
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function deactivated(AudioPromptDeactivated $event): void
    {
    }
}
