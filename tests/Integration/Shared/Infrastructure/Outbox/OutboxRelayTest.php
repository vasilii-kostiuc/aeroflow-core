<?php

declare(strict_types=1);

namespace App\Tests\Integration\Shared\Infrastructure\Outbox;

use App\Shared\Infrastructure\Outbox\Outbox;
use App\Shared\Infrastructure\Outbox\OutboxMessage;
use App\Shared\Infrastructure\Outbox\OutboxRelay;
use App\Shared\Infrastructure\Outbox\OutboxSender;
use Doctrine\DBAL\Connection;
use Psr\Log\NullLogger;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Relay semantics on the real outbox table (task 035): oldest first, the priority
 * destination ahead of the rest, a failing destination waits without holding up
 * the others, delivered rows disappear.
 */
final class OutboxRelayTest extends KernelTestCase
{
    private Outbox $outbox;

    protected function setUp(): void
    {
        self::bootKernel();
        self::getContainer()->get(Connection::class)->executeStatement('DELETE FROM outbox_message');
        $this->outbox = self::getContainer()->get(Outbox::class);
    }

    public function testDeliversInOrderWithThePriorityDestinationFirst(): void
    {
        $this->outbox->append('announcement_playback', ['body' => 'a1', 'headers' => ['Content-Type' => 'application/json']]);
        $this->outbox->append('announcement_playback', ['body' => 'a2']);
        $this->outbox->append('emergency_commands', ['body' => 'e1']);
        $sender = new RecordingSender();

        $result = new OutboxRelay($this->outbox, $sender, new NullLogger(), 'emergency_commands')->relay();

        self::assertSame(['sent' => 3, 'failed' => false], $result);
        self::assertSame(['e1', 'a1', 'a2'], $sender->bodies);
        self::assertSame(['Content-Type' => 'application/json'], $sender->messages[1]->headers);
        self::assertSame([], $this->outbox->pending(10));
    }

    public function testAFailingDestinationWaitsWithoutHoldingUpTheOthers(): void
    {
        $this->outbox->append('announcement_playback', ['body' => 'a1']);
        $this->outbox->append('emergency_commands', ['body' => 'e1']);
        $this->outbox->append('announcement_playback', ['body' => 'a2']);
        $sender = new RecordingSender(failOn: 'a1');

        $result = new OutboxRelay($this->outbox, $sender, new NullLogger())->relay();

        self::assertSame(['sent' => 1, 'failed' => true], $result);
        self::assertSame(['e1'], $sender->bodies);
        // a2 must not overtake a1: both stay, in order, for the next pass.
        self::assertSame(['a1', 'a2'], array_map(static fn (OutboxMessage $m): string => $m->body, $this->outbox->pending(10)));
    }
}

final class RecordingSender implements OutboxSender
{
    /** @var list<string> */
    public array $bodies = [];

    /** @var list<OutboxMessage> */
    public array $messages = [];

    public function __construct(private readonly ?string $failOn = null)
    {
    }

    public function send(OutboxMessage $message): void
    {
        if ($message->body === $this->failOn) {
            throw new RuntimeException('broker unavailable');
        }
        $this->bodies[] = $message->body;
        $this->messages[] = $message;
    }
}
