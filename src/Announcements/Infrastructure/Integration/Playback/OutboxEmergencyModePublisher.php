<?php

declare(strict_types=1);

namespace App\Announcements\Infrastructure\Integration\Playback;

use App\Announcements\Application\Playback\ActivateEmergencyMode;
use App\Announcements\Application\Playback\DeactivateEmergencyMode;
use App\Announcements\Application\Playback\EmergencyModePublisherInterface;
use App\Shared\Infrastructure\Outbox\Outbox;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

/**
 * Writes the emergency mode control commands for aeroflow-playback into the outbox
 * (task 035) under their own destination: the relay sends them first and on the
 * dedicated emergency_commands queue (ADR 002, level 2), never behind the bulk of
 * ordinary announcement_playback requests.
 */
final readonly class OutboxEmergencyModePublisher implements EmergencyModePublisherInterface
{
    public const string DESTINATION = 'emergency_commands';

    public function __construct(
        private Outbox $outbox,
        #[Autowire(service: 'messenger.transport.symfony_serializer')]
        private SerializerInterface $serializer,
    ) {
    }

    public function publishActivate(ActivateEmergencyMode $message): void
    {
        $this->append($message);
    }

    public function publishDeactivate(DeactivateEmergencyMode $message): void
    {
        $this->append($message);
    }

    private function append(object $message): void
    {
        $this->outbox->append(self::DESTINATION, $this->serializer->encode(new Envelope($message)));
    }
}
