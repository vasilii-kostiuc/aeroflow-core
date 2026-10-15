<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Outbox;

use LogicException;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\Connection;
use Throwable;

/**
 * Publishes outbox rows on the same AMQP exchanges and queues the Messenger
 * transports used to (one DSN per destination), waiting for the broker's publisher
 * confirm: a row is removed from the outbox only once RabbitMQ has taken it.
 */
final class AmqpOutboxSender implements OutboxSender
{
    private const string CONFIRM_TIMEOUT_SECONDS = '5';

    /** @var array<string, Connection> */
    private array $connections = [];

    /**
     * @param array<string, string> $dsns destination => AMQP DSN
     */
    public function __construct(private readonly array $dsns)
    {
    }

    public function send(OutboxMessage $message): void
    {
        $headers = $message->headers;
        $contentType = $headers['Content-Type'] ?? null;
        unset($headers['Content-Type']);

        try {
            $this->connection($message->destination)->publish(
                $message->body,
                $headers,
                0,
                null === $contentType ? null : AmqpStamp::createWithAttributes(['content_type' => $contentType]),
            );
        } catch (Throwable $exception) {
            // Start from a fresh connection next time instead of a half-broken channel.
            unset($this->connections[$message->destination]);

            throw $exception;
        }
    }

    private function connection(string $destination): Connection
    {
        $dsn = $this->dsns[$destination] ?? throw new LogicException(sprintf('No AMQP DSN for outbox destination "%s".', $destination));

        return $this->connections[$destination] ??= Connection::fromDsn($dsn, ['confirm_timeout' => self::CONFIRM_TIMEOUT_SECONDS]);
    }
}
