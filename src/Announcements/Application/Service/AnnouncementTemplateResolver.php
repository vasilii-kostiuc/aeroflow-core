<?php

declare(strict_types=1);

namespace App\Announcements\Application\Service;

use App\Announcements\Application\Port\AudioCatalog\AudioPromptLookupInterface;
use App\Announcements\Application\Port\FlightOperations\OperationalResourceSnapshot;
use App\Announcements\Domain\Entity\FlightAnnouncementConfig;
use App\Announcements\Domain\Enum\AnnouncementTemplateSegmentType;
use App\Announcements\Domain\Enum\DynamicSlotType;
use App\Announcements\Domain\Exception\AnnouncementConfigurationNotReadyException;
use App\Announcements\Domain\Exception\AudioAssetUnavailableException;
use App\Announcements\Domain\Exception\MissingAudioPromptsException;

final readonly class AnnouncementTemplateResolver
{
    public function __construct(private AudioPromptLookupInterface $audioCatalog)
    {
    }

    /**
     * @param list<string>                      $languages
     * @param list<OperationalResourceSnapshot> $counters
     *
     * @return list<array{languageCode:string,sortOrder:int,items:list<array{type:string,audioAssetId?:string,durationMs?:int}>}>
     */
    public function resolve(
        FlightAnnouncementConfig $config,
        array $languages,
        array $counters,
        ?OperationalResourceSnapshot $gate,
    ): array {
        $errors = $config->validationErrors();
        if ($errors !== []) {
            throw AnnouncementConfigurationNotReadyException::withErrors($errors);
        }
        $variants = [];
        foreach ($config->getVariants() as $variant) {
            if ($variant->isEnabled()) {
                $variants[$variant->getLanguageCode()] = $variant;
            }
        }
        $missingLanguages = array_values(array_diff($languages, array_keys($variants)));
        if ($missingLanguages !== []) {
            throw AnnouncementConfigurationNotReadyException::withErrors(array_map(static fn (string $language): string => 'missing_variant_'.$language, $missingLanguages));
        }

        $missingPrompts = [];
        $sequence = [];
        foreach ($languages as $language) {
            $variant = $variants[$language];
            $items = [];
            foreach ($variant->getSegments() as $segment) {
                if ($segment->getType() === AnnouncementTemplateSegmentType::AudioAsset) {
                    $id = (string) $segment->getAudioAssetId()?->toRfc4122();
                    if (!$this->audioCatalog->isActiveAsset($id)) {
                        throw AudioAssetUnavailableException::withId($id);
                    }
                    $items[] = ['type' => 'audio_asset', 'audioAssetId' => $id];
                    continue;
                }
                if ($segment->getType() === AnnouncementTemplateSegmentType::Pause) {
                    $items[] = ['type' => 'pause', 'durationMs' => (int) $segment->getDurationMs()];
                    continue;
                }
                if ($segment->getType() === AnnouncementTemplateSegmentType::Text) {
                    $id = (string) $segment->getAudioAssetId()?->toRfc4122();

                    if ($id === '') {
                        throw AnnouncementConfigurationNotReadyException::withErrors(['text_segment_requires_tts']);
                    }
                    if (!$this->audioCatalog->isActiveAsset($id)) {
                        throw AudioAssetUnavailableException::withId($id);
                    }
                    $items[] = ['type' => 'audio_asset', 'audioAssetId' => $id];
                    continue;
                }

                $resources = $segment->getSlot() === DynamicSlotType::CheckInCounters
                    ? $counters
                    : ($gate === null ? [] : [$gate]);
                $kind = $segment->getSlot() === DynamicSlotType::CheckInCounters
                    ? 'check_in_counter_code'
                    : 'gate_code';
                foreach ($resources as $resource) {
                    $assetId = $this->audioCatalog->activeAssetId($kind, $resource->code, $language);
                    if ($assetId === null) {
                        $missingPrompts[] = sprintf('%s/%s/%s', $kind, $resource->code, $language);
                        continue;
                    }
                    $items[] = ['type' => 'audio_asset', 'audioAssetId' => $assetId];
                }
            }
            $sequence[] = ['languageCode' => $language, 'sortOrder' => $variant->getSortOrder(), 'items' => $items];
        }
        if ($missingPrompts !== []) {
            throw MissingAudioPromptsException::forKeys(array_values(array_unique($missingPrompts)));
        }

        return $sequence;
    }
}
