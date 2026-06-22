<?php

declare(strict_types=1);

namespace App\Announcements\Application\ChangeAnnouncementLanguages;

final readonly class ChangeAnnouncementLanguagesCommand
{
    /**
     * @param list<string> $languages
     */
    public function __construct(public string $id, public array $languages)
    {
    }
}
