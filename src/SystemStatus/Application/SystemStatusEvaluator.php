<?php

declare(strict_types=1);

namespace App\SystemStatus\Application;

use App\SystemStatus\Application\Port\HeartbeatRecord;
use App\SystemStatus\Application\Port\QueueSnapshot;
use DateTimeImmutable;

/**
 * Turns raw probes into the «Состояние системы» lines and the dispatcher's alerts
 * (task 034). Pure: every input is passed in, so the rules are tested without a
 * broker, a database or a clock.
 */
final class SystemStatusEvaluator
{
    /** A worker whose last heartbeat is older than this is considered stopped. */
    public const int HEARTBEAT_STALE_SECONDS = 60;
    public const int QUEUE_BACKLOG_WARNING = 20;
    public const int PLAYING_TOO_LONG_MINUTES = 10;

    public const string AGENT_QUEUE = 'agent_commands';

    /**
     * Transports served by workers, with the line that reports each.
     *
     * @var array<string, array{string, string}> transport => [key, name]
     */
    private const array WORKER_TRANSPORTS = [
        'announcement_playback' => ['playback_requests', 'Приём объявлений (playback)'],
        'emergency_commands' => ['playback_emergency', 'Экстренные команды (playback)'],
        'agent_events' => ['playback_agent_events', 'События аудиоагента (playback)'],
        'scheduler' => ['playback_scheduler', 'Повторы по расписанию (playback)'],
        'playback_events' => ['core_events', 'Приём событий воспроизведения (core)'],
    ];

    /**
     * @param list<QueueSnapshot>|null $queues     null = broker unreachable
     * @param list<HeartbeatRecord>    $heartbeats
     *
     * @return list<ComponentStatus>
     */
    public function components(
        bool $databaseReachable,
        ?array $queues,
        array $heartbeats,
        bool $textToSpeechHealthy,
        DateTimeImmutable $now,
    ): array {
        $components = [
            new ComponentStatus(
                'database',
                'База данных core',
                $databaseReachable ? ComponentStatus::OK : ComponentStatus::DOWN,
                $databaseReachable ? 'Отвечает.' : 'Не отвечает.',
            ),
            null === $queues
                ? new ComponentStatus('broker', 'Брокер сообщений (RabbitMQ)', ComponentStatus::DOWN, 'Недоступен: объявления не передаются в playback.')
                : new ComponentStatus('broker', 'Брокер сообщений (RabbitMQ)', ComponentStatus::OK, 'Доступен.'),
            $this->agent($queues),
        ];

        foreach (self::WORKER_TRANSPORTS as $transport => [$key, $name]) {
            $components[] = $this->worker($key, $name, $transport, $heartbeats, $now);
        }

        $components[] = $this->playing($heartbeats, $now);
        $components[] = $this->backlog($queues);
        $components[] = new ComponentStatus(
            'tts',
            'Синтез речи (TTS)',
            $textToSpeechHealthy ? ComponentStatus::OK : ComponentStatus::DOWN,
            $textToSpeechHealthy ? 'Отвечает.' : 'Не отвечает: озвучить новый текст сейчас нельзя.',
        );

        return $components;
    }

    /**
     * @param list<ComponentStatus> $components
     */
    public function overall(array $components): string
    {
        $rank = [ComponentStatus::OK => 0, ComponentStatus::UNKNOWN => 1, ComponentStatus::WARNING => 2, ComponentStatus::DOWN => 3];
        $worst = ComponentStatus::OK;
        foreach ($components as $component) {
            if ($rank[$component->status] > $rank[$worst]) {
                $worst = $component->status;
            }
        }

        return $worst;
    }

    /**
     * What the dispatcher must know, in their words: only problems that stop
     * announcements from sounding or make the queue screen lag.
     *
     * @param list<ComponentStatus> $components
     *
     * @return list<SystemAlert>
     */
    public function alerts(array $components): array
    {
        $messages = [
            'broker' => [ComponentStatus::DOWN, 'Нет связи с брокером сообщений — объявления не передаются на воспроизведение.'],
            'agent' => [ComponentStatus::DOWN, 'Аудиоагент не подключён — объявления не прозвучат.'],
            'playback_requests' => [ComponentStatus::DOWN, 'Сервис воспроизведения не принимает объявления.'],
            'playback_emergency' => [ComponentStatus::DOWN, 'Экстренные команды сейчас не обрабатываются.'],
            'playback_scheduler' => [ComponentStatus::DOWN, 'Повторы «продолжение регистрации» сейчас не звучат.'],
            'core_events' => [ComponentStatus::DOWN, 'Экран «Статус» может отставать: события воспроизведения не обрабатываются.'],
            'playing' => [ComponentStatus::WARNING, sprintf('Объявление звучит дольше %d минут — возможно, аудиоагент завис.', self::PLAYING_TOO_LONG_MINUTES)],
        ];

        $alerts = [];
        foreach ($components as $component) {
            [$status, $message] = $messages[$component->key] ?? [null, null];
            if (null !== $status && $component->status === $status) {
                $alerts[] = new SystemAlert($component->key, $message);
            }
        }

        return $alerts;
    }

