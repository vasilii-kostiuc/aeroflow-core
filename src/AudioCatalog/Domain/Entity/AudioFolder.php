<?php

declare(strict_types=1);

namespace App\AudioCatalog\Domain\Entity;

use App\AudioCatalog\Domain\Event\AudioFolderCreated;
use App\AudioCatalog\Domain\Event\AudioFolderDeleted;
use App\AudioCatalog\Domain\Event\AudioFolderMoved;
use App\AudioCatalog\Domain\Event\AudioFolderRenamed;
use App\AudioCatalog\Domain\Exception\InvalidAudioFolderException;
use App\Shared\Domain\AggregateRoot;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * An admin-managed folder of the audio library (task 031). Folders only organise
 * assets for people: no announcement rule depends on a folder, so the tree has no
 * system folders and no mapping from announcement types to folders.
 *
 * The parent is a bare id (rule #6), not an ORM relation. Invariants that need the
 * whole tree — sibling name uniqueness, depth and cycles — are checked by the
 * application layer against the repository; the partial unique indexes on
 * `name_key` are the last line of defence against concurrent writes.
 */
#[ORM\Entity]
#[ORM\Table(name: 'audio_folder')]
#[ORM\Index(name: 'IDX_AUDIO_FOLDER_PARENT', columns: ['parent_id'])]
#[ORM\UniqueConstraint(name: 'UNIQ_AUDIO_FOLDER_CHILD_NAME', columns: ['parent_id', 'name_key'], options: ['where' => 'parent_id IS NOT NULL'])]
#[ORM\UniqueConstraint(name: 'UNIQ_AUDIO_FOLDER_ROOT_NAME', columns: ['name_key'], options: ['where' => 'parent_id IS NULL'])]
final class AudioFolder extends AggregateRoot
{
    public const int MAX_NAME_LENGTH = 100;
    public const int MAX_DEPTH = 4;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\Column(length: self::MAX_NAME_LENGTH)]
    private string $name;

    /** Case-folded name backing the sibling-uniqueness indexes. */
    #[ORM\Column(length: self::MAX_NAME_LENGTH)]
    private string $nameKey;

    #[ORM\Column(type: UuidType::NAME, nullable: true)]
    private ?Uuid $parentId;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    #[ORM\Column]
    private DateTimeImmutable $updatedAt;

    private function __construct()
    {
    }

    public static function create(string $name, ?Uuid $parentId): self
    {
        $now = self::now();
        $folder = new self();
        $folder->id = Uuid::v7();
        $folder->name = self::normalizeName($name);
        $folder->nameKey = self::keyOf($folder->name);
        $folder->parentId = $parentId;
        $folder->createdAt = $now;
        $folder->updatedAt = $now;
        $folder->recordEvent(new AudioFolderCreated(
            $folder->id->toRfc4122(),
            $folder->name,
            $parentId?->toRfc4122(),
            $now,
        ));

        return $folder;
    }

    public function rename(string $name): bool
    {
        $name = self::normalizeName($name);
        if ($this->name === $name) {
            return false;
        }
        $this->name = $name;
        $this->nameKey = self::keyOf($name);
        $this->updatedAt = self::now();
        $this->recordEvent(new AudioFolderRenamed($this->id->toRfc4122(), $name, $this->updatedAt));

        return true;
    }

    /**
     * Moving into itself is rejected here; moving into a descendant needs the tree
     * and is rejected by the application layer before this call.
     */
    public function moveTo(?Uuid $parentId): bool
    {
        if (null !== $parentId && $parentId->equals($this->id)) {
            throw InvalidAudioFolderException::cycle();
        }
        if (self::sameParent($this->parentId, $parentId)) {
            return false;
        }
        $this->parentId = $parentId;
        $this->updatedAt = self::now();
        $this->recordEvent(new AudioFolderMoved($this->id->toRfc4122(), $parentId?->toRfc4122(), $this->updatedAt));

        return true;
    }

    /**
     * Records the removal fact; emptiness is an application-level precondition
     * because it depends on other folders and on assets.
     */
    public function delete(): void
    {
        $this->recordEvent(new AudioFolderDeleted($this->id->toRfc4122(), self::now()));
    }

    public static function keyOf(string $name): string
    {
        return mb_strtolower(self::normalizeName($name));
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getNameKey(): string
    {
        return $this->nameKey;
    }

    public function getParentId(): ?Uuid
    {
        return $this->parentId;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    private static function normalizeName(string $name): string
    {
        $name = trim((string) preg_replace('/\s+/u', ' ', $name));
        if ('' === $name) {
            throw InvalidAudioFolderException::emptyName();
        }
        if (mb_strlen($name) > self::MAX_NAME_LENGTH) {
            throw InvalidAudioFolderException::nameTooLong(self::MAX_NAME_LENGTH);
        }
        if (str_contains($name, '/')) {
            throw InvalidAudioFolderException::slashInName();
        }

        return $name;
    }

    private static function sameParent(?Uuid $a, ?Uuid $b): bool
    {
        if (null === $a || null === $b) {
            return $a === $b;
        }

        return $a->equals($b);
    }

    private static function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}
