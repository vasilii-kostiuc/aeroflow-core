<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Console;

use App\Announcements\Application\CreateSupplementaryTemplate\CreateSupplementaryTemplateCommand;
use App\Announcements\Application\ListSupplementaryTemplates\ListSupplementaryTemplatesQuery;
use App\Announcements\Application\SupplementaryTemplateResult;
use App\Announcements\Application\UpdateSupplementaryTemplate\UpdateSupplementaryTemplateCommand;
use App\AudioCatalog\Application\AudioAssetResult;
use App\AudioCatalog\Application\GenerateAudioAsset\GenerateAudioAssetCommand;
use App\Shared\Application\Bus\ApplicationBus;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Throwable;

/**
 * Seeds the airport-wide supplementary announcement presets (task 024) — the heir of
 * the legacy САО "Дополнительно" set (грипп, такси, безопасность, …). Each preset is
 * created with one language variant per requested language; the backing
 * `AudioAsset(source=generated)` is produced by running the text through the TTS
 * pre-step (task 021), so this command also warms up those assets — the aeroflow-tts
 * service must be reachable.
 *
 * Idempotent by preset name: an existing preset is skipped, never mutated, so
 * re-running is safe. With --revoice an existing preset is re-generated and updated
 * (use after a voice or model version change).
 */
#[AsCommand(
    name: 'app:announcements:seed-supplementary',
    description: 'Seed supplementary announcement presets (and generate their TTS assets).',
)]
final class SeedSupplementaryAnnouncementsCommand extends Command
{
    /**
     * Preset name (semantic, in the ubiquitous language) => text per language.
     *
     * @var array<string, array<string, string>>
     */
    private const PRESETS = [
        'Безопасность' => [
            'ru' => 'Уважаемые пассажиры! Не оставляйте багаж без присмотра. Обо всех подозрительных предметах сообщайте сотрудникам аэропорта.',
            'en' => 'Attention please. Do not leave your baggage unattended. Report any suspicious items to airport staff.',
            'ro-MD' => 'Stimați pasageri! Nu lăsați bagajele nesupravegheate. Anunțați personalul aeroportului despre orice obiect suspect.',
        ],
        'Такси' => [
            'ru' => 'Уважаемые пассажиры! Пользуйтесь услугами официального такси и остерегайтесь нелегальных перевозчиков.',
            'en' => 'Attention please. Please use official airport taxi services and avoid unlicensed drivers.',
            'ro-MD' => 'Stimați pasageri! Vă rugăm să folosiți serviciile oficiale de taxi și să evitați transportatorii ilegali.',
        ],
        'Ручная кладь' => [
            'ru' => 'Уважаемые пассажиры! Обратите внимание на нормы провоза ручной клади. Уточнить правила можно у сотрудников авиакомпании.',
            'en' => 'Attention please. Please observe the hand luggage allowance. Airline staff can help with any questions.',
            'ro-MD' => 'Stimați pasageri! Respectați normele privind bagajul de mână. Personalul companiei aeriene vă poate oferi detalii.',
        ],
        'Бесхозные вещи' => [
            'ru' => 'Уважаемые пассажиры! Обнаруженные без присмотра вещи будут изъяты службой безопасности аэропорта.',
            'en' => 'Attention please. Any unattended items will be removed by airport security.',
            'ro-MD' => 'Stimați pasageri! Obiectele lăsate nesupravegheate vor fi ridicate de serviciul de securitate al aeroportului.',
        ],
        'Регистрация' => [
            'ru' => 'Уважаемые пассажиры! Просим завершать регистрацию заблаговременно, не позднее чем за сорок минут до вылета.',
            'en' => 'Attention please. Please complete your check-in in good time, no later than forty minutes before departure.',
            'ro-MD' => 'Stimați pasageri! Vă rugăm să finalizați înregistrarea din timp, cu cel puțin patruzeci de minute înainte de plecare.',
        ],
        'Зелёный коридор' => [
            'ru' => 'Уважаемые пассажиры! При отсутствии товаров, подлежащих декларированию, вы можете воспользоваться зелёным коридором.',
            'en' => 'Attention please. If you have nothing to declare, you may use the green channel.',
            'ro-MD' => 'Stimați pasageri! Dacă nu aveți bunuri de declarat, puteți folosi culoarul verde.',
        ],
        'Профилактика гриппа' => [
            'ru' => 'Уважаемые пассажиры! В целях профилактики гриппа и простудных заболеваний просим соблюдать правила личной гигиены.',
            'en' => 'Attention please. To help prevent the spread of influenza, please observe personal hygiene rules.',
            'ro-MD' => 'Stimați pasageri! Pentru prevenirea gripei, vă rugăm să respectați regulile de igienă personală.',
        ],
    ];

