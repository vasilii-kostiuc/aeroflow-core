<?php

declare(strict_types=1);

namespace App\AudioCatalog\Application\DeleteAudioFolder;

use App\AudioCatalog\Application\Support\AudioFolderResolver;
use App\AudioCatalog\Domain\Exception\AudioFolderNotEmptyException;
use App\AudioCatalog\Domain\Repository\AudioAssetRepositoryInterface;
use App\AudioCatalog\Domain\Repository\AudioFolderRepositoryInterface;
use App\Shared\Application\Event\DomainEventPublisher;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Only an empty folder can be deleted: no subfolders and no active assets.
 * Inactive assets are invisible in the library, so instead of blocking the delete
 * with a folder that looks empty they are moved to the root in the same
 * transaction (command.bus wraps the handler in one).
 */
#[AsMessageHandler(bus: 'command.bus')]
final readonly class DeleteAudioFolderHandler
{
    public function __construct(
        private AudioFolderRepositoryInterface $folders,
        private AudioAssetRepositoryInterface $assets,
        private AudioFolderResolver $resolver,
        private DomainEventPublisher $events,
    ) {
    }

    public function __invoke(DeleteAudioFolderCommand $command): void
    {
        $folder = $this->resolver->load($command->id);

        if ($this->folders->hasChildren($folder->getId()) || $this->assets->countActiveInFolder($folder->getId()) > 0) {
            throw AudioFolderNotEmptyException::withId($command->id);
        }

        $events = [];
        foreach ($this->assets->findInactiveInFolder($folder->getId()) as $asset) {
            $asset->updateDetails($asset->getTitle(), null);
            $this->assets->save($asset);
            array_push($events, ...$asset->pullEvents());
        }

        $folder->delete();
        array_push($events, ...$folder->pullEvents());
        $this->folders->remove($folder);

        $this->events->publish(...$events);
    }
}
