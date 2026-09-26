<?php

declare(strict_types=1);

namespace Expansa\Scheduler\Cron;

use DateTimeInterface;

/**
 * Day of month field, allows: * , / - ? L W.
 *
 * `L` is the last day of the month. `15W` is the weekday nearest to the 15th that does not leave the month:
 * a Saturday moves to Friday, a Sunday to Monday, `1W` on a Saturday moves to Monday the 3rd.
 *
 * @package Expansa\Scheduler\Cron
 */
class DayOfMonthField extends AbstractField
{
    protected int $rangeStart = 1;

    protected int $rangeEnd = 31;

    public function isSatisfiedBy(DateTimeInterface $date, string $value, bool $invert): bool
    {
        if ($value === '?') {
            return true;
        }

        if ($value === 'L') {
            return $date->format('j') === $date->format('t');
        }

        if ($position = strpos($value, 'W')) {
            $nearest = self::nearestWeekday(
                (int) $date->format('Y'),
                (int) $date->format('n'),
                (int) substr($value, 0, $position)
            );

            return (int) $date->format('j') === $nearest;
        }

        return $this->isSatisfied((int) $date->format('j'), $value);
    }

    public function increment(DateTimeInterface &$date, bool $invert = false, ?string $parts = null): void
    {
        $date = $this->shiftDay($date, $invert);
    }

    public function validate(string $value): bool
    {
        // W and L are allowed only as a single day, never inside a list
        if (str_contains($value, ',')) {
            return ! str_contains($value, 'W') && ! str_contains($value, 'L') && parent::validate($value);
        }

        if ($value === '?' || $value === 'L') {
            return true;
        }

        if (str_ends_with($value, 'W')) {
            return ctype_digit($day = substr($value, 0, -1)) && parent::validate($day);
        }

        return parent::validate($value);
    }

    /**
     * Get the weekday nearest to a day of the month, `null` if the month has no such day.
     *
     * @param int $year
     * @param int $month
     * @param int $day
     * @return int|null
     */
    private static function nearestWeekday(int $year, int $month, int $day): ?int
    {
        $first = mktime(0, 0, 0, $month, 1, $year);
        $last  = (int) date('t', $first);

        if ($day < 1 || $day > $last) {
            return null;
        }

        // ISO-8601 weekday of the 1st minus one, so a day's weekday is ($offset + $day - 1) % 7 + 1
        $offset = (int) date('N', $first) - 1;

        foreach ([0, -1, 1, -2, 2] as $shift) {
            $candidate = $day + $shift;

            if ($candidate >= 1 && $candidate <= $last && ($offset + $candidate - 1) % 7 < 5) {
                return $candidate;
            }
        }

        return null;
    }
}
