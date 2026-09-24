<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\AudioCatalog\Domain\Entity\AudioFolder;
use App\AudioCatalog\Domain\Repository\AudioFolderRepositoryInterface;
use Symfony\Component\Uid\Uuid;

final class InMemoryAudioFolderRepository implements AudioFolderRepositoryInterface
{
    /** @var array<string, AudioFolder> */
    private array $folders = [];

    public function __construct(AudioFolder ...$folders)
    {
        foreach ($folders as $folder) {
            $this->save($folder);
        }
    }

    public function save(AudioFolder $folder): void
    {
        $this->folders[$folder->getId()->toRfc4122()] = $folder;
    }

    public function remove(AudioFolder $folder): void
    {
        unset($this->folders[$folder->getId()->toRfc4122()]);
    }

    public function findById(Uuid $id): ?AudioFolder
    {
        return $this->folders[$id->toRfc4122()] ?? null;
    }

    public function findAll(): array
    {
        $folders = array_values($this->folders);
        usort($folders, static fn (AudioFolder $a, AudioFolder $b): int => $a->getNameKey() <=> $b->getNameKey());

        return $folders;
    }

    public function findByParentAndNameKey(?Uuid $parentId, string $nameKey): ?AudioFolder
    {
        foreach ($this->folders as $folder) {
            $sameParent = null === $parentId
                ? null === $folder->getParentId()
                : null !== $folder->getParentId() && $folder->getParentId()->equals($parentId);
            if ($sameParent && $folder->getNameKey() === $nameKey) {
                return $folder;
            }
        }

        return null;
    }

    public function hasChildren(Uuid $id): bool
    {
        foreach ($this->folders as $folder) {
            if (null !== $folder->getParentId() && $folder->getParentId()->equals($id)) {
                return true;
            }
        }

        return false;
    }
}
