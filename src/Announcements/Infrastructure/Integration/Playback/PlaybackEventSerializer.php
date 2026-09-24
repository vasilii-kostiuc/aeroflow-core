<?php

declare(strict_types=1);

namespace App\Announcements\Infrastructure\Integration\Playback;

use App\Announcements\Application\Playback\EmergencyModeIntegrationEvent;
use App\Announcements\Application\Playback\PlaybackIntegrationEvent;
use JsonException;
use LogicException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

/**
 * Decodes the inbound playback integration events published by aeroflow-playback.
 *
 * The contract crosses a service boundary, so core does not rely on playback's PHP
 * class names: it reads only the JSON body and discriminates by the `event` field,
 * ignoring the producer's `type` header. Mirrors AnnouncementPlaybackSerializer on
 * the playback side.
 *
 * Two families share the queue (task 026): the per-job announcement_playback.* facts
 * and the global emergency_mode.* reports, which carry no announcement or job at
 * all. The `event` prefix tells them apart.
 */
final class PlaybackEventSerializer implements SerializerInterface
{
    private const string EMERGENCY_MODE_PREFIX = 'emergency_mode.';

    private const array REQUIRED_KEYS = [
        'event',
        'messageId',
        'correlationId',
        'announcementId',
        'jobId',
    ];

    public function decode(array $encodedEnvelope): Envelope
    {
        $body = $encodedEnvelope['body'];
        if ($body === '') {
            throw new MessageDecodingFailedException('Empty playback event body.');
        }

        try {
            /** @var array<string,mixed> $data */
            $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new MessageDecodingFailedException('Playback event body is not valid JSON.', 0, $exception);
        }

        $event = (string) ($data['event'] ?? '');
        if (str_starts_with($event, self::EMERGENCY_MODE_PREFIX)) {
            return RetryCountHeader::restore(new Envelope($this->decodeEmergencyMode($event, $data)), $encodedEnvelope);
        }

        foreach (self::REQUIRED_KEYS as $key) {
            if (!array_key_exists($key, $data)) {
                throw new MessageDecodingFailedException(sprintf('Playback event is missing "%s".', $key));
            }
        }

        return RetryCountHeader::restore(new Envelope(new PlaybackIntegrationEvent(
            event: (string) $data['event'],
            messageId: (string) $data['messageId'],
            correlationId: (string) $data['correlationId'],
            announcementId: (string) $data['announcementId'],
            jobId: (string) $data['jobId'],
            occurredAt: (string) ($data['occurredAt'] ?? ''),
            schemaVersion: (int) ($data['schemaVersion'] ?? 1),
            reason: isset($data['reason']) ? (string) $data['reason'] : null,
            nextAt: isset($data['nextAt']) ? (string) $data['nextAt'] : null,
        )), $encodedEnvelope);
    }

    /**
     * @param array<string,mixed> $data
     */
    private function decodeEmergencyMode(string $event, array $data): EmergencyModeIntegrationEvent
    {
        if ($event !== EmergencyModeIntegrationEvent::EVENT_ACTIVATED
            && $event !== EmergencyModeIntegrationEvent::EVENT_DEACTIVATED) {
            throw new MessageDecodingFailedException(sprintf('Unknown emergency mode event "%s".', $event));
        }
        if (!array_key_exists('messageId', $data)) {
            throw new MessageDecodingFailedException('Emergency mode event is missing "messageId".');
        }

        return new EmergencyModeIntegrationEvent(
            event: $event,
            messageId: (string) $data['messageId'],
            occurredAt: (string) ($data['occurredAt'] ?? ''),
            schemaVersion: (int) ($data['schemaVersion'] ?? 1),
        );
    }

    /**
     * Encoding happens when the retry listener re-sends a failed event to this
     * same transport; the body must round-trip through decode(). A throwing
     * encode() would kill the whole worker on the first handler failure.
     */
    public function encode(Envelope $envelope): array
    {
        $message = $envelope->getMessage();
        if ($message instanceof EmergencyModeIntegrationEvent) {
            return [
                'body' => json_encode([
                    'event' => $message->event,
                    'messageId' => $message->messageId,
                    'occurredAt' => $message->occurredAt,
                    'schemaVersion' => $message->schemaVersion,
                ], JSON_THROW_ON_ERROR),
                'headers' => RetryCountHeader::add($envelope, [
                    'type' => $message->event,
                    'Content-Type' => 'application/json',
                ]),
            ];
        }

        if (!$message instanceof PlaybackIntegrationEvent) {
            throw new LogicException(sprintf(
                '%s only encodes playback events, got "%s".',
                self::class,
                get_debug_type($message),
            ));
        }

        $body = [
            'event' => $message->event,
            'messageId' => $message->messageId,
            'correlationId' => $message->correlationId,
            'announcementId' => $message->announcementId,
            'jobId' => $message->jobId,
        ];
        if ($message->reason !== null) {
            $body['reason'] = $message->reason;
        }
        if ($message->nextAt !== null) {
            $body['nextAt'] = $message->nextAt;
        }
        $body['occurredAt'] = $message->occurredAt;
        $body['schemaVersion'] = $message->schemaVersion;

        return [
            'body' => json_encode($body, JSON_THROW_ON_ERROR),
            'headers' => RetryCountHeader::add($envelope, [
                'type' => $message->event,
                'Content-Type' => 'application/json',
            ]),
        ];
    }
}
