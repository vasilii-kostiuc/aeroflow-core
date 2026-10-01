<?php

declare(strict_types=1);

namespace App\SystemStatus\Infrastructure\Messenger;

use App\SystemStatus\Application\Port\HeartbeatRecord;
use App\SystemStatus\Application\Port\HeartbeatStoreInterface;
use Closure;
use DateTimeImmutable;
use DateTimeZone;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\Event\WorkerRunningEvent;
use Throwable;

/**
 * Core's own worker reports that it is alive (task 034), straight into the
 * heartbeat store — the same table playback's heartbeats land in. At most every
 * INTERVAL seconds; a failure here is logged and never stops the worker.
 */
#[AsEventListener(event: WorkerRunningEvent::class)]
final class CoreWorkerHeartbeatListener
{
    public const int INTERVAL_SECONDS = 15;

    private ?float $lastSentAt = null;

    /** @var Closure(): float */
    private Closure $now;

    public function __construct(
        private readonly HeartbeatStoreInterface $store,
        private readonly LoggerInterface $logger,
        ?Closure $now = null,
    ) {
        $this->now = $now ?? static fn (): float => microtime(true);
    }

    public function __invoke(WorkerRunningEvent $event): void
    {
        $now = ($this->now)();
        if (null !== $this->lastSentAt && $now - $this->lastSentAt < self::INTERVAL_SECONDS) {
            return;
        }
        $this->lastSentAt = $now;

        try {
            $this->store->record(new HeartbeatRecord(
                'core',
                array_values($event->getWorker()->getMetadata()->getTransportNames()),
                new DateTimeImmutable('now', new DateTimeZone('UTC')),
            ));
        } catch (Throwable $exception) {
            $this->logger->warning('Core worker heartbeat was not recorded: {message}', [
                'message' => $exception->getMessage(),
                'exception' => $exception,
            ]);
        }
    }
}