    public function __construct(private readonly ApplicationBus $bus)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('languages', null, InputOption::VALUE_REQUIRED, 'Comma-separated language codes to voice', 'ru,en,ro-MD')
            ->addOption('revoice', null, InputOption::VALUE_NONE, 'Re-generate and update presets that already exist (use after a voice or model version change)')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Print what would be done without writing anything or calling TTS');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

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
            foreach (self::PRESETS as $name => $texts) {
                if (!isset($texts[$language])) {
                    $io->error(sprintf('Preset "%s" has no text for language "%s". Known: %s.', $name, $language, implode(', ', array_keys($texts))));

                    return Command::FAILURE;
                }
            }
        }

        $existing = $this->existingByName();

        $io->title(sprintf('Seeding %d supplementary preset(s) in [%s]', count(self::PRESETS), implode(', ', $languages)));
        if ($dryRun) {
            $io->note('Dry run: nothing will be written and no TTS assets will be generated.');
        }

        $created = 0;
        $revoiced = 0;
        $skipped = 0;
        $failed = 0;

        foreach (self::PRESETS as $name => $texts) {
            $current = $existing[$name] ?? null;
            if (null !== $current && !$revoice) {
                $io->writeln(sprintf('  <comment>skip</comment> %s (already present)', $name));
                ++$skipped;

                continue;
            }

            if ($dryRun) {
                $io->writeln(sprintf('  would %s <info>%s</info> [%s]', null === $current ? 'create' : 'revoice', $name, implode(', ', $languages)));

                continue;
            }

            try {
                $variants = [];
                foreach ($languages as $language) {
                    $assetId = $this->generateAsset($texts[$language], $language);
                    $variants[] = ['languageCode' => $language, 'audioAssetId' => $assetId];
                }

                if (null === $current) {
                    $this->bus->handleAs(new CreateSupplementaryTemplateCommand($name, $variants), SupplementaryTemplateResult::class);
                    $io->writeln(sprintf('  <info>create</info> %s [%s]', $name, implode(', ', $languages)));
                    ++$created;
                } else {
                    $this->bus->handleAs(new UpdateSupplementaryTemplateCommand($current->id, $name, $variants), SupplementaryTemplateResult::class);
                    $io->writeln(sprintf('  <info>revoice</info> %s [%s]', $name, implode(', ', $languages)));
                    ++$revoiced;
                }
            } catch (Throwable $exception) {
                $io->writeln(sprintf('  <error>fail</error> %s: %s', $name, $this->unwrap($exception)->getMessage()));
                ++$failed;
            }
        }

        $io->newLine();
        $io->success(sprintf(
            'Presets created: %d, revoiced: %d, already present: %d, failed: %d.',
            $created,
            $revoiced,
            $skipped,
            $failed,
        ));

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * @return array<string, SupplementaryTemplateResult> preset name => existing template
     */
    private function existingByName(): array
    {
        $templates = $this->bus->handleList(new ListSupplementaryTemplatesQuery(), SupplementaryTemplateResult::class);

        $byName = [];
        foreach ($templates as $template) {
            $byName[$template->name] = $template;
        }

        return $byName;
    }

    private function generateAsset(string $text, string $languageCode): string
    {
        $result = $this->bus->handleAs(
            new GenerateAudioAssetCommand($text, $languageCode),
            AudioAssetResult::class,
        );

        return $result->id;
    }

    /**
     * The synchronous command bus wraps a handler failure; surface the real domain
     * exception so its message is shown.
     */
    private function unwrap(Throwable $exception): Throwable
    {
        if ($exception instanceof HandlerFailedException) {
            return $exception->getPrevious() ?? $exception;
        }

        return $exception;
    }
}
