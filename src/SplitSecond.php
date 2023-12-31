<?php

declare(strict_types=1);

namespace Ordinary\SplitSecond;

use DateTimeImmutable;
use DateTimeInterface;

class SplitSecond
{
    public static function create(SplitSecondUnit $unit, int $splitSeconds): self
    {
        assert(
            $splitSeconds < $unit->perSecond(),
            new UnexpectedValueException("Split seconds ($splitSeconds) is more than {$unit->name} will allow"),
        );

        return new self($unit, $splitSeconds);
    }

    public static function milliseconds(int $milliseconds): self
    {
        return self::create(SplitSecondUnit::Millisecond, $milliseconds);
    }

    public static function microseconds(int $microseconds): self
    {
        return self::create(SplitSecondUnit::Microsecond, $microseconds);
    }

    public static function nanoseconds(int $nanoseconds): self
    {
        return self::create(SplitSecondUnit::Nanosecond, $nanoseconds);
    }

    public static function extractFromDateTime(DateTimeInterface $dateTime): self
    {
        return self::microseconds((int) $dateTime->format('u'));
    }

    private function __construct(public readonly SplitSecondUnit $unit, public readonly int $splitSeconds)
    {
    }

    public function applyToDateTime(DateTimeInterface $dateTime): DateTimeImmutable
    {
        $microseconds = $this->toMicroseconds()->splitSeconds;

        if ($microseconds === (int) $dateTime->format('u')) {
            return $dateTime instanceof DateTimeImmutable
                ? $dateTime
                : DateTimeImmutable::createFromInterface($dateTime);
        }

        $result = DateTimeImmutable::createFromInterface($dateTime);

        return $result->setTime(
            (int) $result->format('H'),
            (int) $result->format('i'),
            (int) $result->format('s'),
            $microseconds,
        );
    }

    public function toMilliseconds(): self
    {
        return match ($this->unit) {
            SplitSecondUnit::Millisecond => $this,
            SplitSecondUnit::Microsecond => self::milliseconds(intdiv($this->splitSeconds, 1_000)),
            SplitSecondUnit::Nanosecond => self::milliseconds(intdiv($this->splitSeconds, 1_000_000)),
        };
    }

    public function toMicroseconds(): self
    {
        return match ($this->unit) {
            SplitSecondUnit::Millisecond => self::microseconds($this->splitSeconds * 1_000),
            SplitSecondUnit::Microsecond => $this,
            SplitSecondUnit::Nanosecond => self::microseconds(intdiv($this->splitSeconds, 1_000)),
        };
    }

    public function toNanoseconds(): self
    {
        return match ($this->unit) {
            SplitSecondUnit::Millisecond => self::nanoseconds($this->splitSeconds * 1_000_000),
            SplitSecondUnit::Microsecond => self::nanoseconds($this->splitSeconds * 1_000),
            SplitSecondUnit::Nanosecond => $this,
        };
    }
}
