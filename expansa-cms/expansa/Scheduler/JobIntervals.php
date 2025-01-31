<?php

declare(strict_types=1);

namespace Expansa\Scheduler;

use DateTime;
use Cron\CronExpression;
use InvalidArgumentException;

trait JobIntervals
{
    /**
     * Set the Job execution time.
     *
     * @param string $expression
     * @return self
     */
    public function at(string $expression): static
    {
        $this->executionTime = new CronExpression($expression);

        return $this;
    }

    /**
     * Run the Job at a specific date.
     *
     * @param DateTime|string $date
     * @return self
     * @throws \DateMalformedStringException
     */
    public function date(DateTime|string $date): static
    {
        if (! $date instanceof DateTime) {
            $date = new DateTime($date);
        }

        $this->executionYear = $date->format('Y');

        return $this->at("{$date->format('i')} {$date->format('H')} {$date->format('d')} {$date->format('m')} *");
    }

    /**
     * Set the execution time to every minute.
     *
     * @param null|int|string $minute When set, specifies that the job will be run every $minute minutes
     * @return self
     */
    public function everyMinute(int|string $minute = null): static
    {
        $minuteExpression = '*';
        if ($minute !== null) {
            $c = $this->validateCronSequence($minute);
            $minuteExpression = '*/' . $c['minute'];
        }

        return $this->at($minuteExpression . ' * * * *');
    }

    /**
     * Set the execution time to every hour.
     *
     * @param int|string $minute
     * @return self
     */
    public function hourly(int|string $minute = 0): static
    {
        $c = $this->validateCronSequence($minute);

        return $this->at("{$c['minute']} * * * *");
    }

    /**
     * Set the execution time to once a day.
     *
     * @param int|string $hour
     * @param int|string $minute
     * @return self
     */
    public function daily(int|string $hour = 0, int|string $minute = 0): static
    {
        if (is_string($hour)) {
            $parts  = explode(':', $hour);
            $hour   = $parts[0];
            $minute = $parts[1] ?? '0';
        }

        $c = $this->validateCronSequence($minute, $hour);

        return $this->at("{$c['minute']} {$c['hour']} * * *");
    }

    /**
     * Set the execution time to once a week.
     *
     * @param int|string $weekday
     * @param int|string $hour
     * @param int|string $minute
     * @return self
     */
    public function weekly(int|string $weekday = 0, int|string $hour = 0, int|string $minute = 0)
    {
        if (is_string($hour)) {
            $parts   = explode(':', $hour);
            $hour   = $parts[0];
            $minute = $parts[1] ?? '0';
        }

        $c = $this->validateCronSequence($minute, $hour, null, null, $weekday);

        return $this->at("{$c['minute']} {$c['hour']} * * {$c['weekday']}");
    }

    /**
     * Set the execution time to once a month.
     *
     * @param int|string $month
     * @param int|string $day
     * @param int|string $hour
     * @param int|string $minute
     * @return self
     */
    public function monthly(int|string $month = '*', int|string $day = 1, int|string $hour = 0, int|string $minute = 0): static
    {
        if (is_string($hour)) {
            $parts  = explode(':', $hour);
            $hour   = $parts[0];
            $minute = $parts[1] ?? '0';
        }

        $c = $this->validateCronSequence($minute, $hour, $day, $month);

        return $this->at("{$c['minute']} {$c['hour']} {$c['day']} {$c['month']} *");
    }

    /**
     * Set the execution time to every Sunday.
     *
     * @param int|string $hour
     * @param int|string $minute
     * @return self
     */
    public function sunday(int|string $hour = 0, int|string $minute = 0): static
    {
        return $this->weekly(0, $hour, $minute);
    }

    /**
     * Set the execution time to every Monday.
     *
     * @param int|string $hour
     * @param int|string $minute
     * @return self
     */
    public function monday(int|string $hour = 0, int|string $minute = 0): static
    {
        return $this->weekly(1, $hour, $minute);
    }

    /**
     * Set the execution time to every Tuesday.
     *
     * @param int|string $hour
     * @param int|string $minute
     * @return self
     */
    public function tuesday(int|string $hour = 0, int|string $minute = 0): static
    {
        return $this->weekly(2, $hour, $minute);
    }

    /**
     * Set the execution time to every Wednesday.
     *
     * @param int|string $hour
     * @param int|string $minute
     * @return self
     */
    public function wednesday(int|string $hour = 0, int|string $minute = 0): static
    {
        return $this->weekly(3, $hour, $minute);
    }

    /**
     * Set the execution time to every Thursday.
     *
     * @param int|string $hour
     * @param int|string $minute
     * @return self
     */
    public function thursday(int|string $hour = 0, int|string $minute = 0): static
    {
        return $this->weekly(4, $hour, $minute);
    }

    /**
     * Set the execution time to every Friday.
     *
     * @param int|string $hour
     * @param int|string $minute
     * @return self
     */
    public function friday(int|string $hour = 0, int|string $minute = 0): static
    {
        return $this->weekly(5, $hour, $minute);
    }

    /**
     * Set the execution time to every Saturday.
     *
     * @param int|string $hour
     * @param int|string $minute
     * @return self
     */
    public function saturday(int|string $hour = 0, int|string $minute = 0): static
    {
        return $this->weekly(6, $hour, $minute);
    }

