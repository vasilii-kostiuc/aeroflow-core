<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Console\AudioLibrary;

use App\AudioCatalog\Application\AudioAssetResult;
use App\AudioCatalog\Domain\Entity\AudioAsset;

/**
 * Decides where existing audio assets belong, from how they are used (task 032):
 *
 * - emergency preset     → «Экстренные», titled with the preset name;
 * - supplementary preset → «Дополнительно», titled with the preset name;
 * - flight segment       → «Вылеты / <рейс>» or «Прибытие / <рейс>», titled with
 *                          the segment text it was voiced from.
 *
 * Only assets without a folder are moved and only default titles (derived from
 * the file name) are replaced, so whatever an admin already arranged or renamed
 * is kept and the command is safe to re-run. Assets used nowhere, or in places
 * that disagree (two flights, a preset and a flight), are left alone.
 */
final class AudioLibraryPlanner
{
    public const string DEPARTURES = 'Вылеты';
    public const string ARRIVALS = 'Прибытие';
    public const string SUPPLEMENTARY = 'Дополнительно';
    public const string EMERGENCY = 'Экстренные';

    public const string SKIP_UNUSED = 'не используется';
    public const string SKIP_AMBIGUOUS = 'используется в нескольких местах';

    /**
     * @param list<AudioAssetResult> $assets active assets
     * @param list<AssetUsage>       $usages
     *
     * @return array{planned: list<PlannedAsset>, skipped: array<string, list<AudioAssetResult>>}
     */
    public function plan(array $assets, array $usages): array
    {
        $byAsset = [];
        foreach ($usages as $usage) {
            $byAsset[$usage->assetId][] = $usage;
        }

        $planned = [];
        $skipped = [self::SKIP_UNUSED => [], self::SKIP_AMBIGUOUS => []];

        foreach ($assets as $asset) {
            $assetUsages = $byAsset[$asset->id] ?? [];
            if ([] === $assetUsages) {
                $skipped[self::SKIP_UNUSED][] = $asset;
                continue;
            }

            $placement = $this->placement($assetUsages);
            if (null === $placement) {
                $skipped[self::SKIP_AMBIGUOUS][] = $asset;
                continue;
            }
            [$path, $title] = $placement;

            $folderPath = null === $asset->folderId ? $path : null;
            $isDefaultTitle = $asset->title === AudioAsset::titleFromFileName($asset->name);
            $newTitle = $isDefaultTitle && null !== $title && $title !== $asset->title ? $title : null;

            if (null !== $folderPath || null !== $newTitle) {
                $planned[] = new PlannedAsset($asset->id, $asset->title, $asset->folderId, $folderPath, $newTitle);
            }
        }

        return ['planned' => $planned, 'skipped' => $skipped];
    }

    /**
     * @param non-empty-list<AssetUsage> $usages
     *
     * @return array{list<string>, string|null}|null folder path and title, or null when usages disagree
     */
    private function placement(array $usages): ?array
    {
        $places = [];
        foreach ($usages as $usage) {
            $places[$usage->kind.'|'.$usage->label] = $usage;
        }
        if (1 !== count($places)) {
            return null;
        }

        $usage = $usages[0];

        return match ($usage->kind) {
            AssetUsage::EMERGENCY => [[self::EMERGENCY], $usage->label],
            AssetUsage::SUPPLEMENTARY => [[self::SUPPLEMENTARY], $usage->label],
            AssetUsage::ARRIVAL => [[self::ARRIVALS, $usage->label], $this->textTitle($usages)],
            default => [[self::DEPARTURES, $usage->label], $this->textTitle($usages)],
        };
    }

    /**
     * @param list<AssetUsage> $usages
     */
    private function textTitle(array $usages): ?string
    {
        foreach ($usages as $usage) {
            if (null !== $usage->text) {
                return AudioAsset::titleFromText($usage->text);
            }
        }

        return null;
    }
}
