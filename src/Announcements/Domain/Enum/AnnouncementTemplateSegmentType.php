<?php

declare(strict_types=1);

namespace App\Announcements\Domain\Enum;

enum AnnouncementTemplateSegmentType: string
{
    case AudioAsset = 'audio_asset';
    case DynamicSlot = 'dynamic_slot';
    case Pause = 'pause';
    case Text = 'text';
}
