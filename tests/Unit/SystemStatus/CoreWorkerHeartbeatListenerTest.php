<?php

declare(strict_types=1);

namespace App\Tests\Unit\SystemStatus;

use App\SystemStatus\Application\Port\HeartbeatRecord;
use App\SystemStatus\Application\Port\HeartbeatStoreInterface;
use App\SystemStatus\Infrastructure\Messenger\CoreWorkerHeartbeatListener;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;
use Symfony\Component\Messenger\Event\WorkerRunningEvent;
use Symfony\Component\Messenger\Worker;
use Symfony\Component\Messenger\WorkerMetadata;

final class CoreWorkerHeartbeatListenerTest extends TestCase
{
    public function testRecordsItsTransportsAtMostEveryInterval(): void
    {
        $clock = 100.0;
        $store = new class implements HeartbeatStoreInterface {
            /** @var list<HeartbeatRecord> */
            public array $records = [];

            public function record(HeartbeatRecord $heartbeat): void
            {
                $this->records[] = $heartbeat;
            }

            public function all(): array
            {
                return $this->records;
            }
        };
        $listener = new CoreWorkerHeartbeatListener($store, new NullLogger(), function () use (&$clock): float {
            return $clock;
        });

        $listener($this->event());
        ++$clock;
        $listener($this->event());
        $clock += CoreWorkerHeartbeatListener::INTERVAL_SECONDS;
        $listener($this->event());

        self::assertCount(2, $store->records);
        self::assertSame('core', $store->records[0]->service);
        self::assertSame(['playback_events'], $store->records[0]->transports);
    }

    public function testAStoreFailureDoesNotStopTheWorker(): void
    {
        $store = $this->createStub(HeartbeatStoreInterface::class);
        $store->method('record')->willThrowException(new RuntimeException('database down'));

        new CoreWorkerHeartbeatListener($store, new NullLogger())($this->event());

        $this->addToAssertionCount(1);
    }

    private function event(): WorkerRunningEvent
    {
        $worker = $this->createStub(Worker::class);
        $worker->method('getMetadata')->willReturn(new WorkerMetadata(['transportNames' => ['playback_events']]));

        return new WorkerRunningEvent($worker, true);
    }
}
