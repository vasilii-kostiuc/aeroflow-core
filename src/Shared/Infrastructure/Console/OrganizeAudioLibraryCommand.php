<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Console;

use App\AudioCatalog\Application\AudioAssetResult;
use App\AudioCatalog\Application\AudioFolderResult;
use App\AudioCatalog\Application\CreateAudioFolder\CreateAudioFolderCommand;
use App\AudioCatalog\Application\ListAudioAssets\ListAudioAssetsQuery;
use App\AudioCatalog\Application\ListAudioFolders\ListAudioFoldersQuery;
use App\AudioCatalog\Application\MoveAudioAssets\MoveAudioAssetsCommand;
use App\AudioCatalog\Application\MoveAudioAssets\MoveAudioAssetsHandler;
use App\AudioCatalog\Application\UpdateAudioAsset\UpdateAudioAssetCommand;
use App\Shared\Application\Bus\ApplicationBus;
use App\Shared\Infrastructure\Console\AudioLibrary\AudioLibraryPlanner;
use App\Shared\Infrastructure\Console\AudioLibrary\AudioLibraryUsageReader;
use App\Shared\Infrastructure\Console\AudioLibrary\PlannedAsset;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Sorts the audio assets that existed before folders (task 031) into the library
 * tree by how they are used, and gives voiced assets readable titles (task 032).
 * See {@see AudioLibraryPlanner} for the rules. Writes go through the Audio
 * Catalog use cases, so folder invariants and domain events apply as usual.
 *
 * Safe to re-run: only assets without a folder move and only default titles
 * change. Also useful later, e.g. after new flight segments were voiced — those
 * land in «Без папки» until sorted.
 */
#[AsCommand(
    name: 'app:audio-library:organize',
    description: 'Sort existing audio assets into folders by where they are used and restore readable titles.',
)]
final class OrganizeAudioLibraryCommand extends Command
{
    /** @var array<string, string> "parentId|name key" => folder id */
    private array $folderIds = [];

    public function __construct(
        private readonly ApplicationBus $bus,
        private readonly AudioLibraryUsageReader $usages,
        private readonly AudioLibraryPlanner $planner,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show the plan without changing anything');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');

        $assets = $this->bus->handleList(new ListAudioAssetsQuery(), AudioAssetResult::class);
        $plan = $this->planner->plan($assets, $this->usages->read());

        $this->printPlan($io, $plan['planned'], $plan['skipped']);

        if ([] === $plan['planned']) {
            $io->success('Nothing to organize.');

            return Command::SUCCESS;
        }
        if ($dryRun) {
            $io->note('Dry run: nothing was changed. Run without --dry-run to apply.');

            return Command::SUCCESS;
        }

        $this->rememberFolders($this->bus->handleList(new ListAudioFoldersQuery(), AudioFolderResult::class));

        $moves = [];
        $renamed = 0;
        foreach ($plan['planned'] as $item) {
            $folderId = null !== $item->folderPath ? $this->ensureFolder($item->folderPath) : $item->currentFolderId;
            if (null !== $item->newTitle) {
                $this->bus->handleAs(new UpdateAudioAssetCommand($item->assetId, $item->newTitle, $folderId), AudioAssetResult::class);
                ++$renamed;
            } elseif (null !== $item->folderPath) {
                $moves[$folderId ?? ''][] = $item->assetId;
            }
        }
        $moved = 0;
        foreach ($moves as $folderId => $ids) {
            foreach (array_chunk($ids, MoveAudioAssetsHandler::MAX_ASSETS) as $chunk) {
                $moved += count($this->bus->handleList(
                    new MoveAudioAssetsCommand($chunk, '' === $folderId ? null : (string) $folderId),
                    AudioAssetResult::class,
                ));
            }
        }

        $io->success(sprintf('Done: %d assets retitled (and moved), %d more moved.', $renamed, $moved));

        return Command::SUCCESS;
    }

    /**
     * @param list<PlannedAsset>                    $planned
     * @param array<string, list<AudioAssetResult>> $skipped
     */
    private function printPlan(SymfonyStyle $io, array $planned, array $skipped): void
    {
        $rows = [];
        foreach ($planned as $item) {
            $folder = null !== $item->folderPath ? implode(' / ', $item->folderPath) : '(остаётся на месте)';
            $rows[$folder] ??= [$folder, 0, 0];
            ++$rows[$folder][1];
            if (null !== $item->newTitle) {
                ++$rows[$folder][2];
            }
        }
        ksort($rows);
        $io->table(['Папка', 'Файлов', 'Новых названий'], array_values($rows));

        if ($io->isVerbose()) {
            foreach ($planned as $item) {
                $io->writeln(sprintf(
                    '  %s → %s%s',
                    $item->currentTitle,
                    null !== $item->folderPath ? implode(' / ', $item->folderPath) : '(на месте)',
                    null !== $item->newTitle ? sprintf(' «%s»', $item->newTitle) : '',
                ));
            }
        }

        foreach ($skipped as $reason => $assets) {
            if ([] === $assets) {
                continue;
            }
            $io->writeln(sprintf('Оставлены как есть (%s): %d', $reason, count($assets)));
            foreach ($assets as $asset) {
                $io->writeln(sprintf('  - %s (%s)', $asset->title, $asset->languageCode));
            }
        }
    }

    /**
     * @param list<AudioFolderResult> $folders
     */
    private function rememberFolders(array $folders): void
    {
        foreach ($folders as $folder) {
            $this->folderIds[$this->key($folder->parentId, $folder->name)] = $folder->id;
        }
    }

    /**
     * Existing folders are reused by name (case-insensitively), missing ones are
     * created — e.g. if an admin deleted «Вылеты», it comes back.
     *
     * @param list<string> $path
     */
    private function ensureFolder(array $path): string
    {
        $parentId = null;
        foreach ($path as $name) {
            $key = $this->key($parentId, $name);
            if (!isset($this->folderIds[$key])) {
                $folder = $this->bus->handleAs(new CreateAudioFolderCommand($name, $parentId), AudioFolderResult::class);
                $this->folderIds[$key] = $folder->id;
            }
            $parentId = $this->folderIds[$key];
        }

        return (string) $parentId;
    }

    private function key(?string $parentId, string $name): string
    {
        return ($parentId ?? '').'|'.mb_strtolower(trim($name));
    }
}
