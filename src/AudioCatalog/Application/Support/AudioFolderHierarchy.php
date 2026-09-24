<?php

declare(strict_types=1);

namespace App\AudioCatalog\Application\Support;

use App\AudioCatalog\Domain\Entity\AudioFolder;
use App\AudioCatalog\Domain\Exception\DuplicateAudioFolderException;
use App\AudioCatalog\Domain\Exception\InvalidAudioFolderException;
use App\AudioCatalog\Domain\Repository\AudioFolderRepositoryInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Tree-wide folder invariants that a single AudioFolder cannot check alone:
 * sibling name uniqueness, maximum depth and no moving into a descendant. The
 * whole tree is loaded into memory — a library has tens of folders, not
 * thousands.
 */
final readonly class AudioFolderHierarchy
{
    public function __construct(private AudioFolderRepositoryInterface $folders)
    {
    }

    public function assertNameAvailable(?Uuid $parentId, string $name, ?Uuid $exceptId = null): void
    {
        $sibling = $this->folders->findByParentAndNameKey($parentId, AudioFolder::keyOf($name));
        if (null !== $sibling && (null === $exceptId || !$sibling->getId()->equals($exceptId))) {
            throw DuplicateAudioFolderException::withName(trim($name));
        }
    }

    /**
     * Placing a subtree of the given height (1 = a single folder) under the parent
     * must not exceed {@see AudioFolder::MAX_DEPTH}.
     */
    public function assertFits(?Uuid $parentId, int $subtreeHeight = 1): void
    {
        if ($this->depthOf($parentId) + $subtreeHeight > AudioFolder::MAX_DEPTH) {
            throw InvalidAudioFolderException::tooDeep(AudioFolder::MAX_DEPTH);
        }
    }

    public function assertNotInsideItself(Uuid $folderId, ?Uuid $newParentId): void
    {
        $parents = $this->parentMap();
        $cursor = $newParentId?->toRfc4122();
        $target = $folderId->toRfc4122();
        while (null !== $cursor) {
            if ($cursor === $target) {
                throw InvalidAudioFolderException::cycle();
            }
            $cursor = $parents[$cursor] ?? null;
        }
    }

    /** Levels in the subtree rooted at the folder, the folder itself included. */
    public function heightOf(Uuid $folderId): int
    {
        $children = [];
        foreach ($this->parentMap() as $id => $parentId) {
            if (null !== $parentId) {
                $children[$parentId][] = $id;
            }
        }

        $height = static function (string $id) use (&$height, $children): int {
            $max = 0;
            foreach ($children[$id] ?? [] as $childId) {
                $max = max($max, $height($childId));
            }

            return $max + 1;
        };

        return $height($folderId->toRfc4122());
    }

    /** 0 for the root, 1 for a top-level folder. */
    private function depthOf(?Uuid $folderId): int
    {
        $parents = $this->parentMap();
        $depth = 0;
        $cursor = $folderId?->toRfc4122();
        while (null !== $cursor && $depth <= AudioFolder::MAX_DEPTH + 1) {
            ++$depth;
            $cursor = $parents[$cursor] ?? null;
        }

        return $depth;
    }

    /** @return array<string, string|null> folder id => parent id */
    private function parentMap(): array
    {
        $map = [];
        foreach ($this->folders->findAll() as $folder) {
            $map[$folder->getId()->toRfc4122()] = $folder->getParentId()?->toRfc4122();
        }

        return $map;
    }
}
