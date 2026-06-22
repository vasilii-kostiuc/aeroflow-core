<?php

declare(strict_types=1);

namespace App\Announcements\Domain\ValueObject;

use App\Announcements\Domain\Exception\InvalidCheckInCounterRangeException;

final readonly class CheckInCounterRange
{
    private function __construct(
        private int $start,
        private int $end,
    ) {
    }

    public static function between(int $start, int $end): self
    {
        if ($start < 1 || $end < $start) {
            throw InvalidCheckInCounterRangeException::forBounds($start, $end);
        }

        return new self($start, $end);
    }

    public static function single(int $counter): self
    {
        return self::between($counter, $counter);
    }

    public function start(): int
    {
        return $this->start;
    }

    public function end(): int
    {
        return $this->end;
    }

    public function toString(): string
    {
        if ($this->start === $this->end) {
            return (string) $this->start;
        }

        return sprintf('%d-%d', $this->start, $this->end);
    }

    public function equals(self $other): bool
    {
        return $this->start === $other->start && $this->end === $other->end;
    }
}
