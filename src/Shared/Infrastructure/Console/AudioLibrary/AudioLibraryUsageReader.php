<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Console\AudioLibrary;

use Doctrine\DBAL\Connection;

/**
 * Reads which audio assets the announcement configuration uses. A one-off
 * maintenance read across contexts (Announcements, Flight Operations), kept in
 * console infrastructure next to the seeders, never in application code.
 */
final readonly class AudioLibraryUsageReader
{
    public function __construct(private Connection $connection)
    {
    }

    /**
     * @return list<AssetUsage>
     */
    public function read(): array
    {
        $usages = [];

        foreach (['emergency_announcement_template' => AssetUsage::EMERGENCY, 'supplementary_announcement_template' => AssetUsage::SUPPLEMENTARY] as $table => $kind) {
            $rows = $this->connection->fetchAllAssociative(sprintf(
                "SELECT t.name, v->>'audioAssetId' AS asset_id FROM %s t, json_array_elements(t.variants) v",
                $table,
            ));
            foreach ($rows as $row) {
                if (is_string($row['asset_id']) && '' !== $row['asset_id']) {
                    $usages[] = new AssetUsage($row['asset_id'], $kind, (string) $row['name']);
                }
            }
        }

        $rows = $this->connection->fetchAllAssociative(<<<'SQL'
            SELECT s.audio_asset_id AS asset_id, s.text, fd.flight_number, fd.direction
            FROM announcement_template_segment s
            JOIN announcement_variant v ON v.id = s.variant_id
            JOIN flight_announcement_config c ON c.id = v.config_id
            JOIN flight_definition fd ON fd.id = c.flight_definition_id
            WHERE s.audio_asset_id IS NOT NULL
            SQL);
        foreach ($rows as $row) {
            $usages[] = new AssetUsage(
                (string) $row['asset_id'],
                'arrival' === $row['direction'] ? AssetUsage::ARRIVAL : AssetUsage::DEPARTURE,
                (string) $row['flight_number'],
                is_string($row['text']) && '' !== trim($row['text']) ? $row['text'] : null,
            );
        }

        return $usages;
    }
}
