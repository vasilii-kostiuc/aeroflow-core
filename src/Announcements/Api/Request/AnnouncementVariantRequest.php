<?php

declare(strict_types=1);

namespace App\Announcements\Api\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class AnnouncementVariantRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 16)]
    public string $languageCode = '';

    #[Assert\Positive]
    public int $sortOrder = 1;

    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['audio_asset', 'text'])]
    public string $sourceType = 'text';

    #[Assert\Uuid]
    public ?string $audioAssetId = null;

    public ?string $text = null;

    public bool $enabled = true;
}
