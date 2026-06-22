<?php

declare(strict_types=1);

namespace App\Announcements\Api\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateAnnouncementRequest
{
    /**
     * @param list<string> $languages
     */
    public function __construct(
        #[Assert\Choice(choices: ['check_in_opening', 'check_in_closing', 'boarding_invitation', 'arrival'])]
        public string $type,
        #[Assert\Uuid]
        public string $flightDefinitionId,
        #[Assert\Count(min: 1)]
        #[Assert\All([new Assert\Type('string')])]
        public array $languages,
        public ?int $checkInCounterStart = null,
        public ?int $checkInCounterEnd = null,
        public ?string $gateCode = null,
    ) {
    }
}
