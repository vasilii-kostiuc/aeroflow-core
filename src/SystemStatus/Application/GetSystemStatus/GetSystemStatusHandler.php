<?php

declare(strict_types=1);

namespace App\SystemStatus\Application\GetSystemStatus;

use App\SystemStatus\Application\SystemStatusReader;
use App\SystemStatus\Application\SystemStatusResult;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class GetSystemStatusHandler
{
    public function __construct(private SystemStatusReader $reader)
    {
    }

    public function __invoke(GetSystemStatusQuery $query): SystemStatusResult
    {
        return $this->reader->read();
    }
}
