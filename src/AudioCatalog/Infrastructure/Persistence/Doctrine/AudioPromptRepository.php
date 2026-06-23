<?php

declare(strict_types=1);

namespace App\AudioCatalog\Infrastructure\Persistence\Doctrine;

use App\AudioCatalog\Domain\Entity\AudioPrompt;
use App\AudioCatalog\Domain\Enum\AudioPromptKind;
use App\AudioCatalog\Domain\Exception\DuplicateAudioPromptException;
use App\AudioCatalog\Domain\Repository\AudioPromptRepositoryInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class AudioPromptRepository implements AudioPromptRepositoryInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(AudioPrompt $prompt): void
    {
        try {
            $this->entityManager->persist($prompt);
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            throw DuplicateAudioPromptException::forKey($prompt->getKind()->value, $prompt->getValue(), $prompt->getLanguageCode());
        }
    }

    public function findById(Uuid $id): ?AudioPrompt
    {
        return $this->entityManager->find(AudioPrompt::class, $id);
    }

    public function findActive(AudioPromptKind $kind, string $value, string $languageCode): ?AudioPrompt
    {
        return $this->entityManager->getRepository(AudioPrompt::class)->findOneBy([
            'kind' => $kind,
            'value' => strtoupper(trim($value)),
            'languageCode' => $languageCode,
            'active' => true,
        ]);
    }

    public function findAll(?AudioPromptKind $kind = null, ?string $value = null, ?string $languageCode = null, ?bool $active = null): array
    {
        $criteria = array_filter([
            'kind' => $kind,
            'value' => $value === null ? null : strtoupper(trim($value)),
            'languageCode' => $languageCode,
            'active' => $active,
        ], static fn (mixed $item): bool => $item !== null);

        return $this->entityManager->getRepository(AudioPrompt::class)->findBy($criteria, ['kind' => 'ASC', 'value' => 'ASC', 'languageCode' => 'ASC']);
    }
}
