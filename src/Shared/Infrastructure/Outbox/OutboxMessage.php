<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Outbox;

final readonly class OutboxMessage
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        public int $id,
        public string $destination,
        public string $body,
        public array $headers,
    ) {
    }
}
