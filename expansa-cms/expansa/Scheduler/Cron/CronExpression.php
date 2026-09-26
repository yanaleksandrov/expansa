<?php

declare(strict_types=1);

namespace Expansa\Scheduler\Cron;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use LogicException;
use RuntimeException;
use Expansa\Scheduler\Exceptions\SchedulerException;

/**
 * CRON expression: checks whether it is due and finds its next and previous run dates.
 * Seconds are dropped from every comparison, so the result is exact when checked once a minute.
 * Fork of https://github.com/dragonmantank/cron-expression.
 *
 * @package Expansa\Scheduler\Cron
 */
class CronExpression
{
    public const int MINUTE  = 0;
    public const int HOUR    = 1;
    public const int DAY     = 2;
    public const int MONTH   = 3;
    public const int WEEKDAY = 4;

    /**
     * Built-in aliases, they can not be unregistered.
     */
    public const array MAPPINGS = [
        '@yearly'   => '0 0 1 1 *',
        '@annually' => '0 0 1 1 *',
        '@monthly'  => '0 0 1 * *',
        '@weekly'   => '0 0 * * 0',
        '@daily'    => '0 0 * * *',
        '@midnight' => '0 0 * * *',
        '@hourly'   => '0 * * * *',
    ];

    /**
     * Order in which the parts are satisfied when a run date is searched: the largest unit first.
     */
    private const array ORDER = [self::MONTH, self::DAY, self::WEEKDAY, self::HOUR, self::MINUTE];

    /**
     * Built-in and registered aliases.
     */
    private static array $aliases = self::MAPPINGS;

    /**
     * Field instances shared by all expressions.
     *
     * @var AbstractField[]
     */
    private static array $fields = [];

    /**
     * The five parts of the expression, from minute to weekday.
     *
     * @var string[]
     */
    private array $parts = [];

    /**
     * Parts other than `*` in ORDER, each split into its comma separated items.
     *
     * @var array<int, string[]>|null
     */
    private ?array $lists = null;

    /**
     * Both the day of month and the day of week are set, so either of them satisfies the day.
     */
    private bool $eitherDay = false;

    /**
     * Copies without the day of week and without the day of month, used when $eitherDay is set.
     */
    private ?self $monthDays = null;

    private ?self $weekDays = null;

    /**
     * Max iterations when searching for a run date.
     */
    private int $maxIterationCount = 1000;

    /**
     * Parse a CRON expression or an alias.
     *
     * @throws SchedulerException
     */
    public function __construct(

        /**
         * E.g. `8 * * * *` or `@daily`.
         */
        string $expression,
    )
    {
        $this->setExpression(self::$aliases[strtolower($expression)] ?? $expression);
    }

    /**
     * Register a user defined alias, e.g. `@workdays` for `0 9 * * 1-5`.
     *
     * @param string $alias
     * @param string $expression
     * @return void
     * @throws LogicException If the expression or the alias are invalid, or the alias is already registered.
     */
    public static function registerAlias(string $alias, string $expression): void
    {
        if (! self::isValidExpression($expression)) {
            throw new LogicException("The expression `$expression` is invalid");
        }

        $shortcut = strtolower($alias);
        if (preg_match('/^@\w+$/', $shortcut) !== 1) {
            throw new LogicException("The alias `$alias` is invalid: it must be `@` followed by letters, numbers or `_`.");
        }

        if (isset(self::$aliases[$shortcut])) {
            throw new LogicException("The alias `$alias` is already registered.");
        }

        self::$aliases[$shortcut] = $expression;
    }

    /**
     * Unregister a user defined alias.
     *
     * @param string $alias
     * @return bool False if the alias is not registered.
     * @throws LogicException For a built-in alias.
     */
    public static function unregisterAlias(string $alias): bool
    {
        $shortcut = strtolower($alias);
        if (isset(self::MAPPINGS[$shortcut])) {
            throw new LogicException("The alias `$alias` is a built-in alias; it can not be unregistered.");
        }

        if (! isset(self::$aliases[$shortcut])) {
            return false;
        }

        unset(self::$aliases[$shortcut]);

        return true;
    }

