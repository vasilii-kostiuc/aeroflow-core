<?php

declare(strict_types=1);

namespace App\SystemStatus\Application\GetSystemAlerts;

use App\SystemStatus\Application\SystemAlert;
use App\SystemStatus\Application\SystemStatusReader;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class GetSystemAlertsHandler
{
    public function __construct(private SystemStatusReader $reader)
    {
    }

    /** @return list<SystemAlert> */
    public function __invoke(GetSystemAlertsQuery $query): array
    {
        return $this->reader->alerts();
    }
}
