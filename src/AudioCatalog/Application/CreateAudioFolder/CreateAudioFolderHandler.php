<?php

declare(strict_types=1);

namespace App\AudioCatalog\Application\CreateAudioFolder;

use App\AudioCatalog\Application\AudioFolderResult;
use App\AudioCatalog\Application\Support\AudioFolderHierarchy;
use App\AudioCatalog\Application\Support\AudioFolderResolver;
use App\AudioCatalog\Domain\Entity\AudioFolder;
use App\AudioCatalog\Domain\Repository\AudioFolderRepositoryInterface;
use App\Shared\Application\Event\DomainEventPublisher;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class CreateAudioFolderHandler
{
    public function __construct(
        private AudioFolderRepositoryInterface $folders,
        private AudioFolderResolver $resolver,
        private AudioFolderHierarchy $hierarchy,
        private DomainEventPublisher $events,
    ) {
    }

    public function __invoke(CreateAudioFolderCommand $command): AudioFolderResult
    {
        $parentId = $this->resolver->resolveId($command->parentId);
        $folder = AudioFolder::create($command->name, $parentId);

        $this->hierarchy->assertFits($parentId);
        $this->hierarchy->assertNameAvailable($parentId, $folder->getName());

        $this->folders->save($folder);
        $this->events->publish(...$folder->pullEvents());

        return AudioFolderResult::fromEntity($folder);
    }
}
