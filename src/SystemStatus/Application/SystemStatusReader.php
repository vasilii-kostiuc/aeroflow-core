<?php

declare(strict_types=1);

namespace App\SystemStatus\Application;

use App\SystemStatus\Application\Port\HeartbeatStoreInterface;
use App\SystemStatus\Application\Port\MessageBrokerProbeInterface;
use App\SystemStatus\Application\Port\ServiceProbeInterface;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

/** Runs the probes once and evaluates them; shared by the admin screen and the dispatcher's alerts. */
final readonly class SystemStatusReader
{
    public function __construct(
        private ServiceProbeInterface $services,
        private MessageBrokerProbeInterface $broker,
        private HeartbeatStoreInterface $heartbeats,
        private SystemStatusEvaluator $evaluator,
    ) {
    }

    public function read(): SystemStatusResult
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $components = $this->evaluator->components(
            $this->services->databaseReachable(),
            $this->broker->queues(),
            $this->heartbeats->all(),
            $this->services->textToSpeechHealthy(),
            $now,
        );

        return new SystemStatusResult($now->format(DateTimeInterface::ATOM), $this->evaluator->overall($components), $components);
    }

    /** @return list<SystemAlert> */
    public function alerts(): array
    {
        return $this->evaluator->alerts($this->read()->components);
    }
}
