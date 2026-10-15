<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Messenger;

use App\Shared\Infrastructure\Event\DeferredDomainEventPublisher;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Throwable;

/**
 * Opens a domain-event scope around each command bus message and releases the
 * buffered events once the message has been handled successfully; on failure they
 * are discarded.
 *
 * Must be registered after `doctrine_transaction` in the command bus middleware
 * list (task 035): the events are then handled inside the transaction, so the
 * outbox rows their handlers write commit atomically with the use case.
 */
final readonly class DomainEventTransactionMiddleware implements MiddlewareInterface
{
    public function __construct(private DeferredDomainEventPublisher $publisher)
    {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $this->publisher->enter();

        try {
            $envelope = $stack->next()->handle($envelope, $stack);
            $this->publisher->commit();

            return $envelope;
        } catch (Throwable $exception) {
            $this->publisher->rollback();

            throw $exception;
        }
    }
}
