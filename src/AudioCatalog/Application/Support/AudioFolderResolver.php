<?php

declare(strict_types=1);

namespace App\AudioCatalog\Application\Support;

use App\AudioCatalog\Domain\Entity\AudioFolder;
use App\AudioCatalog\Domain\Exception\AudioFolderNotFoundException;
use App\AudioCatalog\Domain\Repository\AudioFolderRepositoryInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Turns a folder id coming from a command into a reference to an existing folder.
 * Shared by every use case that places something into a folder: an asset upload
 * or edit, a new folder's parent, a moved folder's new parent.
 */
final readonly class AudioFolderResolver
{
    public function __construct(private AudioFolderRepositoryInterface $folders)
    {
    }

    /** Null or empty id means the library root («Без папки» for assets). */
    public function resolveId(?string $folderId): ?Uuid
    {
        if (null === $folderId || '' === trim($folderId)) {
            return null;
        }

        return $this->load($folderId)->getId();
    }

    public function load(string $folderId): AudioFolder
    {
        if (!Uuid::isValid($folderId)) {
            throw AudioFolderNotFoundException::withId($folderId);
        }

        return $this->folders->findById(Uuid::fromString($folderId))
            ?? throw AudioFolderNotFoundException::withId($folderId);
    }
}
