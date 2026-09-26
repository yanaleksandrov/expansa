<?php

declare(strict_types=1);

namespace Expansa\Scheduler\Cron;

use DateTimeInterface;

/**
 * Month field, allows: * , / - and the literals JAN-DEC.
 *
 * @package Expansa\Scheduler\Cron
 */
class MonthField extends AbstractField
{
    protected const array LITERALS = [
        'JAN' => 1,
        'FEB' => 2,
        'MAR' => 3,
        'APR' => 4,
        'MAY' => 5,
        'JUN' => 6,
        'JUL' => 7,
        'AUG' => 8,
        'SEP' => 9,
        'OCT' => 10,
        'NOV' => 11,
        'DEC' => 12,
    ];

    protected int $rangeStart = 1;

    protected int $rangeEnd = 12;

    public function isSatisfiedBy(DateTimeInterface $date, string $value, bool $invert): bool
    {
        return $value === '?' || $this->isSatisfied((int) $date->format('n'), $this->convertLiterals($value));
    }

    public function increment(DateTimeInterface &$date, bool $invert = false, ?string $parts = null): void
    {
        $date = $invert
            ? $date->modify('last day of previous month')->setTime(23, 59)
            : $date->modify('first day of next month')->setTime(0, 0);
    }
}
