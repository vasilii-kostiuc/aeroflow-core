<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Outbox;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Throwable;

/**
 * Delivers outbox rows to the broker (task 035): at least once, in order within a
 * destination. A row is removed only after the broker confirmed it; when sending
 * fails, the rest of that destination waits for the next pass instead of
 * overtaking it. A row sent but not yet removed is sent again — receivers drop the
 * duplicate by messageId.
 */
final readonly class OutboxRelay
{
    private const int BATCH = 100;

    public function __construct(
        private Outbox $outbox,
        private OutboxSender $sender,
        private LoggerInterface $logger,
        /** Delivered ahead of the other destinations (the emergency control plane). */
        #[Autowire(param: 'app.outbox.first_destination')]
        private ?string $firstDestination = null,
    ) {
    }

    /**
     * One pass over the pending rows.
     *
     * @return array{sent: int, failed: bool}
     */
    public function relay(): array
    {
        $sent = 0;
        $blocked = [];
        foreach ($this->outbox->pending(self::BATCH, $this->firstDestination) as $message) {
            if (isset($blocked[$message->destination])) {
                continue;
            }

            try {
                $this->sender->send($message);
            } catch (Throwable $exception) {
                $blocked[$message->destination] = true;
                $this->logger->error('Outbox message {id} to {destination} was not delivered: {message}', [
                    'id' => $message->id,
                    'destination' => $message->destination,
                    'message' => $exception->getMessage(),
                    'exception' => $exception,
                ]);

                continue;
            }

            $this->outbox->remove($message->id);
            ++$sent;
        }

        return ['sent' => $sent, 'failed' => [] !== $blocked];
    }
}
