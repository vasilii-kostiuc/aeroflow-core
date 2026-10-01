<?php

declare(strict_types=1);

namespace App\SystemStatus\Application\Port;

/** Health of core's own dependencies the status screen reports on. */
interface ServiceProbeInterface
{
    public function databaseReachable(): bool;

    public function textToSpeechHealthy(): bool;
}
