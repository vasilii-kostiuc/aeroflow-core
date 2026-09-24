<?php

declare(strict_types=1);

namespace App\AudioCatalog\Application\ListAudioFolders;

use App\AudioCatalog\Application\AudioFolderResult;
use App\AudioCatalog\Domain\Entity\AudioFolder;
use App\AudioCatalog\Domain\Repository\AudioFolderRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/** Flat list of the whole tree; clients assemble the hierarchy from `parentId`. */
#[AsMessageHandler(bus: 'command.bus')]
final readonly class ListAudioFoldersHandler
{
    public function __construct(private AudioFolderRepositoryInterface $folders)
    {
    }

    /**
     * @return list<AudioFolderResult>
     */
    public function __invoke(ListAudioFoldersQuery $query): array
    {
        return array_map(
            static fn (AudioFolder $folder): AudioFolderResult => AudioFolderResult::fromEntity($folder),
            $this->folders->findAll(),
        );
    }
}
