<?php

declare(strict_types=1);

namespace App\Announcements\Api\Request;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'LaunchSupplementaryAnnouncementRequest',
    required: ['templateId'],
)]
final class LaunchSupplementaryAnnouncementRequest
{
    #[OA\Property(format: 'uuid')]
    #[Assert\NotBlank]
    public string $templateId = '';

    /**
     * Subset of the preset's languages; empty means all configured languages.
     *
     * @var list<string>
     */
    #[OA\Property(type: 'array', items: new OA\Items(type: 'string'), example: ['ro-MD', 'ru'])]
    #[Assert\All([new Assert\NotBlank(), new Assert\Length(max: 16)])]
    public array $languages = [];
}
