<?php

declare(strict_types=1);

namespace Expansa\Scheduler\Cron;

use DateTimeImmutable;
use DateTimeInterface;

/**
 * Hours field, allows: * , / -. Hours skipped or repeated by a DST change are still matched.
 *
 * @package Expansa\Scheduler\Cron
 */
class HoursField extends AbstractField
{
    /**
     * Seconds around a date for which the timezone transitions are loaded.
     */
    private const int TRANSITIONS_WINDOW = 31_622_400;

    protected int $rangeStart = 0;

    protected int $rangeEnd = 23;

    /**
     * Transitions of $transitionsZone, from DateTimeZone::getTransitions().
     */
    private array $transitions = [];

    private string $transitionsZone = '';

    private int $transitionsStart = 0;

    private int $transitionsEnd = 0;

    public function isSatisfiedBy(DateTimeInterface $date, string $value, bool $invert): bool
    {
        $hour = (int) $date->format('H');
        if ($this->isSatisfied($hour, $value)) {
            return true;
        }

        $timestamp  = $date->getTimestamp();
        $transition = $this->getPastTransition($date);
        if ($transition === null || $transition['ts'] <= $timestamp - 3600) {
            return false;
        }

        // the hour right after a DST change also satisfies the hour it replaced
        $zone   = $date->getTimezone();
        $change = $zone->getOffset(new DateTimeImmutable('@' . ($timestamp + 3600)))
            - $zone->getOffset(new DateTimeImmutable('@' . ($timestamp - 3600)));

        if ($change >= 3600) {
            return $this->isSatisfied($hour - 1, $value);
        }

        if (! $invert && $change <= -3600) {
            return $this->isSatisfied($hour + 1, $value);
        }

        return false;
    }

    public function increment(DateTimeInterface &$date, bool $invert = false, ?string $parts = null): void
    {
        $originalTimestamp = $date->getTimestamp();

        if ($parts === null || $parts === '*') {
            $date = $this->setTimeHour($this->shift($date, $invert ? -3600 : 3600), $invert, $originalTimestamp);

            return;
        }

        $hour   = (int) $date->format('H');
        $target = $this->target($this->values($parts), $hour, $invert);
        $day    = (int) $date->format('d');
        $offset = $date->getOffset();

        if (! $invert) {
            if ($hour >= $target) {
                $date = $this->shift($date, (24 - $hour) * 3600);

                // a DST change on the way leaves the date off midnight of the next day
                if ((int) $date->format('d') !== $day + 1 && (int) $date->format('H') !== 0) {
                    $date = $this->shift($date, $offset - $date->getOffset());
                }

                $hour = (int) $date->format('H');
            }

            $date = $this->shift($date, ($target - $hour) * 3600);
        } else {
            if ($hour <= $target) {
                $date = $this->shift($date, -($hour + 1) * 3600);

                if ((int) $date->format('d') !== $day - 1 && (int) $date->format('H') !== 23) {
                    $date = $this->shift($date, $offset - $date->getOffset());
                }

                $hour = (int) $date->format('H');
            }

            $date = $this->shift($date, -($hour - $target) * 3600);
        }

        $date = $this->setTimeHour($date, $invert, $originalTimestamp);

        $hour = (int) $date->format('H');
        if ($invert && ($hour === $target - 1 || ($hour === 23 && $target === 0))) {
            $date = $this->shift($date, 3600);
        }
    }

    /**
     * Get the latest timezone transition before the date, cached for a year around it.
     *
     * @param DateTimeInterface $date
     * @return array|null
     */
    private function getPastTransition(DateTimeInterface $date): ?array
    {
        $timestamp = $date->getTimestamp();
        $zone      = $date->getTimezone();

        if (
            $this->transitionsZone !== $zone->getName()
            || $timestamp - 86400 < $this->transitionsStart
            || $timestamp + 86400 > $this->transitionsEnd
        ) {
            // the first entry is the state at the window start, so the window starts well before the date
            $this->transitionsZone  = $zone->getName();
            $this->transitionsStart = $timestamp - self::TRANSITIONS_WINDOW;
            $this->transitionsEnd   = $timestamp + self::TRANSITIONS_WINDOW;
            $this->transitions      = $zone->getTransitions($this->transitionsStart, $this->transitionsEnd) ?: [];
        }

        $past = null;
        foreach ($this->transitions as $transition) {
            if ($transition['ts'] > $timestamp) {
                break;
            }
            $past = $transition;
        }

        return $past;
    }
}
