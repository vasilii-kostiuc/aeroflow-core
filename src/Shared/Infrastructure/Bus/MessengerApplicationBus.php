<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus;

use App\Shared\Application\Bus\ApplicationBus;
use App\Shared\Application\Bus\UnexpectedResultException;

use function is_array;

use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

final readonly class MessengerApplicationBus implements ApplicationBus
{
    public function __construct(private MessageBusInterface $messageBus)
    {
    }

    public function dispatch(object $message): void
    {
        $this->messageBus->dispatch($message);
    }

    public function handle(object $message): mixed
    {
        return $this->messageBus->dispatch($message)->last(HandledStamp::class)?->getResult();
    }

    public function handleAs(object $message, string $resultType): object
    {
        $result = $this->handle($message);

        if (!$result instanceof $resultType) {
            throw UnexpectedResultException::expectedType($message, $resultType, $result);
        }

        return $result;
    }

    public function handleList(object $message, string $itemType): array
    {
        $result = $this->handle($message);

        if (!is_array($result)) {
            throw UnexpectedResultException::expectedList($message, $itemType, $result);
        }

        foreach ($result as $item) {
            if (!$item instanceof $itemType) {
                throw UnexpectedResultException::expectedList($message, $itemType, $item);
            }
        }

        return array_values($result);
    }
}
