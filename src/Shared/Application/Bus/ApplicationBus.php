<?php

declare(strict_types=1);

namespace App\Shared\Application\Bus;

/**
 * Thin facade over the synchronous application message bus.
 *
 * Hides the Messenger envelope/HandledStamp transport detail from callers and
 * makes result handling uniform across controllers and application services.
 *
 * Note: handle(), handleAs() and handleList() rely on a synchronous handler
 * result. They must not be used for fire-and-forget or asynchronous integration
 * commands, whose result is not available synchronously — use dispatch() there.
 */
interface ApplicationBus
{
    /**
     * Dispatch a message without expecting a synchronous result.
     *
     * Use for commands that do not return a value and for asynchronous
     * integration commands routed to a transport.
     */
    public function dispatch(object $message): void;

    /**
     * Dispatch a message and return the raw synchronous handler result.
     */
    public function handle(object $message): mixed;

    /**
     * Dispatch a message and assert the synchronous handler result type.
     *
     * @template T of object
     *
     * @param class-string<T> $resultType
     *
     * @return T
     *
     * @throws UnexpectedResultException when the handler result is not of $resultType
     */
    public function handleAs(object $message, string $resultType): object;

    /**
     * Dispatch a message and assert the synchronous handler returned a list of items.
     *
     * @template T of object
     *
     * @param class-string<T> $itemType
     *
     * @return list<T>
     *
     * @throws UnexpectedResultException when the result is not a list of $itemType
     */
    public function handleList(object $message, string $itemType): array;
}
