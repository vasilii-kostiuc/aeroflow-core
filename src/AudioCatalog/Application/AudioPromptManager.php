<?php

declare(strict_types=1);

namespace App\AudioCatalog\Application;

use App\AudioCatalog\Domain\Entity\AudioPrompt;
use App\AudioCatalog\Domain\Enum\AudioPromptKind;
use App\AudioCatalog\Domain\Exception\AudioPromptAssetUnavailableException;
use App\AudioCatalog\Domain\Exception\AudioPromptNotFoundException;
use App\AudioCatalog\Domain\Exception\DuplicateAudioPromptException;
use App\AudioCatalog\Domain\Repository\AudioAssetRepositoryInterface;
use App\AudioCatalog\Domain\Repository\AudioPromptRepositoryInterface;
use App\Shared\Domain\ValueObject\LanguageCode;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

final readonly class AudioPromptManager
{
    public function __construct(
        private AudioPromptRepositoryInterface $prompts,
        private AudioAssetRepositoryInterface $assets,
        #[Autowire(service: 'event.bus')]
        private MessageBusInterface $eventBus,
    ) {
    }

    /** @return array<string,mixed> */
    public function create(string $kind, string $value, string $language, string $assetId): array
    {
        $this->assertAsset($assetId);
        $promptKind = AudioPromptKind::from($kind);
        if ($this->prompts->findActive($promptKind, $value, $language) !== null) {
            throw DuplicateAudioPromptException::forKey($kind, $value, $language);
        }
        $prompt = AudioPrompt::create($promptKind, $value, LanguageCode::fromString($language), Uuid::fromString($assetId));
        $this->prompts->save($prompt);
        $this->dispatch($prompt);

        return $this->result($prompt);
    }

    /** @return array<string,mixed> */
    public function update(string $id, string $kind, string $value, string $language, string $assetId): array
    {
        $this->assertAsset($assetId);
        $prompt = $this->find($id);
        $prompt->update(AudioPromptKind::from($kind), $value, LanguageCode::fromString($language), Uuid::fromString($assetId));
        $this->prompts->save($prompt);
        $this->dispatch($prompt);

        return $this->result($prompt);
    }

    /** @return array<string,mixed> */
    public function status(string $id, bool $active): array
    {
        $prompt = $this->find($id);
        $active ? $prompt->activate() : $prompt->deactivate();
        $this->prompts->save($prompt);
        $this->dispatch($prompt);

        return $this->result($prompt);
    }

    /** @return list<array<string,mixed>> */
    public function list(?string $kind, ?string $value, ?string $language, ?bool $active): array
    {
        $items = $this->prompts->findAll($kind === null ? null : AudioPromptKind::from($kind), $value, $language, $active);

        return array_map($this->result(...), $items);
    }

    private function find(string $id): AudioPrompt
    {
        if (!Uuid::isValid($id)) {
            throw AudioPromptNotFoundException::withId($id);
        }

        return $this->prompts->findById(Uuid::fromString($id)) ?? throw AudioPromptNotFoundException::withId($id);
    }

    private function assertAsset(string $id): void
    {
        if (!Uuid::isValid($id) || ($asset = $this->assets->findById(Uuid::fromString($id))) === null || !$asset->isActive()) {
            throw AudioPromptAssetUnavailableException::withId($id);
        }
    }

    /** @return array<string,mixed> */
    private function result(AudioPrompt $prompt): array
    {
        return [
            'id' => $prompt->getId()->toRfc4122(),
            'kind' => $prompt->getKind()->value,
            'value' => $prompt->getValue(),
            'languageCode' => $prompt->getLanguageCode(),
            'audioAssetId' => $prompt->getAudioAssetId()->toRfc4122(),
            'active' => $prompt->isActive(),
            'createdAt' => $prompt->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $prompt->getUpdatedAt()->format(DATE_ATOM),
        ];
    }

    private function dispatch(AudioPrompt $prompt): void
    {
        foreach ($prompt->pullEvents() as $event) {
            $this->eventBus->dispatch($event);
        }
    }
}
