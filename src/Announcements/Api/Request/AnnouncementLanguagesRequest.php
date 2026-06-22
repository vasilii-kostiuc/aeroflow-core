<?php

declare(strict_types=1);

namespace App\Announcements\Api\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class AnnouncementLanguagesRequest
{
    /**
     * @param list<string> $languages
     */
    public function __construct(
        #[Assert\Count(min: 1)]
        #[Assert\All([new Assert\Type('string')])]
        public array $languages,
    ) {
    }
}
