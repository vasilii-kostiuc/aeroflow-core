<?php

declare(strict_types=1);

namespace App\AudioCatalog\Domain\Enum;

enum AudioPromptKind: string
{
    case CheckInCounterCode = 'check_in_counter_code';
    case GateCode = 'gate_code';
}
