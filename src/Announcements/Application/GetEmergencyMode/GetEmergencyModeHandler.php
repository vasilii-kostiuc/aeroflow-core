<?php

declare(strict_types=1);

namespace App\Announcements\Application\GetEmergencyMode;

use App\Announcements\Application\Playback\EmergencyModeStateInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Local CQRS read of the eventual emergency mode state (task 026): the dispatcher's
 * banner shows what playback confirmed, not what core asked for.
 */
#[AsMessageHandler(bus: 'command.bus')]
final readonly class GetEmergencyModeHandler
{
    public function __construct(private EmergencyModeStateInterface $state)
    {
    }

    public function __invoke(GetEmergencyModeQuery $query): EmergencyModeResult
    {
        $view = $this->state->current();

        return new EmergencyModeResult(active: $view->active, since: $view->since);
    }
}
