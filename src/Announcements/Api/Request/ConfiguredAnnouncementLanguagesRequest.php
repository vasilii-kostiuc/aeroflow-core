<?php

declare(strict_types=1);

namespace App\Announcements\Api\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ConfiguredAnnouncementLanguagesRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public string $flightDefinitionId = '',
        #[Assert\NotBlank]
        #[Assert\Choice(choices: ['check_in_opening', 'check_in_continuation', 'check_in_closing', 'boarding_invitation', 'arrival'])]
        public string $announcementType = '',
    ) {
    }
}