    /**
     * Set the execution time to every January.
     *
     * @param int|string $day
     * @param int|string $hour
     * @param int|string $minute
     * @return self
     */
    public function january(int|string $day = 1, int|string $hour = 0, int|string $minute = 0): static
    {
        return $this->monthly(1, $day, $hour, $minute);
    }

    /**
     * Set the execution time to every February.
     *
     * @param int|string $day
     * @param int|string $hour
     * @param int|string $minute
     * @return self
     */
    public function february(int|string $day = 1, int|string $hour = 0, int|string $minute = 0): static
    {
        return $this->monthly(2, $day, $hour, $minute);
    }

    /**
     * Set the execution time to every March.
     *
     * @param int|string $day
     * @param int|string $hour
     * @param int|string $minute
     * @return self
     */
    public function march(int|string $day = 1, int|string $hour = 0, int|string $minute = 0): static
    {
        return $this->monthly(3, $day, $hour, $minute);
    }

    /**
     * Set the execution time to every April.
     *
     * @param int|string $day
     * @param int|string $hour
     * @param int|string $minute
     * @return self
     */
    public function april(int|string $day = 1, int|string $hour = 0, int|string $minute = 0): static
    {
        return $this->monthly(4, $day, $hour, $minute);
    }

    /**
     * Set the execution time to every May.
     *
     * @param int|string $day
     * @param int|string $hour
     * @param int|string $minute
     * @return self
     */
    public function may(int|string $day = 1, int|string $hour = 0, int|string $minute = 0): static
    {
        return $this->monthly(5, $day, $hour, $minute);
    }

    /**
     * Set the execution time to every June.
     *
     * @param int|string $day
     * @param int|string $hour
     * @param int|string $minute
     * @return self
     */
    public function june(int|string $day = 1, int|string $hour = 0, int|string $minute = 0): static
    {
        return $this->monthly(6, $day, $hour, $minute);
    }

    /**
     * Set the execution time to every July.
     *
     * @param int|string $day
     * @param int|string $hour
     * @param int|string $minute
     * @return self
     */
    public function july(int|string $day = 1, int|string $hour = 0, int|string $minute = 0): static
    {
        return $this->monthly(7, $day, $hour, $minute);
    }

    /**
     * Set the execution time to every August.
     *
     * @param int|string $day
     * @param int|string $hour
     * @param int|string $minute
     * @return self
     */
    public function august(int|string $day = 1, int|string $hour = 0, int|string $minute = 0): static
    {
        return $this->monthly(8, $day, $hour, $minute);
    }

    /**
     * Set the execution time to every September.
     *
     * @param int|string $day
     * @param int|string $hour
     * @param int|string $minute
     * @return self
     */
    public function september(int|string $day = 1, int|string $hour = 0, int|string $minute = 0): static
    {
        return $this->monthly(9, $day, $hour, $minute);
    }

    /**
     * Set the execution time to every October.
     *
     * @param int|string $day
     * @param int|string $hour
     * @param int|string $minute
     * @return self
     */
    public function october(int|string $day = 1, int|string $hour = 0, int|string $minute = 0): static
    {
        return $this->monthly(10, $day, $hour, $minute);
    }

    /**
     * Set the execution time to every November.
     *
     * @param int|string $day
     * @param int|string $hour
     * @param int|string $minute
     * @return self
     */
    public function november(int|string $day = 1, int|string $hour = 0, int|string $minute = 0): static
    {
        return $this->monthly(11, $day, $hour, $minute);
    }

    /**
     * Set the execution time to every December.
     *
     * @param int|string $day
     * @param int|string $hour
     * @param int|string $minute
     * @return self
     */
    public function december(int|string $day = 1, int|string $hour = 0, int|string $minute = 0): static
    {
        return $this->monthly(12, $day, $hour, $minute);
    }

    /**
     * Validate sequence of cron expression.
     *
     * @param null|int|string $minute
     * @param null|int|string $hour
     * @param null|int|string $day
     * @param null|int|string $month
     * @param null|int|string $weekday
     * @return array
     */
    private function validateCronSequence(
        int|string $minute = null,
        int|string $hour = null,
        int|string $day = null,
        int|string $month = null,
        int|string $weekday = null
    ): array
    {
        return [
            'minute'  => $this->validateCronRange($minute, 0, 59),
            'hour'    => $this->validateCronRange($hour, 0, 23),
            'day'     => $this->validateCronRange($day, 1, 31),
            'month'   => $this->validateCronRange($month, 1, 12),
            'weekday' => $this->validateCronRange($weekday, 0, 6),
        ];
    }

    /**
     * Validate sequence of cron expression.
     *
     * @param int|string $value
     * @param int        $min
     * @param int        $max
     * @return string|int
     */
    private function validateCronRange(int|string $value, int $min, int $max): string|int
    {
        if ($value === '*') {
            return '*';
        }

        if (! is_numeric($value) || ! ($value >= $min && $value <= $max)) {
            throw new InvalidArgumentException("Invalid value: it should be '*' or between {$min} and {$max}.");
        }

        return (int) $value;
    }
}
