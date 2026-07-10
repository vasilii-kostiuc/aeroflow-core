<?php

declare(strict_types=1);

namespace App\Announcements\Infrastructure\Integration\Playback;

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
 */
final class PlaybackEventSerializer implements SerializerInterface
{
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

        foreach (self::REQUIRED_KEYS as $key) {
            if (!array_key_exists($key, $data)) {
                throw new MessageDecodingFailedException(sprintf('Playback event is missing "%s".', $key));
            }
        }

        return new Envelope(new PlaybackIntegrationEvent(
            event: (string) $data['event'],
            messageId: (string) $data['messageId'],
            correlationId: (string) $data['correlationId'],
            announcementId: (string) $data['announcementId'],
            jobId: (string) $data['jobId'],
            occurredAt: (string) ($data['occurredAt'] ?? ''),
            schemaVersion: (int) ($data['schemaVersion'] ?? 1),
        ));
    }

    public function encode(Envelope $envelope): array
    {
        throw new LogicException(self::class.' only decodes inbound playback events.');
    }
}
