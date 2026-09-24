<?php

declare(strict_types=1);

namespace App\Announcements\Application\LaunchEmergencyAnnouncement;

use App\Announcements\Application\AnnouncementResult;
use App\Announcements\Application\Port\AudioCatalog\AudioPromptLookupInterface;
use App\Announcements\Domain\Entity\Announcement;
use App\Announcements\Domain\Enum\AnnouncementType;
use App\Announcements\Domain\Exception\AudioAssetUnavailableException;
use App\Announcements\Domain\Exception\EmergencyTemplateNotFoundException;
use App\Announcements\Domain\Exception\InactiveEmergencyTemplateException;
use App\Announcements\Domain\Repository\AnnouncementRepositoryInterface;
use App\Announcements\Domain\Repository\EmergencyAnnouncementTemplateRepositoryInterface;
use App\Announcements\Domain\ValueObject\AnnouncementLanguages;
use App\Shared\Application\Event\DomainEventPublisher;
use App\Shared\Application\Uuid\UuidParser;
use App\Shared\Domain\ValueObject\LanguageCode;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Launches an airport-wide emergency announcement (task 026) from a preset — the
 * mirror of LaunchSupplementaryAnnouncementHandler: resolve the preset's per-language
 * audio into an immutable AudioSequence and store a non-flight
 * Announcement(type=emergency). As with every other announcement, no audio is
 * generated on the launch path; the preset is voiced when the admin saves it.
 *
 * One dispatcher action, two post-commit effects on the single AnnouncementCreated
 * fact: the usual publisher emits RequestAnnouncementPlayback (priority 1000, the
 * preemptive threshold of playback) and PublishEmergencyModeActivation emits
 * ActivateEmergencyMode, which suspends the ordinary queue until the dispatcher
 * lifts the mode. Neither belongs here: this handler owns the announcement, the
 * post-commit handlers own the integration.
 */
#[AsMessageHandler(bus: 'command.bus')]
final readonly class LaunchEmergencyAnnouncementHandler
{
    public function __construct(
        private EmergencyAnnouncementTemplateRepositoryInterface $templates,
        private AnnouncementRepositoryInterface $announcements,
        private AudioPromptLookupInterface $audioCatalog,
        private UuidParser $uuidParser,
        private DomainEventPublisher $events,
    ) {
    }

    public function __invoke(LaunchEmergencyAnnouncementCommand $command): AnnouncementResult
    {
        $template = $this->templates->findById($this->uuidParser->parse($command->templateId))
            ?? throw EmergencyTemplateNotFoundException::withId($command->templateId);
        if (!$template->isActive()) {
            throw InactiveEmergencyTemplateException::withId($command->templateId);
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
            AnnouncementType::Emergency,
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
