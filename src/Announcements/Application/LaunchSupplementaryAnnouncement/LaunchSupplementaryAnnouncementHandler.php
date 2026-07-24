<?php

declare(strict_types=1);

namespace App\Announcements\Application\LaunchSupplementaryAnnouncement;

use App\Announcements\Application\AnnouncementResult;
use App\Announcements\Application\Port\AudioCatalog\AudioPromptLookupInterface;
use App\Announcements\Domain\Entity\Announcement;
use App\Announcements\Domain\Enum\AnnouncementType;
use App\Announcements\Domain\Exception\AudioAssetUnavailableException;
use App\Announcements\Domain\Exception\InactiveSupplementaryTemplateException;
use App\Announcements\Domain\Exception\SupplementaryTemplateNotFoundException;
use App\Announcements\Domain\Repository\AnnouncementRepositoryInterface;
use App\Announcements\Domain\Repository\SupplementaryAnnouncementTemplateRepositoryInterface;
use App\Announcements\Domain\ValueObject\AnnouncementLanguages;
use App\Shared\Application\Event\DomainEventPublisher;
use App\Shared\Application\Uuid\UuidParser;
use App\Shared\Domain\ValueObject\LanguageCode;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Launches an airport-wide supplementary announcement (task 024) from a preset. The
 * dispatcher picks a template (heir of the legacy "Дополнительно" set) and a subset
 * of its languages; the handler resolves the preset's per-language audio assets into
 * an immutable AudioSequence and stores a non-flight
 * Announcement(type=supplementary). The post-commit publisher then emits
 * RequestAnnouncementPlayback with priority 50 on AnnouncementCreated, exactly like
 * flight announcements.
 *
 * No TTS on the launch path: the preset audio is prepared at admin save time (upload
 * or task 021 generation), so launching only assembles ready assets. An asset that
 * was deactivated after the preset was saved is rejected here, not silently played.
 */
#[AsMessageHandler(bus: 'command.bus')]
final readonly class LaunchSupplementaryAnnouncementHandler
{
    public function __construct(
        private SupplementaryAnnouncementTemplateRepositoryInterface $templates,
        private AnnouncementRepositoryInterface $announcements,
        private AudioPromptLookupInterface $audioCatalog,
        private UuidParser $uuidParser,
        private DomainEventPublisher $events,
    ) {
    }

    public function __invoke(LaunchSupplementaryAnnouncementCommand $command): AnnouncementResult
    {
        $template = $this->templates->findById($this->uuidParser->parse($command->templateId))
            ?? throw SupplementaryTemplateNotFoundException::withId($command->templateId);
        if (!$template->isActive()) {
            throw InactiveSupplementaryTemplateException::withId($command->templateId);
        }

        // Default to all configured languages; an explicit subset is validated against
        // the template (assetsForLanguages rejects a language it does not carry).
        $requestedCodes = [] === $command->languages ? $template->getLanguageCodes() : $command->languages;
        $languages = AnnouncementLanguages::fromCodes(...array_map(
            static fn (string $code): LanguageCode => LanguageCode::fromString($code),
            $requestedCodes,
        ));

        $audioSequence = [];
        $sortOrder = 1;
        foreach ($template->assetsForLanguages($languages->toStrings()) as $variant) {
            if (!$this->audioCatalog->isActiveAsset($variant['audioAssetId'])) {
                throw AudioAssetUnavailableException::withId($variant['audioAssetId']);
            }
            $audioSequence[] = [
                'languageCode' => $variant['languageCode'],
                'sortOrder' => $sortOrder++,
                'items' => [['type' => 'audio_asset', 'audioAssetId' => $variant['audioAssetId']]],
            ];
        }

        $announcement = Announcement::createPrepared(
            AnnouncementType::Supplementary,
            null,
            $languages,
            [],
            null,
            $audioSequence,
        );
        $this->announcements->save($announcement);
        $this->events->publish(...$announcement->pullEvents());

        return AnnouncementResult::fromEntity($announcement);
    }
}
