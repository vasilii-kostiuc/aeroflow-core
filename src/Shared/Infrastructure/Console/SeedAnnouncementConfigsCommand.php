<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Console;

use App\Announcements\Application\AddAnnouncementVariant\AddAnnouncementVariantCommand;
use App\Announcements\Application\CreateFlightAnnouncementConfig\CreateFlightAnnouncementConfigCommand;
use App\Announcements\Application\FlightAnnouncementConfigResult;
use App\Announcements\Application\ListFlightAnnouncementConfigs\ListFlightAnnouncementConfigsQuery;
use App\Announcements\Application\UpdateAnnouncementVariant\UpdateAnnouncementVariantCommand;
use App\Announcements\Domain\Enum\FlightAnnouncementType;
use App\FlightOperations\Application\FlightDefinitionResult;
use App\FlightOperations\Application\ListFlightDefinitions\ListFlightDefinitionsQuery;
use App\Shared\Application\Bus\ApplicationBus;
use App\Shared\Application\Pagination\PaginatedResult;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Throwable;

/**
 * Seeds text-based announcement configurations for the first N active flight
 * definitions, so every flight has ready-to-launch announcements without a
 * human filling in the admin UI segment by segment.
 *
 * Each configuration is a single `text` segment per language: saving a variant
 * runs the text through the TTS pre-step (task 022), which creates the backing
 * `AudioAsset(source=generated)`. So this command is also the way to warm up
 * generated assets — the aeroflow-tts service must be reachable.
 *
 * The command is idempotent at the flight+type level: an existing configuration
 * is skipped, never mutated, so re-running is safe. To fully reseed, drop the
 * existing `flight_announcement_config` rows first.
 */
#[AsCommand(
    name: 'app:announcements:seed-configs',
    description: 'Seed text-based announcement configs (and generate their TTS assets) for the first N active flights.',
)]
final class SeedAnnouncementConfigsCommand extends Command
{
    /**
     * Text templates per announcement type and language. `{flight}` is replaced
     * with the flight number. Text-only on purpose: no dynamic slots, so seeding
     * needs no pre-existing audio prompts for counters or gates.
     *
     * @var array<string, array<string, string>>
     */
    private const TEMPLATES = [
        'ru' => [
            'check_in_opening' => 'Уважаемые пассажиры! Начинается регистрация на рейс {flight}. Просим вас пройти к стойкам регистрации.',
            'check_in_continuation' => 'Уважаемые пассажиры рейса {flight}! Продолжается регистрация. Просим вас пройти к стойкам регистрации.',
            'check_in_closing' => 'Уважаемые пассажиры! Регистрация на рейс {flight} заканчивается. Просим вас завершить регистрацию.',
            'boarding_invitation' => 'Уважаемые пассажиры рейса {flight}! Приглашаем вас на посадку. Просим пройти к выходу на посадку.',
            'arrival' => 'Уважаемые встречающие! Рейс {flight} совершил посадку.',
        ],
        'en' => [
            'check_in_opening' => 'Attention please. Check-in for flight {flight} is now open. Please proceed to the check-in counters.',
            'check_in_continuation' => 'Attention passengers of flight {flight}. Check-in continues. Please proceed to the check-in counters.',
            'check_in_closing' => 'Attention please. Check-in for flight {flight} is now closing. Please complete your check-in.',
            'boarding_invitation' => 'Attention passengers of flight {flight}. Boarding is now starting. Please proceed to the boarding gate.',
            'arrival' => 'Attention please. Flight {flight} has now landed.',
        ],
        'ro-MD' => [
            'check_in_opening' => 'Stimați pasageri! Începe înregistrarea la cursa {flight}. Vă rugăm să vă prezentați la ghișeele de înregistrare.',
            'check_in_continuation' => 'Stimați pasageri ai cursei {flight}! Înregistrarea continuă. Vă rugăm să vă prezentați la ghișeele de înregistrare.',
            'check_in_closing' => 'Stimați pasageri! Înregistrarea la cursa {flight} se închide. Vă rugăm să finalizați înregistrarea.',
            'boarding_invitation' => 'Stimați pasageri ai cursei {flight}! Vă invităm la îmbarcare. Vă rugăm să vă prezentați la poarta de îmbarcare.',
            'arrival' => 'Stimați pasageri! Cursa {flight} a aterizat.',
        ],
    ];

