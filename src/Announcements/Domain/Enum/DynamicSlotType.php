<?php

declare(strict_types=1);

namespace App\Announcements\Domain\Enum;

enum DynamicSlotType: string
{
    case CheckInCounters = 'check_in_counters';
    case GateCode = 'gate_code';
}
