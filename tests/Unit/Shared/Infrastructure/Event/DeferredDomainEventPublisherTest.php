<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Event;

use App\Shared\Infrastructure\Event\DeferredDomainEventPublisher;
use App\Shared\Infrastructure\Event\MessengerDomainEventPublisher;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class DeferredDomainEventPublisherTest extends TestCase
{
    public function testDispatchesImmediatelyOutsideScope(): void
    {
        $dispatched = [];
        $publisher = $this->publisher($dispatched);

        $event = new stdClass();
        $publisher->publish($event);

        self::assertSame([$event], $dispatched);
    }

    public function testBuffersWithinScopeAndReleasesAfterCommit(): void
    {
        $dispatched = [];
        $publisher = $this->publisher($dispatched);

        $publisher->enter();
        $first = new stdClass();
        $second = new stdClass();
        $publisher->publish($first, $second);

        self::assertSame([], $dispatched, 'events must not be dispatched before commit');

        $publisher->commit();

        self::assertSame([$first, $second], $dispatched);
    }

    public function testDiscardsBufferedEventsOnRollback(): void
    {
        $dispatched = [];
        $publisher = $this->publisher($dispatched);

        $publisher->enter();
        $publisher->publish(new stdClass());
        $publisher->rollback();

        self::assertSame([], $dispatched);
    }

    public function testNestedScopeReleasesOnlyOnOutermostCommit(): void
    {
        $dispatched = [];
        $publisher = $this->publisher($dispatched);

        $publisher->enter();              // outer
        $publisher->enter();              // nested
        $inner = new stdClass();
        $publisher->publish($inner);
        $publisher->commit();             // nested commit: nothing yet
        self::assertSame([], $dispatched);

        $outer = new stdClass();
        $publisher->publish($outer);
        $publisher->commit();             // outermost commit: flush in order

        self::assertSame([$inner, $outer], $dispatched);
    }

    public function testNestedRollbackKeepsScopeUntilOuterDiscards(): void
    {
        $dispatched = [];
        $publisher = $this->publisher($dispatched);

        $publisher->enter();
        $publisher->publish(new stdClass());
        $publisher->enter();
        $publisher->rollback();           // leaves outer scope open
        $publisher->rollback();           // outermost: discard

        self::assertSame([], $dispatched);
    }

    /**
     * @param list<object> $dispatched
     */
    private function publisher(array &$dispatched): DeferredDomainEventPublisher
    {
        $bus = new class($dispatched) implements MessageBusInterface {
            /** @param list<object> $dispatched */
            public function __construct(private array &$dispatched)
            {
            }

            public function dispatch(object $message, array $stamps = []): Envelope
            {
                $this->dispatched[] = $message;

                return new Envelope($message);
            }
        };

        return new DeferredDomainEventPublisher(new MessengerDomainEventPublisher($bus));
    }
}
