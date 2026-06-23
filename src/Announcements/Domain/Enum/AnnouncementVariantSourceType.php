<?php

declare(strict_types=1);

namespace App\Announcements\Domain\Enum;

enum AnnouncementVariantSourceType: string
{
    case AudioAsset = 'audio_asset';
    case Text = 'text';
}