    /**
     * Announcement types to configure per flight direction, in dispatch order.
     *
     * @var array<string, list<FlightAnnouncementType>>
     */
    private const TYPES_BY_DIRECTION = [
        'departure' => [
            FlightAnnouncementType::CheckInOpening,
            FlightAnnouncementType::CheckInContinuation,
            FlightAnnouncementType::CheckInClosing,
            FlightAnnouncementType::BoardingInvitation,
        ],
        'arrival' => [
            FlightAnnouncementType::Arrival,
        ],
    ];

    public function __construct(private readonly ApplicationBus $bus)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'How many active flights to seed', '10')
            ->addOption('languages', null, InputOption::VALUE_REQUIRED, 'Comma-separated language codes to configure', 'ru,en,ro-MD')
            ->addOption('repeat-minutes', null, InputOption::VALUE_REQUIRED, 'Repeat interval for check-in continuation', '10')
            ->addOption('revoice', null, InputOption::VALUE_NONE, 'Re-save variants that already exist, re-running TTS (use after a voice or model version change)')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Print what would be done without writing anything');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $limit = max(1, (int) $input->getOption('limit'));
        $repeatMinutes = max(1, (int) $input->getOption('repeat-minutes'));
        $dryRun = (bool) $input->getOption('dry-run');
        $revoice = (bool) $input->getOption('revoice');
        $languages = array_values(array_filter(array_map(
            'trim',
            explode(',', (string) $input->getOption('languages')),
        )));

        if ([] === $languages) {
            $io->error('No languages given.');

            return Command::FAILURE;
        }

        foreach ($languages as $language) {
            if (!isset(self::TEMPLATES[$language])) {
                $io->error(sprintf('No text templates for language "%s". Known: %s.', $language, implode(', ', array_keys(self::TEMPLATES))));

                return Command::FAILURE;
            }
        }

        $flights = $this->fetchActiveFlights($limit);
        if ([] === $flights) {
            $io->warning('No active flight definitions found.');

            return Command::SUCCESS;
        }

        $io->title(sprintf('Seeding announcement configs for %d flight(s)', count($flights)));
        if ($dryRun) {
            $io->note('Dry run: nothing will be written and no TTS assets will be generated.');
        }

        $variantsAdded = 0;
        $variantsRevoiced = 0;
        $variantsSkipped = 0;
        $configsCreated = 0;
        $failed = 0;

        foreach ($flights as $flight) {
            $types = self::TYPES_BY_DIRECTION[$flight->direction] ?? [];
            $io->section(sprintf('%s (%s)', $flight->flightNumber, $flight->direction));

            foreach ($types as $type) {
                $repeat = $type->requiresRepeatRule() ? $repeatMinutes : null;

                if ($dryRun) {
                    $io->writeln(sprintf('  would configure <info>%s</info> [%s]', $type->value, implode(', ', $languages)));

                    continue;
                }

                // Idempotent at the variant level: reuse an existing config and
                // only add the languages it is still missing, so a re-run (or a
                // half-finished earlier run) is completed rather than skipped.
                try {
                    $existing = $this->findConfig($flight->id, $type);
                    if (null === $existing) {
                        $configId = $this->createConfig($flight->id, $type, $repeat)->id;
                        ++$configsCreated;
                    } else {
                        $configId = $existing->id;
                    }
                } catch (Throwable $exception) {
                    $io->writeln(sprintf('  <error>fail</error> %s: %s', $type->value, $exception->getMessage()));
                    ++$failed;

                    continue;
                }

                $present = $this->existingVariants($existing);
                $added = [];
                $revoiced = [];
                $skipped = [];

                foreach ($languages as $index => $language) {
                    $variantId = $present[$language] ?? null;

                    if (null !== $variantId && !$revoice) {
                        $skipped[] = $language;
                        ++$variantsSkipped;

                        continue;
                    }

                    try {
                        if (null === $variantId) {
                            $this->addTextVariant($flight, $configId, $type, $language, $index + 1);
                            $added[] = $language;
                            ++$variantsAdded;
                        } else {
                            $this->revoiceTextVariant($flight, $configId, $variantId, $type, $language, $index + 1);
                            $revoiced[] = $language;
                            ++$variantsRevoiced;
                        }
                    } catch (Throwable $exception) {
                        $io->writeln(sprintf('  <error>fail</error> %s/%s: %s', $type->value, $language, $exception->getMessage()));
                        ++$failed;
                    }
                }

                $io->writeln(sprintf(
                    '  <info>%s</info> %s — added [%s]%s%s',
                    null === $existing ? 'new ' : 'have',
                    $type->value,
                    implode(', ', $added) ?: '—',
                    [] === $revoiced ? '' : sprintf(', revoiced [%s]', implode(', ', $revoiced)),
                    [] === $skipped ? '' : sprintf(', already present [%s]', implode(', ', $skipped)),
                ));
            }
        }

        $io->newLine();
        $io->success(sprintf(
            'Configs created: %d. Variants added: %d, revoiced: %d, already present: %d, failed: %d.',
            $configsCreated,
            $variantsAdded,
            $variantsRevoiced,
            $variantsSkipped,
            $failed,
        ));

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    private function findConfig(string $flightDefinitionId, FlightAnnouncementType $type): ?FlightAnnouncementConfigResult
    {
        try {
            $configs = $this->bus->handleList(
                new ListFlightAnnouncementConfigsQuery($flightDefinitionId),
                FlightAnnouncementConfigResult::class,
            );
        } catch (HandlerFailedException $exception) {
            throw $this->unwrap($exception);
        }

        foreach ($configs as $config) {
            if ($config->announcementType === $type->value) {
                return $config;
            }
        }

        return null;
    }

    /**
     * Variants the config already has, keyed by language code.
     *
     * @return array<string, string> language code => variant id
     */
    private function existingVariants(?FlightAnnouncementConfigResult $config): array
    {
        if (null === $config) {
            return [];
        }

        $byLanguage = [];
        foreach ($config->variants as $variant) {
            $byLanguage[(string) $variant->languageCode] = (string) $variant->id;
        }

        return $byLanguage;
    }

    /**
     * @return list<FlightDefinitionResult>
     */
    private function fetchActiveFlights(int $limit): array
    {
        /** @var PaginatedResult<FlightDefinitionResult> $result */
        $result = $this->bus->handleAs(
            new ListFlightDefinitionsQuery(active: true, page: 1, limit: $limit),
            PaginatedResult::class,
        );

        return $result->items;
    }

    private function createConfig(string $flightDefinitionId, FlightAnnouncementType $type, ?int $repeatMinutes): FlightAnnouncementConfigResult
    {
        try {
            return $this->bus->handleAs(
                new CreateFlightAnnouncementConfigCommand(
                    flightDefinitionId: $flightDefinitionId,
                    announcementType: $type->value,
                    enabled: true,
                    repeatEveryMinutes: $repeatMinutes,
                ),
                FlightAnnouncementConfigResult::class,
            );
        } catch (HandlerFailedException $exception) {
            throw $this->unwrap($exception);
        }
    }

    private function addTextVariant(
        FlightDefinitionResult $flight,
        string $configId,
        FlightAnnouncementType $type,
        string $language,
        int $sortOrder,
    ): void {
        $text = str_replace('{flight}', $flight->flightNumber, self::TEMPLATES[$language][$type->value]);

        try {
            $this->bus->handleAs(
                new AddAnnouncementVariantCommand(
                    flightDefinitionId: $flight->id,
                    configId: $configId,
                    languageCode: $language,
                    sortOrder: $sortOrder,
                    segments: [
                        ['sortOrder' => 1, 'type' => 'text', 'text' => $text],
                    ],
                    enabled: true,
                ),
                FlightAnnouncementConfigResult::class,
            );
        } catch (HandlerFailedException $exception) {
            throw $this->unwrap($exception);
        }
    }

    /**
     * Re-saves an existing variant with the same text so the TTS pre-step runs
     * again. Used after a voice or model version changes: the generation cache
     * keys on text + language + voice + model version, so a bumped version makes
     * this produce a fresh asset and supersede the stale one. With an unchanged
     * version it is a no-op that reuses the cached asset.
     */
    private function revoiceTextVariant(
        FlightDefinitionResult $flight,
        string $configId,
        string $variantId,
        FlightAnnouncementType $type,
        string $language,
        int $sortOrder,
    ): void {
        $text = str_replace('{flight}', $flight->flightNumber, self::TEMPLATES[$language][$type->value]);

        try {
            $this->bus->handleAs(
                new UpdateAnnouncementVariantCommand(
                    flightDefinitionId: $flight->id,
                    configId: $configId,
                    variantId: $variantId,
                    languageCode: $language,
                    sortOrder: $sortOrder,
                    segments: [
                        ['sortOrder' => 1, 'type' => 'text', 'text' => $text],
                    ],
                    enabled: true,
                ),
                FlightAnnouncementConfigResult::class,
            );
        } catch (HandlerFailedException $exception) {
            throw $this->unwrap($exception);
        }
    }

    /**
     * The synchronous command bus wraps a handler failure; surface the real
     * domain exception so callers can match on its type.
     */
    private function unwrap(HandlerFailedException $exception): Throwable
    {
        return $exception->getPrevious() ?? $exception;
    }
}
