<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Event;

use App\Shared\Application\Event\DomainEventPublisher;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Exception\NoHandlerForMessageException;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class MessengerDomainEventPublisher implements DomainEventPublisher
{
    public function __construct(
        #[Autowire(service: 'event.bus')]
        private MessageBusInterface $eventBus,
    ) {
    }

    public function publish(object ...$events): void
    {
        foreach ($events as $event) {
            try {
                $this->eventBus->dispatch($event);
            } catch (NoHandlerForMessageException) {
                // A domain event with no subscriber is normal: `event.bus` is
                // declared `allow_no_handlers` precisely so raising a fact nobody
                // consumes yet is not an error (e.g. AudioAssetGenerated).
                //
                // That flag does not reach the middleware on the current Symfony
                // version: FrameworkExtension passes it as argument index 0 of the
                // abstract `messenger.middleware.handle_message`, which MessengerPass
                // then overwrites with the handlers locator, so every bus ends up
                // with the constructor default `allowNoHandlers: false`. Until that
                // is fixed upstream, honour the intent here.
            }
        }
    }
}
