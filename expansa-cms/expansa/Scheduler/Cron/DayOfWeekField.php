<?php

declare(strict_types=1);

namespace Expansa\Scheduler\Cron;

use DateTimeInterface;
use Expansa\Scheduler\Exception\SchedulerException;

/**
 * Day of week field, allows: * , / - ? L # and the literals MON-SUN, both 0 and 7 are Sunday.
 *
 * `5L` is the last Friday of the month, `5#2` is the second Friday of the month (from 1 to 5).
 *
 * @package Expansa\Scheduler\Cron
 */
class DayOfWeekField extends AbstractField
{
    protected const array LITERALS = ['MON' => 1, 'TUE' => 2, 'WED' => 3, 'THU' => 4, 'FRI' => 5, 'SAT' => 6, 'SUN' => 7];

    protected int $rangeStart = 0;

    protected int $rangeEnd = 7;

    public function isSatisfiedBy(DateTimeInterface $date, string $value, bool $invert): bool
    {
        if ($value === '?') {
            return true;
        }

        $value = $this->convertLiterals($value);

        if ($position = strpos($value, 'L')) {
            return (int) substr($value, 0, $position) % 7 === (int) $date->format('w')
                && (int) $date->format('t') - (int) $date->format('j') < 7;
        }

        if (strpos($value, '#')) {
            [$weekday, $nth] = explode('#', $value, 2);

            if (! ctype_digit($nth)) {
                throw new SchedulerException("Hashed weekdays must be numeric, {$nth} given");
            }

            $weekday = (int) $weekday ?: 7;
            if ($weekday > 7) {
                throw new SchedulerException("Weekday must be a value between 0 and 7. {$weekday} given");
            }

            $nth = (int) $nth;
            if ($nth < 1 || $nth > 5) {
                throw new SchedulerException("A month has from 1 to 5 of a given weekday, {$nth} given");
            }

            return (int) $date->format('N') === $weekday && intdiv((int) $date->format('j') - 1, 7) + 1 === $nth;
        }

        // Sunday is 0 at the start of a range and 7 at its end
        if (str_contains($value, '-')) {
            $parts = explode('-', $value);
            if ($parts[0] === '7') {
                $parts[0] = '0';
            } elseif ($parts[1] === '0') {
                $parts[1] = '7';
            }
            $value = implode('-', $parts);
        }

        return $this->isSatisfied((int) $date->format(str_contains($value, '7') ? 'N' : 'w'), $value);
    }

    public function increment(DateTimeInterface &$date, bool $invert = false, ?string $parts = null): void
    {
        $date = $this->shiftDay($date, $invert);
    }

    public function validate(string $value): bool
    {
        if (parent::validate($value) || $value === '?') {
            return true;
        }

        $value = $this->convertLiterals($value);

        if (str_contains($value, '#')) {
            [$weekday, $nth] = explode('#', $value, 2);

            return parent::validate($weekday) && ctype_digit($nth) && $nth >= 1 && $nth <= 5;
        }

        return str_ends_with($value, 'L') && $this->validate(substr($value, 0, -1));
    }
}
