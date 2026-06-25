<?php

declare(strict_types=1);

namespace App\Localization\Application\ListLanguages;

use App\Localization\Application\LanguageProviderInterface;
use App\Localization\Application\LanguageResult;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class ListLanguagesHandler
{
    public function __construct(private LanguageProviderInterface $languages)
    {
    }

    /**
     * @return list<LanguageResult>
     */
    public function __invoke(ListLanguagesQuery $query): array
    {
        return $this->languages->all();
    }
}