    /**
     * Check if an alias is registered.
     *
     * @param string $alias
     * @return bool
     */
    public static function supportsAlias(string $alias): bool
    {
        return isset(self::$aliases[strtolower($alias)]);
    }

    /**
     * Get all aliases with their expressions.
     *
     * @return array<string, string>
     */
    public static function getAliases(): array
    {
        return self::$aliases;
    }

    /**
     * Check if an expression is valid.
     *
     * @param string $expression
     * @return bool
     */
    public static function isValidExpression(string $expression): bool
    {
        try {
            new self($expression);
        } catch (SchedulerException) {
            return false;
        }

        return true;
    }

    /**
     * Get the shared field of a position.
     *
     * @param int $position One of the MINUTE ... WEEKDAY constants.
     * @return AbstractField
     * @throws SchedulerException
     */
    public static function field(int $position): AbstractField
    {
        return self::$fields[$position] ??= match ($position) {
            self::MINUTE  => new MinutesField(),
            self::HOUR    => new HoursField(),
            self::DAY     => new DayOfMonthField(),
            self::MONTH   => new MonthField(),
            self::WEEKDAY => new DayOfWeekField(),
            default       => throw new SchedulerException(($position + 1) . ' is not a valid position'),
        };
    }

    /**
     * Set or change the whole expression.
     *
     * @param string $value E.g. `8 * * * *`, aliases are not resolved here.
     * @return static
     * @throws SchedulerException
     */
    public function setExpression(string $value): static
    {
        $parts = preg_split('/\s/', $value, -1, PREG_SPLIT_NO_EMPTY);

        // `?` is allowed only in one of the day fields
        if (
            ! is_array($parts)
            || count($parts) !== 5
            || $parts[self::MINUTE] === '?'
            || $parts[self::HOUR] === '?'
            || $parts[self::MONTH] === '?'
            || ($parts[self::DAY] === '?' && $parts[self::WEEKDAY] === '?')
        ) {
            throw new SchedulerException($value . ' is not a valid CRON expression');
        }

        foreach ($parts as $position => $part) {
            $this->setPart($position, $part);
        }

        return $this;
    }

    /**
     * Set one part of the expression.
     *
     * @param int    $position One of the MINUTE ... WEEKDAY constants.
     * @param string $value
     * @return static
     * @throws SchedulerException
     */
    public function setPart(int $position, string $value): static
    {
        if (! self::field($position)->validate($value)) {
            throw new SchedulerException('Invalid CRON field value ' . $value . ' at position ' . $position);
        }

        $this->parts[$position] = $value;
        $this->lists            = null;
        $this->monthDays        = null;
        $this->weekDays         = null;

        return $this;
    }

    /**
     * Set max iterations when searching for a run date.
     *
     * @param int $maxIterationCount
     * @return static
     */
    public function setMaxIterationCount(int $maxIterationCount): static
    {
        $this->maxIterationCount = $maxIterationCount;

        return $this;
    }

    /**
     * Get the whole expression or one of its parts.
     *
     * @param int|null $part One of the MINUTE ... WEEKDAY constants, `null` for the whole expression.
     * @return string|null `null` for an unknown part.
     */
    public function getExpression(?int $part = null): ?string
    {
        return $part === null ? implode(' ', $this->parts) : $this->parts[$part] ?? null;
    }

    /**
     * Get the parts of the expression, from minute to weekday.
     *
     * @return string[]
     */
    public function getParts(): array
    {
        return $this->parts;
    }

    public function __toString(): string
    {
        return $this->getExpression();
    }

