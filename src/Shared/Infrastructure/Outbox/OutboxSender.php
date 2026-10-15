<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Outbox;

/** Puts one outbox message on the wire; throws when the broker did not accept it. */
interface OutboxSender
{
    public function send(OutboxMessage $message): void;
}
