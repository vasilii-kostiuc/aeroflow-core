<?php

declare(strict_types=1);

namespace App\Announcements\Infrastructure\Integration\Playback;

use App\Announcements\Application\Playback\ActivateEmergencyMode;
use App\Announcements\Application\Playback\DeactivateEmergencyMode;
use App\Announcements\Application\Playback\EmergencyModePublisherInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Sends the emergency mode control commands to aeroflow-playback. Routing puts them
 * on the dedicated emergency_commands transport (ADR 002, level 2), not on the
 * announcement_playback queue the ordinary requests share.
 */
final readonly class MessengerEmergencyModePublisher implements EmergencyModePublisherInterface
{
    public function __construct(
        #[Autowire(service: 'integration.bus')]
        private MessageBusInterface $bus,
    ) {
    }

    public function publishActivate(ActivateEmergencyMode $message): void
    {
        $this->bus->dispatch($message);
    }

    public function publishDeactivate(DeactivateEmergencyMode $message): void
    {
        $this->bus->dispatch($message);
    }
}
