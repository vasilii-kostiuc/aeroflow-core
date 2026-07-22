<?php

declare(strict_types=1);

namespace App\Announcements\Application\Service;

use App\Announcements\Application\Port\AudioCatalog\SpeechAssetGeneratorInterface;

/**
 * Turns `text` segments into speech at save time: each non-empty text segment is
 * synthesized (via the Audio Catalog port) and enriched with the resulting
 * `audioAssetId`, so the persisted segment carries both its editable source text
 * and the generated asset it resolves to.
 *
 * Generation is a pre-step of saving a variant (task 022): if the TTS service is
 * unavailable the save fails and no half-voiced segment is stored. The generation
 * cache lives in Audio Catalog (task 021), so re-saving identical text does not
 * synthesize again.
 */
final readonly class TextSegmentSpeechResolver
{
    public function __construct(private SpeechAssetGeneratorInterface $generator)
    {
    }

    /**
     * @param list<array{sortOrder:int,type:string,audioAssetId?:?string,slot?:?string,durationMs?:?int,text?:?string}> $segments
     *
     * @return list<array{sortOrder:int,type:string,audioAssetId?:?string,slot?:?string,durationMs?:?int,text?:?string}>
     */
    public function resolve(array $segments, string $languageCode): array
    {
        foreach ($segments as $index => $segment) {
            if (($segment['type'] ?? null) !== 'text') {
                continue;
            }
            $text = trim((string) ($segment['text'] ?? ''));
            if ('' === $text) {
                // Empty text is rejected by the domain segment factory; leave it
                // untouched so that validation reports it rather than the TTS call.
                continue;
            }
            $segments[$index]['audioAssetId'] = $this->generator->generate($text, $languageCode);
        }

        return $segments;
    }
}
