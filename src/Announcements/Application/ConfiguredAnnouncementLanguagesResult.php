<?php

declare(strict_types=1);

namespace App\Announcements\Application;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ConfiguredAnnouncementLanguagesResult',
    required: ['languages'],
)]
final readonly class ConfiguredAnnouncementLanguagesResult
{
    /**
     * @param list<string> $languages ordered enabled variant languages, empty when
     *                                the flight has no enabled config for the type
     */
    public function __construct(
        #[OA\Property(type: 'array', items: new OA\Items(type: 'string', example: 'ro-MD'))]
        public array $languages,
    ) {
    }
}
