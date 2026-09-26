<?php

declare(strict_types=1);

namespace Expansa\Scheduler\Traits;

use DateTimeInterface;
use DateTimeImmutable;
use Expansa\Scheduler\Cron\CronExpression;
use Expansa\Scheduler\Exception\SchedulerException;

/**
 * Fluent schedule methods of a job, each one replaces the previous schedule.
 * Hours accept the `H:i` form, e.g. `daily('10:30')`, which overrides the minute argument.
 *
 * @package Expansa\Scheduler\Traits
 */
trait JobIntervals
{
    /**
     * Run the job by a CRON expression or an alias, e.g. `0 9 * * 1-5` or `@daily`.
     *
     * @param string $expression
     * @return static
     * @throws SchedulerException
     */
    public function at(string $expression): static
    {
        $this->executionTime = new CronExpression($expression);
        $this->executionYear = null;

        return $this;
    }

    /**
     * Run the job once, at the minute of a date.
     *
     * @param DateTimeInterface|string $date
     * @return static
     * @throws \DateMalformedStringException
     */
    public function date(DateTimeInterface|string $date): static
    {
        if (! $date instanceof DateTimeInterface) {
            $date = new DateTimeImmutable($date);
        }

        $this->at($date->format('i H d m') . ' *');
        $this->executionYear = $date->format('Y');

        return $this;
    }

    /**
     * Run the job every minute, or every $minute minutes.
     *
     * @param int|string|null $minute From 1 to 59.
     * @return static
     */
    public function everyMinute(int|string|null $minute = null): static
    {
        return $this->at($minute === null ? '* * * * *' : '*/' . $this->cronValue($minute, 1, 59) . ' * * * *');
    }

    /**
     * Run the job every hour.
     *
     * @param int|string $minute
     * @return static
     */
    public function hourly(int|string $minute = 0): static
    {
        return $this->at($this->cronValue($minute, 0, 59) . ' * * * *');
    }

    /**
     * Run the job once a day.
     *
     * @param int|string $hour   Hour or `H:i`.
     * @param int|string $minute
     * @return static
     */
    public function daily(int|string $hour = 0, int|string $minute = 0): static
    {
        [$hour, $minute] = $this->splitTime($hour, $minute);

        return $this->at("{$minute} {$hour} * * *");
    }

    /**
     * Run the job once a week.
     *
     * @param int|string $weekday From 0 (Sunday) to 6.
     * @param int|string $hour    Hour or `H:i`.
     * @param int|string $minute
     * @return static
     */
    public function weekly(int|string $weekday = 0, int|string $hour = 0, int|string $minute = 0): static
    {
        [$hour, $minute] = $this->splitTime($hour, $minute);

        return $this->at("{$minute} {$hour} * * " . $this->cronValue($weekday, 0, 6));
    }

    /**
     * Run the job once a month.
     *
     * @param int|string $month From 1 to 12, `*` for every month.
     * @param int|string $day   From 1 to 31.
     * @param int|string $hour  Hour or `H:i`.
     * @param int|string $minute
     * @return static
     */
    public function monthly(int|string $month = '*', int|string $day = 1, int|string $hour = 0, int|string $minute = 0): static
    {
        [$hour, $minute] = $this->splitTime($hour, $minute);

        return $this->at("{$minute} {$hour} " . $this->cronValue($day, 1, 31) . ' ' . $this->cronValue($month, 1, 12) . ' *');
    }

    public function sunday(int|string $hour = 0, int|string $minute = 0): static
    {
        return $this->weekly(0, $hour, $minute);
    }

    public function monday(int|string $hour = 0, int|string $minute = 0): static
    {
        return $this->weekly(1, $hour, $minute);
    }

    public function tuesday(int|string $hour = 0, int|string $minute = 0): static
    {
        return $this->weekly(2, $hour, $minute);
    }

    public function wednesday(int|string $hour = 0, int|string $minute = 0): static
    {
        return $this->weekly(3, $hour, $minute);
    }

    public function thursday(int|string $hour = 0, int|string $minute = 0): static
    {
        return $this->weekly(4, $hour, $minute);
    }

    public function friday(int|string $hour = 0, int|string $minute = 0): static
    {
        return $this->weekly(5, $hour, $minute);
    }

    public function saturday(int|string $hour = 0, int|string $minute = 0): static
    {
        return $this->weekly(6, $hour, $minute);
    }

    public function january(int|string $day = 1, int|string $hour = 0, int|string $minute = 0): static
    {
        return $this->monthly(1, $day, $hour, $minute);
    }

    public function february(int|string $day = 1, int|string $hour = 0, int|string $minute = 0): static
    {
        return $this->monthly(2, $day, $hour, $minute);
    }

    public function march(int|string $day = 1, int|string $hour = 0, int|string $minute = 0): static
    {
        return $this->monthly(3, $day, $hour, $minute);
    }

    public function april(int|string $day = 1, int|string $hour = 0, int|string $minute = 0): static
    {
        return $this->monthly(4, $day, $hour, $minute);
    }

    public function may(int|string $day = 1, int|string $hour = 0, int|string $minute = 0): static
    {
        return $this->monthly(5, $day, $hour, $minute);
    }

    public function june(int|string $day = 1, int|string $hour = 0, int|string $minute = 0): static
    {
        return $this->monthly(6, $day, $hour, $minute);
    }

    public function july(int|string $day = 1, int|string $hour = 0, int|string $minute = 0): static
    {
        return $this->monthly(7, $day, $hour, $minute);
    }

    public function august(int|string $day = 1, int|string $hour = 0, int|string $minute = 0): static
    {
        return $this->monthly(8, $day, $hour, $minute);
    }

    public function september(int|string $day = 1, int|string $hour = 0, int|string $minute = 0): static
    {
        return $this->monthly(9, $day, $hour, $minute);
    }

    public function october(int|string $day = 1, int|string $hour = 0, int|string $minute = 0): static
    {
        return $this->monthly(10, $day, $hour, $minute);
    }

    public function november(int|string $day = 1, int|string $hour = 0, int|string $minute = 0): static
    {
        return $this->monthly(11, $day, $hour, $minute);
    }

    public function december(int|string $day = 1, int|string $hour = 0, int|string $minute = 0): static
    {
        return $this->monthly(12, $day, $hour, $minute);
    }

    /**
     * Validate an hour and a minute, splitting an `H:i` hour into both.
     *
     * @param int|string $hour
     * @param int|string $minute
     * @return array{string, string}
     */
    private function splitTime(int|string $hour, int|string $minute): array
    {
        if (is_string($hour) && str_contains($hour, ':')) {
            [$hour, $minute] = explode(':', $hour, 2);
        }

        return [$this->cronValue($hour, 0, 23), $this->cronValue($minute, 0, 59)];
    }

    /**
     * Validate a single value of an expression part.
     *
     * @param int|string $value `*` or an integer between $min and $max.
     * @param int        $min
     * @param int        $max
     * @return string
     * @throws SchedulerException
     */
    private function cronValue(int|string $value, int $min, int $max): string
    {
        if ($value === '*') {
            return '*';
        }

        $number = is_int($value) || ctype_digit($value) ? (int) $value : -1;
        if ($number < $min || $number > $max) {
            throw new SchedulerException("Invalid value: it should be '*' or between {$min} and {$max}.");
        }

        return (string) $number;
    }
}
