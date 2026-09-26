<?php

declare(strict_types=1);

namespace Expansa\Scheduler\Cron;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use OutOfRangeException;
use RuntimeException;

/**
 * Base CRON expression field: validates a part and checks it against a date value.
 * Instances are shared between all expressions, so the caches are keyed by the part itself.
 *
 * @package Expansa\Scheduler\Cron
 */
abstract class AbstractField
{
    /**
     * Literal names and their numeric values, e.g. `JAN` => 1.
     */
    protected const array LITERALS = [];

    /**
     * First allowed value of the field.
     */
    protected int $rangeStart = 0;

    /**
     * Last allowed value of the field.
     */
    protected int $rangeEnd = 0;

    /**
     * Parts with literals replaced by numbers, keyed by the original part.
     */
    private array $converted = [];

    /**
     * Sorted values matching a part, keyed by the part.
     */
    private array $values = [];

    /**
     * Check if a date satisfies a single, not comma separated, part of the expression.
     *
     * @param DateTimeInterface $date
     * @param string            $value
     * @param bool              $invert Whether the run date is searched backwards.
     * @return bool
     */
    abstract public function isSatisfiedBy(DateTimeInterface $date, string $value, bool $invert): bool;

    /**
     * Move the date to the next (or previous) moment that can satisfy the part.
     *
     * @param DateTime|DateTimeImmutable $date
     * @param bool                       $invert
     * @param string|null                $parts  The whole part of the expression, `null` for `*`.
     * @return void
     */
    abstract public function increment(DateTimeInterface &$date, bool $invert = false, ?string $parts = null): void;

    /**
     * Check if a numeric date value satisfies a value, a range or a step of the part.
     *
     * @param int    $dateValue
     * @param string $value
     * @return bool
     */
    public function isSatisfied(int $dateValue, string $value): bool
    {
        if ($value === '*') {
            return true;
        }

        if (str_contains($value, '/')) {
            return $this->isInIncrementsOfRanges($dateValue, $value);
        }

        if (str_contains($value, '-')) {
            [$from, $to] = explode('-', $value, 2);

            return $dateValue >= (int) $this->convertLiterals(trim($from))
                && $dateValue <= (int) $this->convertLiterals(trim($to));
        }

        return $dateValue === (int) $value;
    }

    /**
     * Check if a value is within a stepped range: `*`/`offset[-to]` followed by `/step`.
     *
     * @param int    $dateValue
     * @param string $value
     * @return bool
     * @throws OutOfRangeException
     */
    public function isInIncrementsOfRanges(int $dateValue, string $value): bool
    {
        [$range, $step] = array_map('trim', explode('/', $value, 2)) + [1 => '0'];

        $step = (int) $step;
        if ($step <= 0) {
            return false;
        }

        [$start, $end] = $range === '*' ? [$this->rangeStart, $this->rangeEnd] : explode('-', $range, 2) + [1 => $range];

        $start = (int) $start;
        $end   = (int) $end;

        if ($start < $this->rangeStart || $start > $this->rangeEnd || $start > $end) {
            throw new OutOfRangeException('Invalid range start requested');
        }

        if ($end > $this->rangeEnd) {
            throw new OutOfRangeException('Invalid range end requested');
        }

        // a step larger than the field wraps around once, as the original library does
        if ($step > $this->rangeEnd) {
            return $dateValue === $this->rangeStart + $step % ($this->rangeEnd - $this->rangeStart + 1);
        }

        return $dateValue >= $start && $dateValue <= $end && ($dateValue - $start) % $step === 0;
    }

