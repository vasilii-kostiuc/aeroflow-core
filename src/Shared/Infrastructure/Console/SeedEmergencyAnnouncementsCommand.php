<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Console;

use App\Announcements\Application\CreateEmergencyTemplate\CreateEmergencyTemplateCommand;
use App\Announcements\Application\EmergencyTemplateResult;
use App\Announcements\Application\ListEmergencyTemplates\ListEmergencyTemplatesQuery;
use App\Announcements\Application\UpdateEmergencyTemplate\UpdateEmergencyTemplateCommand;
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
 * Seeds the emergency announcement presets (task 026) — the heir of the legacy САО
 * "Экстренные" set, whose user guide documents `Пожар` and `Проверка` пожарной
 * сигнализации. Mirror of SeedSupplementaryAnnouncementsCommand: each preset gets one
 * language variant per requested language, backed by an
 * `AudioAsset(source=generated)` produced through the TTS pre-step (task 021), so the
 * aeroflow-tts service must be reachable.
 *
 * Seeding only fills the catalog — nothing is launched and no emergency mode is
 * activated; that happens when the dispatcher picks a preset in the panel.
 *
 * Idempotent by preset name: an existing preset is skipped, never mutated, so
 * re-running is safe. With --revoice an existing preset is re-generated and updated
 * (use after a voice or model version change).
 */
#[AsCommand(
    name: 'app:announcements:seed-emergency',
    description: 'Seed emergency announcement presets (and generate their TTS assets).',
)]
final class SeedEmergencyAnnouncementsCommand extends Command
{
    /**
     * Preset name (semantic, in the ubiquitous language) => text per language.
     *
     * @var array<string, array<string, string>>
     */
    private const PRESETS = [
        'Пожар' => [
            'ru' => 'Внимание! В здании аэропорта объявлена пожарная тревога. Просим всех пассажиров и посетителей покинуть здание через ближайший выход, следуя указаниям сотрудников аэропорта.',
            'en' => 'Attention. A fire alarm has been activated in the terminal. All passengers and visitors are asked to leave the building through the nearest exit, following the instructions of airport staff.',
            'ro-MD' => 'Atenție! În clădirea aeroportului a fost declanșată alarma de incendiu. Rugăm toți pasagerii și vizitatorii să părăsească clădirea prin cea mai apropiată ieșire, urmând indicațiile personalului aeroportului.',
        ],
        'Проверка пожарной сигнализации' => [
            'ru' => 'Внимание! Проводится плановая проверка пожарной сигнализации. Просим сохранять спокойствие и не покидать здание. Это учебный сигнал.',
            'en' => 'Attention. This is a scheduled test of the fire alarm system. Please remain calm and stay in the building. This is a test signal only.',
            'ro-MD' => 'Atenție! Se efectuează verificarea planificată a sistemului de alarmă de incendiu. Vă rugăm să păstrați calmul și să nu părăsiți clădirea. Este doar un semnal de test.',
        ],
        'Эвакуация' => [
            'ru' => 'Внимание! Объявляется эвакуация здания аэропорта. Немедленно проследуйте к ближайшему эвакуационному выходу. Не пользуйтесь лифтами. Выполняйте указания сотрудников аэропорта.',
            'en' => 'Attention. The airport terminal is being evacuated. Please proceed immediately to the nearest emergency exit. Do not use the lifts. Follow the instructions of airport staff.',
            'ro-MD' => 'Atenție! Se anunță evacuarea clădirii aeroportului. Îndreptați-vă imediat spre cea mai apropiată ieșire de urgență. Nu folosiți lifturile. Urmați indicațiile personalului aeroportului.',
        ],
        'Отмена тревоги' => [
            'ru' => 'Внимание! Сигнал тревоги отменён. Обслуживание пассажиров возобновляется в обычном режиме. Благодарим за понимание.',
            'en' => 'Attention. The alarm has been cancelled. Passenger services are resuming as normal. Thank you for your cooperation.',
            'ro-MD' => 'Atenție! Semnalul de alarmă a fost anulat. Deservirea pasagerilor se reia în regim obișnuit. Vă mulțumim pentru înțelegere.',
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

        $io->title(sprintf('Seeding %d emergency preset(s) in [%s]', count(self::PRESETS), implode(', ', $languages)));
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
                    $this->bus->handleAs(new CreateEmergencyTemplateCommand($name, $variants), EmergencyTemplateResult::class);
                    $io->writeln(sprintf('  <info>create</info> %s [%s]', $name, implode(', ', $languages)));
                    ++$created;
                } else {
                    $this->bus->handleAs(new UpdateEmergencyTemplateCommand($current->id, $name, $variants), EmergencyTemplateResult::class);
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
     * @return array<string, EmergencyTemplateResult> preset name => existing template
     */
    private function existingByName(): array
    {
        $templates = $this->bus->handleList(new ListEmergencyTemplatesQuery(), EmergencyTemplateResult::class);

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
