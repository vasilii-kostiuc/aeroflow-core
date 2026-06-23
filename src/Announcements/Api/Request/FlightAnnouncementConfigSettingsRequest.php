<?php

declare(strict_types=1);

namespace App\Announcements\Api\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class FlightAnnouncementConfigSettingsRequest
{
    public bool $enabled = true;

    #[Assert\Range(min: 1, max: 120)]
    public ?int $repeatEveryMinutes = null;
}