    /**
     * Check if a part is valid for the field.
     *
     * @param string $value
     * @return bool
     */
    public function validate(string $value): bool
    {
        $value = $this->convertLiterals($value);

        if ($value === '*') {
            return true;
        }

        if (str_contains($value, ',')) {
            foreach (explode(',', $value) as $item) {
                if (! $this->validate($item)) {
                    return false;
                }
            }

            return true;
        }

        if (str_contains($value, '/')) {
            $chunks = explode('/', $value);

            // a numeric offset without a range is not a step
            return count($chunks) === 2
                && ! ctype_digit($chunks[0])
                && $this->validate($chunks[0])
                && filter_var($chunks[1], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false;
        }

        if (str_contains($value, '-')) {
            $chunks = explode('-', $value);

            return count($chunks) === 2
                && $chunks[0] !== '*'
                && $chunks[1] !== '*'
                && $this->validate($chunks[0])
                && $this->validate($chunks[1]);
        }

        return ctype_digit($value) && (int) $value >= $this->rangeStart && (int) $value <= $this->rangeEnd;
    }

    /**
     * Replace literal names in a part with their numbers, e.g. `MON-FRI` becomes `1-5`.
     *
     * @param string $value
     * @return string
     */
    protected function convertLiterals(string $value): string
    {
        if (static::LITERALS === [] || $value === '*' || ctype_digit($value)) {
            return $value;
        }

        return $this->converted[$value] ??= preg_replace_callback(
            '/[a-z]{3}/i',
            fn (array $match): string => (string) (static::LITERALS[strtoupper($match[0])] ?? $match[0]),
            $value
        );
    }

    /**
     * Get the sorted values of the field that satisfy a comma separated part.
     *
     * @param string $parts
     * @return int[]
     * @throws RuntimeException When no value can satisfy the part.
     */
    protected function values(string $parts): array
    {
        if (isset($this->values[$parts])) {
            return $this->values[$parts];
        }

        $items  = array_map('trim', explode(',', $parts));
        $values = [];

        for ($value = $this->rangeStart; $value <= $this->rangeEnd; $value++) {
            foreach ($items as $item) {
                if ($this->isSatisfied($value, $item)) {
                    $values[] = $value;
                    break;
                }
            }
        }

        if ($values === []) {
            throw new RuntimeException('Impossible CRON expression');
        }

        return $this->values[$parts] = $values;
    }

    /**
     * Pick the value the date should move to: the next one after the current, or the previous one when inverted.
     *
     * @param int[] $values Sorted values.
     * @param int   $current
     * @param bool  $invert
     * @return int
     */
    protected function target(array $values, int $current, bool $invert): int
    {
        if ($invert) {
            for ($i = count($values) - 1; $i >= 0; $i--) {
                if ($values[$i] < $current) {
                    return $values[$i];
                }
            }

            return $values[count($values) - 1];
        }

        foreach ($values as $value) {
            if ($value > $current) {
                return $value;
            }
        }

        return $values[0];
    }

    /**
     * Move the date by a number of seconds regardless of DST changes.
     *
     * @param DateTime|DateTimeImmutable $date
     * @param int                        $seconds
     * @return DateTime|DateTimeImmutable
     */
    protected function shift(DateTimeInterface $date, int $seconds): DateTimeInterface
    {
        return $date->setTimestamp($date->getTimestamp() + $seconds);
    }

    /**
     * Move the date to the start of the next day, or to the end of the previous one.
     *
     * @param DateTime|DateTimeImmutable $date
     * @param bool                       $invert
     * @return DateTime|DateTimeImmutable
     */
    protected function shiftDay(DateTimeInterface $date, bool $invert): DateTimeInterface
    {
        return $invert ? $date->modify('-1 day')->setTime(23, 59) : $date->modify('+1 day')->setTime(0, 0);
    }

    /**
     * Round the date to the start (or end) of its hour, compensating an offset change that moved it the wrong way.
     *
     * @param DateTime|DateTimeImmutable $date
     * @param bool                       $invert
     * @param int                        $originalTimestamp
     * @return DateTime|DateTimeImmutable
     */
    protected function setTimeHour(DateTimeInterface $date, bool $invert, int $originalTimestamp): DateTimeInterface
    {
        $date      = $date->setTime((int) $date->format('H'), $invert ? 59 : 0);
        $timestamp = $date->getTimestamp();

        if (! $invert && $timestamp <= $originalTimestamp) {
            return $this->shift($date, 3600);
        }

        if ($invert && $timestamp >= $originalTimestamp) {
            return $this->shift($date, -3600);
        }

        return $date;
    }
}
