<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Outbox;

use Doctrine\DBAL\Connection;

/**
 * Transactional outbox of outbound integration messages (task 035).
 *
 * A message is appended through the same DBAL connection the ORM uses, so inside
 * a command bus transaction the row commits or rolls back together with the state
 * change that produced it. OutboxRelay delivers the rows to RabbitMQ afterwards.
 *
 * The row holds the encoded wire message (contract JSON body and headers), not a
 * PHP object: what the relay sends is exactly what Messenger would have sent.
 */
final readonly class Outbox
{
    private const string TABLE = 'outbox_message';

    public function __construct(private Connection $connection)
    {
    }

    /**
     * @param array{body: string, headers?: array<string, string>} $encoded
     */
    public function append(string $destination, array $encoded): void
    {
        $this->connection->insert(self::TABLE, [
            'destination' => $destination,
            'body' => $encoded['body'],
            'headers' => json_encode($encoded['headers'] ?? [], JSON_THROW_ON_ERROR),
        ]);
    }

    /**
     * Oldest first; rows for $first go ahead of the rest (order is kept within a
     * destination, which is all the broker preserves anyway).
     *
     * @return list<OutboxMessage>
     */
    public function pending(int $limit, ?string $first = null): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT id, destination, body, headers FROM '.self::TABLE
            .' ORDER BY CASE WHEN destination = :first THEN 0 ELSE 1 END, id LIMIT '.$limit,
            ['first' => $first ?? ''],
        );

        return array_map(
            static fn (array $row): OutboxMessage => new OutboxMessage(
                (int) $row['id'],
                (string) $row['destination'],
                (string) $row['body'],
                json_decode((string) $row['headers'], true, 512, JSON_THROW_ON_ERROR),
            ),
            $rows,
        );
    }

    public function remove(int $id): void
    {
        $this->connection->delete(self::TABLE, ['id' => $id]);
    }
}
