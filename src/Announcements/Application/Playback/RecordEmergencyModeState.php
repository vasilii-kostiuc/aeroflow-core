<?php

declare(strict_types=1);

namespace App\Announcements\Application\Playback;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handles the inbound emergency mode reports. Idempotent in the store: a redelivered
 * or out-of-order report leaves the recorded state unchanged.
 */
#[AsMessageHandler]
final readonly class RecordEmergencyModeState
{
    public function __construct(private EmergencyModeStateInterface $state)
    {
    }

    public function __invoke(EmergencyModeIntegrationEvent $event): void
    {
        $this->state->apply($event);
    }
}
