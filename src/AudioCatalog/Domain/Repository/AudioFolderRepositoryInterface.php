<?php

declare(strict_types=1);

namespace App\AudioCatalog\Domain\Repository;

use App\AudioCatalog\Domain\Entity\AudioFolder;
use Symfony\Component\Uid\Uuid;

interface AudioFolderRepositoryInterface
{
    public function save(AudioFolder $folder): void;

    public function remove(AudioFolder $folder): void;

    public function findById(Uuid $id): ?AudioFolder;

    /**
     * The whole tree as a flat list, ordered by name. The library is small enough
     * that tree invariants (depth, cycles) are checked in memory.
     *
     * @return list<AudioFolder>
     */
    public function findAll(): array;

    /** Sibling with the same case-folded name under the given parent (null = top level). */
    public function findByParentAndNameKey(?Uuid $parentId, string $nameKey): ?AudioFolder;

    public function hasChildren(Uuid $id): bool;
}
