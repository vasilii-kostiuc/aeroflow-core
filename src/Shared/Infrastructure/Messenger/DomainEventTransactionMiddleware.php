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
 * buffered events only after the message (and its Doctrine transaction) has been
 * handled successfully. On failure the buffered events are discarded together
 * with the rolled-back transaction.
 *
 * Must be registered before `doctrine_transaction` in the command bus middleware
 * list so that its post-handling (commit) runs after the transaction commits.
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
