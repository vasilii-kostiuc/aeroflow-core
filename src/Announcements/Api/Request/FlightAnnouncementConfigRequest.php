<?php

declare(strict_types=1);

namespace App\Announcements\Api\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class FlightAnnouncementConfigRequest
{
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['check_in_opening', 'check_in_continuation', 'check_in_closing', 'boarding_invitation', 'arrival'])]
    public string $announcementType = '';

    public bool $enabled = true;

    #[Assert\Range(min: 1, max: 120)]
    public ?int $repeatEveryMinutes = null;
}
