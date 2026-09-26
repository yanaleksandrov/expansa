<?php

declare(strict_types=1);

namespace Expansa\Scheduler\Cron;

use DateTimeInterface;

/**
 * Minutes field, allows: * , / -.
 *
 * @package Expansa\Scheduler\Cron
 */
class MinutesField extends AbstractField
{
    protected int $rangeStart = 0;

    protected int $rangeEnd = 59;

    public function isSatisfiedBy(DateTimeInterface $date, string $value, bool $invert): bool
    {
        return $value === '?' || $this->isSatisfied((int) $date->format('i'), $value);
    }

    public function increment(DateTimeInterface &$date, bool $invert = false, ?string $parts = null): void
    {
        if ($parts === null) {
            $date = $this->shift($date, $invert ? -60 : 60);

            return;
        }

        $minute = (int) $date->format('i');
        $target = $this->target($this->values($parts), $minute, $invert);

        if (! $invert) {
            if ($minute >= $target) {
                $date   = $this->shift($date, (60 - $minute) * 60);
                $minute = (int) $date->format('i');
            }

            $date = $this->shift($date, ($target - $minute) * 60);

            return;
        }

        if ($minute <= $target) {
            $date   = $this->shift($date, -($minute + 1) * 60);
            $minute = (int) $date->format('i');
        }

        $date = $this->shift($date, -($minute - $target) * 60);
    }
}
