<?php

declare(strict_types=1);

namespace App\Announcements\Domain\ValueObject;

use App\Announcements\Domain\Exception\InvalidAnnouncementLanguagesException;
use App\Shared\Domain\ValueObject\LanguageCode;

final readonly class AnnouncementLanguages
{
    /**
     * @var list<LanguageCode>
     */
    private array $values;

    /**
     * @param list<LanguageCode> $values
     */
    private function __construct(array $values)
    {
        $this->values = $values;
    }

    public static function fromCodes(LanguageCode ...$codes): self
    {
        if ([] === $codes) {
            throw InvalidAnnouncementLanguagesException::empty();
        }

        $seen = [];

        foreach ($codes as $code) {
            $value = $code->toString();

            if (isset($seen[$value])) {
                throw InvalidAnnouncementLanguagesException::duplicate($value);
            }

            $seen[$value] = true;
        }

        return new self($codes);
    }

    /**
     * @return list<LanguageCode>
     */
    public function values(): array
    {
        return $this->values;
    }

    /**
     * @return list<string>
     */
    public function toStrings(): array
    {
        return array_map(
            static fn (LanguageCode $code): string => $code->toString(),
            $this->values,
        );
    }

    public function equals(self $other): bool
    {
        return $this->toStrings() === $other->toStrings();
    }
}
