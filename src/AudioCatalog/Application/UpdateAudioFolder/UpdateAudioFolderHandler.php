<?php

declare(strict_types=1);

namespace App\AudioCatalog\Application\UpdateAudioFolder;

use App\AudioCatalog\Application\AudioFolderResult;
use App\AudioCatalog\Application\Support\AudioFolderHierarchy;
use App\AudioCatalog\Application\Support\AudioFolderResolver;
use App\AudioCatalog\Domain\Repository\AudioFolderRepositoryInterface;
use App\Shared\Application\Event\DomainEventPublisher;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class UpdateAudioFolderHandler
{
    public function __construct(
        private AudioFolderRepositoryInterface $folders,
        private AudioFolderResolver $resolver,
        private AudioFolderHierarchy $hierarchy,
        private DomainEventPublisher $events,
    ) {
    }

    public function __invoke(UpdateAudioFolderCommand $command): AudioFolderResult
    {
        $folder = $this->resolver->load($command->id);
        $parentId = $this->resolver->resolveId($command->parentId);

        // Tree checks run against the tree as it is now, before the folder moves.
        $this->hierarchy->assertNotInsideItself($folder->getId(), $parentId);
        $this->hierarchy->assertFits($parentId, $this->hierarchy->heightOf($folder->getId()));

        $folder->moveTo($parentId);
        $folder->rename($command->name);
        $this->hierarchy->assertNameAvailable($parentId, $folder->getName(), $folder->getId());

        $this->folders->save($folder);
        $this->events->publish(...$folder->pullEvents());

        return AudioFolderResult::fromEntity($folder);
    }
}