    /**
     * @param list<QueueSnapshot>|null $queues
     */
    private function agent(?array $queues): ComponentStatus
    {
        if (null === $queues) {
            return new ComponentStatus('agent', 'Аудиоагент', ComponentStatus::UNKNOWN, 'Неизвестно: брокер недоступен.');
        }
        foreach ($queues as $queue) {
            if (self::AGENT_QUEUE === $queue->name && $queue->consumers > 0) {
                return new ComponentStatus('agent', 'Аудиоагент', ComponentStatus::OK, 'Подключён к очереди команд.');
            }
        }

        return new ComponentStatus('agent', 'Аудиоагент', ComponentStatus::DOWN, 'Не подключён: объявления не прозвучат.');
    }

    /**
     * @param list<HeartbeatRecord> $heartbeats
     */
    private function worker(string $key, string $name, string $transport, array $heartbeats, DateTimeImmutable $now): ComponentStatus
    {
        $latest = null;
        foreach ($heartbeats as $heartbeat) {
            if (in_array($transport, $heartbeat->transports, true) && (null === $latest || $heartbeat->lastSeenAt > $latest)) {
                $latest = $heartbeat->lastSeenAt;
            }
        }

        if (null === $latest) {
            return new ComponentStatus($key, $name, ComponentStatus::DOWN, 'Воркер ни разу не сообщал о себе.');
        }
        $age = $now->getTimestamp() - $latest->getTimestamp();
        if ($age > self::HEARTBEAT_STALE_SECONDS) {
            return new ComponentStatus($key, $name, ComponentStatus::DOWN, sprintf('Воркер молчит %s.', $this->duration($age)));
        }

        return new ComponentStatus($key, $name, ComponentStatus::OK, sprintf('Работает (сигнал %d с назад).', max(0, $age)));
    }

    /**
     * @param list<HeartbeatRecord> $heartbeats
     */
    private function playing(array $heartbeats, DateTimeImmutable $now): ComponentStatus
    {
        $report = null;
        foreach ($heartbeats as $heartbeat) {
            $fresh = $now->getTimestamp() - $heartbeat->lastSeenAt->getTimestamp() <= self::HEARTBEAT_STALE_SECONDS;
            if ($fresh && in_array('announcement_playback', $heartbeat->transports, true)
                && (null === $report || $heartbeat->lastSeenAt > $report->lastSeenAt)) {
                $report = $heartbeat;
            }
        }
        if (null === $report) {
            return new ComponentStatus('playing', 'Воспроизведение', ComponentStatus::UNKNOWN, 'Нет свежих сведений от playback.');
        }

        $pending = (int) ($report->details['pendingJobs'] ?? 0);
        $since = $report->details['playingSince'] ?? null;
        if (!is_string($since) || '' === $since) {
            return new ComponentStatus('playing', 'Воспроизведение', ComponentStatus::OK, sprintf('Сейчас тихо, ожидают: %d.', $pending));
        }

        $playingFor = $now->getTimestamp() - new DateTimeImmutable($since)->getTimestamp();
        $detail = sprintf('Звучит %s, ожидают: %d.', $this->duration($playingFor), $pending);

        return new ComponentStatus(
            'playing',
            'Воспроизведение',
            $playingFor > self::PLAYING_TOO_LONG_MINUTES * 60 ? ComponentStatus::WARNING : ComponentStatus::OK,
            $playingFor > self::PLAYING_TOO_LONG_MINUTES * 60 ? $detail.' Похоже, аудиоагент завис.' : $detail,
        );
    }

    /**
     * @param list<QueueSnapshot>|null $queues
     */
    private function backlog(?array $queues): ComponentStatus
    {
        if (null === $queues) {
            return new ComponentStatus('queues', 'Очереди сообщений', ComponentStatus::UNKNOWN, 'Неизвестно: брокер недоступен.');
        }

        $piling = [];
        $total = 0;
        foreach ($queues as $queue) {
            $total += $queue->messagesReady;
            if ($queue->messagesReady > self::QUEUE_BACKLOG_WARNING) {
                $piling[] = sprintf('%s: %d', $queue->name, $queue->messagesReady);
            }
        }

        return [] === $piling
            ? new ComponentStatus('queues', 'Очереди сообщений', ComponentStatus::OK, sprintf('Ожидают обработки: %d.', $total))
            : new ComponentStatus('queues', 'Очереди сообщений', ComponentStatus::WARNING, 'Копятся сообщения — '.implode(', ', $piling).'.');
    }

    private function duration(int $seconds): string
    {
        return $seconds < 120 ? sprintf('%d с', max(0, $seconds)) : sprintf('%d мин', intdiv($seconds, 60));
    }
}