    /**
     * Check if the expression is due at a date, seconds are ignored.
     *
     * @param DateTimeInterface|string $currentTime
     * @param string|null              $timeZone    Defaults to the date timezone, or to the default one for a string.
     * @return bool
     */
    public function isDue(DateTimeInterface|string $currentTime = 'now', ?string $timeZone = null): bool
    {
        try {
            if ($currentTime instanceof DateTimeInterface) {
                if ($timeZone !== null && $timeZone !== $currentTime->getTimezone()->getName()) {
                    $currentTime = DateTimeImmutable::createFromInterface($currentTime)->setTimezone(new DateTimeZone($timeZone));
                }
            } else {
                $currentTime = new DateTime($currentTime);
                $currentTime->setTimezone(new DateTimeZone($timeZone ?? date_default_timezone_get()));
            }

            return $this->matches($currentTime);
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Get the next run date after a date.
     *
     * @param DateTimeInterface|string $currentTime
     * @param int                      $nth              Number of matches to skip.
     * @param bool                     $allowCurrentDate Whether the current date itself can be returned.
     * @param string|null              $timeZone
     * @return DateTime
     * @throws RuntimeException When no run date is found within the max iterations.
     */
    public function getNextRunDate(
        DateTimeInterface|string $currentTime = 'now',
        int $nth = 0,
        bool $allowCurrentDate = false,
        ?string $timeZone = null
    ): DateTime
    {
        return $this->getRunDate($currentTime, $nth, false, $allowCurrentDate, $timeZone);
    }

    /**
     * Get the previous run date before a date.
     *
     * @param DateTimeInterface|string $currentTime
     * @param int                      $nth              Number of matches to skip.
     * @param bool                     $allowCurrentDate Whether the current date itself can be returned.
     * @param string|null              $timeZone
     * @return DateTime
     * @throws RuntimeException When no run date is found within the max iterations.
     */
    public function getPreviousRunDate(
        DateTimeInterface|string $currentTime = 'now',
        int $nth = 0,
        bool $allowCurrentDate = false,
        ?string $timeZone = null
    ): DateTime
    {
        return $this->getRunDate($currentTime, $nth, true, $allowCurrentDate, $timeZone);
    }

    /**
     * Get several consecutive run dates, fewer if the search runs out of iterations.
     *
     * @param int                      $total
     * @param DateTimeInterface|string $currentTime
     * @param bool                     $invert           Whether previous dates are returned.
     * @param bool                     $allowCurrentDate Whether the current date itself can be returned.
     * @param string|null              $timeZone
     * @return DateTime[]
     */
    public function getMultipleRunDates(
        int $total,
        DateTimeInterface|string $currentTime = 'now',
        bool $invert = false,
        bool $allowCurrentDate = false,
        ?string $timeZone = null
    ): array
    {
        $timeZone    = $this->determineTimeZone($currentTime, $timeZone);
        $currentTime = $this->toDateTime($currentTime, $timeZone);
        $matches     = [];

        for ($i = 0; $i < $total; ++$i) {
            try {
                $result = $this->getRunDate($currentTime, 0, $invert, $allowCurrentDate, $timeZone);
            } catch (RuntimeException) {
                break;
            }

            $allowCurrentDate = false;
            $currentTime      = clone $result;
            $matches[]        = $result;
        }

        return $matches;
    }

    /**
     * Check if every part of the expression satisfies the date.
     *
     * @param DateTimeInterface $date
     * @return bool
     */
    private function matches(DateTimeInterface $date): bool
    {
        if ($this->lists === null) {
            $this->compile();
        }

        foreach ($this->lists as $position => $items) {
            if ($this->eitherDay && ($position === self::DAY || $position === self::WEEKDAY)) {
                continue;
            }

            if (! self::satisfies(self::$fields[$position], $date, $items, false)) {
                return false;
            }
        }

        return ! $this->eitherDay
            || self::satisfies(self::$fields[self::DAY], $date, $this->lists[self::DAY], false)
            || self::satisfies(self::$fields[self::WEEKDAY], $date, $this->lists[self::WEEKDAY], false);
    }

    /**
     * Split the parts other than `*` into their items, in the order they are satisfied.
     *
     * @return void
     */
    private function compile(): void
    {
        $this->lists = [];
        foreach (self::ORDER as $position) {
            $part = $this->parts[$position];
            if ($part !== '*') {
                $this->lists[$position] = str_contains($part, ',') ? array_map('trim', explode(',', $part)) : [$part];
            }
        }

        $day     = $this->parts[self::DAY];
        $weekday = $this->parts[self::WEEKDAY];

        $this->eitherDay = $day !== '*' && $day !== '?' && $weekday !== '*' && $weekday !== '?';
    }

    /**
     * Check if any item of a part satisfies the date.
     *
     * @param AbstractField     $field
     * @param DateTimeInterface $date
     * @param string[]          $items
     * @param bool              $invert
     * @return bool
     */
    private static function satisfies(AbstractField $field, DateTimeInterface $date, array $items, bool $invert): bool
    {
        foreach ($items as $item) {
            if ($field->isSatisfiedBy($date, $item, $invert)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the next or previous run date relative to a date.
     *
     * @param DateTimeInterface|string|null $currentTime
     * @param int                           $nth
     * @param bool                          $invert
     * @param bool                          $allowCurrentDate
     * @param string|null                   $timeZone
     * @return DateTime
     * @throws RuntimeException On too many iterations.
     */
    private function getRunDate(
        DateTimeInterface|string|null $currentTime,
        int $nth,
        bool $invert,
        bool $allowCurrentDate,
        ?string $timeZone
    ): DateTime
    {
        $timeZone = $this->determineTimeZone($currentTime, $timeZone);
        $zone     = new DateTimeZone($timeZone);
        $current  = $this->toDateTime($currentTime ?? 'now', $timeZone);

        // drops the seconds without setTime(), which may change the offset: https://bugs.php.net/bug.php?id=81074
        $current = DateTime::createFromFormat('!Y-m-d H:iO', $current->format('Y-m-d H:iP'), $zone);
        if ($current === false) {
            throw new RuntimeException('Unable to create date from format');
        }
        $current->setTimezone($zone);

        if ($this->lists === null) {
            $this->compile();
        }

        // either day satisfies the expression, so the nearest date of the two searches wins
        if ($this->eitherDay) {
            [$minute, $hour, $day, $month, $weekday] = $this->parts;

            $this->monthDays ??= new self("$minute $hour $day $month *");
            $this->weekDays  ??= new self("$minute $hour * $month $weekday");

            $combined = [
                ...$this->monthDays->getMultipleRunDates($nth + 1, $currentTime ?? 'now', $invert, $allowCurrentDate, $timeZone),
                ...$this->weekDays->getMultipleRunDates($nth + 1, $currentTime ?? 'now', $invert, $allowCurrentDate, $timeZone),
            ];

            usort($combined, $invert ? fn ($a, $b) => $b <=> $a : fn ($a, $b) => $a <=> $b);

            return $combined[$nth] ?? throw new RuntimeException('Impossible CRON expression');
        }

        $next   = clone $current;
        $minute = $this->parts[self::MINUTE] === '*' ? null : $this->parts[self::MINUTE];

        for ($i = 0; $i < $this->maxIterationCount; ++$i) {
            foreach ($this->lists as $position => $items) {
                if (! self::satisfies(self::$fields[$position], $next, $items, $invert)) {
                    self::$fields[$position]->increment($next, $invert, $this->parts[$position]);

                    continue 2;
                }
            }

            if ((! $allowCurrentDate && $next == $current) || --$nth > -1) {
                self::$fields[self::MINUTE]->increment($next, $invert, $minute);

                continue;
            }

            return $next;
        }

        throw new RuntimeException('Impossible CRON expression');
    }

    /**
     * Convert a date to a DateTime in a timezone, never modifying the given object.
     *
     * @param DateTimeInterface|string $time
     * @param string                   $timeZone
     * @return DateTime
     */
    private function toDateTime(DateTimeInterface|string $time, string $timeZone): DateTime
    {
        $date = match (true) {
            $time instanceof DateTime          => clone $time,
            $time instanceof DateTimeInterface => DateTime::createFromInterface($time),
            default                            => new DateTime($time),
        };

        return $date->setTimezone(new DateTimeZone($timeZone));
    }

    /**
     * Get the timezone to calculate in: the given one, the date one, or the default one.
     *
     * @param DateTimeInterface|string|null $currentTime
     * @param string|null                   $timeZone
     * @return string
     */
    private function determineTimeZone(DateTimeInterface|string|null $currentTime, ?string $timeZone): string
    {
        if ($timeZone !== null) {
            return $timeZone;
        }

        return $currentTime instanceof DateTimeInterface ? $currentTime->getTimezone()->getName() : date_default_timezone_get();
    }
}
