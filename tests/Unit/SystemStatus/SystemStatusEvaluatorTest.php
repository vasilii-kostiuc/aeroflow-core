<?php

declare(strict_types=1);

namespace App\Tests\Unit\SystemStatus;

use App\SystemStatus\Application\ComponentStatus;
use App\SystemStatus\Application\Port\HeartbeatRecord;
use App\SystemStatus\Application\Port\QueueSnapshot;
use App\SystemStatus\Application\SystemStatusEvaluator;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class SystemStatusEvaluatorTest extends TestCase
{
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->now = new DateTimeImmutable('2026-09-25T10:00:00+00:00');
    }

    public function testAllHealthy(): void
    {
        $components = $this->evaluate();

        self::assertSame('ok', new SystemStatusEvaluator()->overall($components));
        self::assertSame([], new SystemStatusEvaluator()->alerts($components));
        self::assertSame('ok', $this->statusOf($components, 'agent'));
        self::assertSame('ok', $this->statusOf($components, 'playback_emergency'));
        self::assertSame('ok', $this->statusOf($components, 'core_events'));
    }

    public function testBrokerDownMakesTheAgentUnknownAndAlerts(): void
    {
        $components = $this->evaluate(queues: null);

        self::assertSame('down', $this->statusOf($components, 'broker'));
        self::assertSame('unknown', $this->statusOf($components, 'agent'));
        self::assertSame('unknown', $this->statusOf($components, 'queues'));
        self::assertSame(['broker'], array_column(new SystemStatusEvaluator()->alerts($components), 'key'));
    }

    public function testAgentWithoutSubscriptionIsDown(): void
    {
        $components = $this->evaluate(queues: [new QueueSnapshot('agent_commands', 0, 0)]);

        self::assertSame('down', $this->statusOf($components, 'agent'));
        self::assertContains('agent', array_column(new SystemStatusEvaluator()->alerts($components), 'key'));
    }

    public function testAStaleWorkerIsDownAfterAMinute(): void
    {
        $components = $this->evaluate(heartbeats: [
            $this->playback(['announcement_playback', 'agent_events', 'scheduler'], 30),
            $this->playback(['emergency_commands'], SystemStatusEvaluator::HEARTBEAT_STALE_SECONDS + 5),
            new HeartbeatRecord('core', ['playback_events'], $this->now->modify('-10 seconds')),
        ]);

        self::assertSame('ok', $this->statusOf($components, 'playback_requests'));
        self::assertSame('down', $this->statusOf($components, 'playback_emergency'));
        self::assertSame(['playback_emergency'], array_column(new SystemStatusEvaluator()->alerts($components), 'key'));
    }

    public function testAWorkerThatNeverReportedIsDown(): void
    {
        $components = $this->evaluate(heartbeats: []);

        self::assertSame('down', $this->statusOf($components, 'core_events'));
        self::assertSame('unknown', $this->statusOf($components, 'playing'));
    }

    public function testAJobPlayingTooLongIsAWarning(): void
    {
        $since = $this->now->modify(sprintf('-%d minutes', SystemStatusEvaluator::PLAYING_TOO_LONG_MINUTES + 1));
        $components = $this->evaluate(heartbeats: [
            $this->playback(['announcement_playback', 'agent_events', 'scheduler'], 5, $since->format(DATE_ATOM), 2),
            $this->playback(['emergency_commands'], 5),
            new HeartbeatRecord('core', ['playback_events'], $this->now),
        ]);

        self::assertSame('warning', $this->statusOf($components, 'playing'));
        self::assertSame('warning', new SystemStatusEvaluator()->overall($components));
        self::assertSame(['playing'], array_column(new SystemStatusEvaluator()->alerts($components), 'key'));
    }

    public function testPilingQueueIsAWarningButNotADispatcherAlert(): void
    {
        $components = $this->evaluate(queues: [
            new QueueSnapshot('agent_commands', 1, 0),
            new QueueSnapshot('playback_events', 0, SystemStatusEvaluator::QUEUE_BACKLOG_WARNING + 1),
        ]);

        self::assertSame('warning', $this->statusOf($components, 'queues'));
        self::assertSame([], new SystemStatusEvaluator()->alerts($components));
    }

    /**
     * @param list<QueueSnapshot>|null   $queues
     * @param list<HeartbeatRecord>|null $heartbeats
     *
     * @return list<ComponentStatus>
     */
    private function evaluate(?array $queues = [new QueueSnapshot('agent_commands', 1, 0)], ?array $heartbeats = null): array
    {
        return new SystemStatusEvaluator()->components(
            true,
            $queues,
            $heartbeats ?? [
                $this->playback(['announcement_playback', 'agent_events', 'scheduler'], 5),
                $this->playback(['emergency_commands'], 5),
                new HeartbeatRecord('core', ['playback_events'], $this->now->modify('-3 seconds')),
            ],
            true,
            $this->now,
        );
    }

    /**
     * @param list<string> $transports
     */
    private function playback(array $transports, int $secondsAgo, ?string $playingSince = null, int $pending = 0): HeartbeatRecord
    {
        return new HeartbeatRecord(
            'playback',
            $transports,
            $this->now->modify(sprintf('-%d seconds', $secondsAgo)),
            ['pendingJobs' => $pending, 'playingSince' => $playingSince],
        );
    }

    /**
     * @param list<ComponentStatus> $components
     */
    private function statusOf(array $components, string $key): string
    {
        foreach ($components as $component) {
            if ($component->key === $key) {
                return $component->status;
            }
        }
        self::fail(sprintf('No component "%s".', $key));
    }
}
