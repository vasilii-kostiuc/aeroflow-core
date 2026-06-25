<?php

declare(strict_types=1);

namespace App\Localization\Infrastructure\Config;

use App\Localization\Application\LanguageProviderInterface;
use App\Localization\Application\LanguageResult;
use App\Shared\Domain\ValueObject\LanguageCode;
use InvalidArgumentException;

final readonly class ConfiguredLanguageProvider implements LanguageProviderInterface
{
    /**
     * @param list<array{code:string,name:string,nativeName?:string,active?:bool,sortOrder?:int}> $languages
     */
    public function __construct(private array $languages)
    {
    }

    public function all(): array
    {
        $results = [];
        $seen = [];

        foreach ($this->languages as $index => $language) {
            $code = LanguageCode::fromString($language['code'] ?? '')->toString();
            if (isset($seen[$code])) {
                throw new InvalidArgumentException(sprintf('Duplicate configured language "%s".', $code));
            }
            $seen[$code] = true;

            $name = trim($language['name'] ?? '');
            if ($name === '') {
                throw new InvalidArgumentException(sprintf('Configured language "%s" must have a name.', $code));
            }

            $nativeName = trim($language['nativeName'] ?? $name);
            $sortOrder = (int) ($language['sortOrder'] ?? $index + 1);
            if ($sortOrder < 1) {
                throw new InvalidArgumentException(sprintf('Configured language "%s" must have a positive sortOrder.', $code));
            }

            $results[] = new LanguageResult(
                $code,
                $name,
                $nativeName === '' ? $name : $nativeName,
                (bool) ($language['active'] ?? true),
                $sortOrder,
            );
        }

        usort(
            $results,
            static fn (LanguageResult $a, LanguageResult $b): int => [$a->sortOrder, $a->code] <=> [$b->sortOrder, $b->code],
        );

        return array_values($results);
    }
}
