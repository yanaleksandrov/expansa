<?php

declare(strict_types=1);

namespace Expansa\Pulse;

use LogicException;

class Bench
{
    protected float $startTime;

    protected float $endTime;

    protected int $memoryUsage;

    /**
     * Sets start microtime
     *
     * @return void
     */
    public function start(): void
    {
        $this->startTime = microtime(true);
    }

    /**
     * Sets end microtime
     *
     * @return $this
     * @throws LogicException
     */
    public function end(): self
    {
        if (!$this->hasStarted()) {
            throw new LogicException("You must call start()");
        }

        $this->endTime     = microtime(true);
        $this->memoryUsage = memory_get_usage(true);
        return $this;
    }

    /**
     * Returns the elapsed time, readable or not
     *
     * @param bool $raw
     * @return float|string
     */
    public function getTime(bool $raw = false): float|string
    {
        if (!$this->hasStarted()) {
            throw new LogicException("You must call start()");
        }

        if (!$this->hasEnded()) {
            throw new LogicException("You must call end()");
        }
        $this->end();

        $elapsed = $this->endTime - $this->startTime;

        return $raw ? $elapsed : $this->readableElapsedTime($elapsed);
    }

    /**
     * Returns the memory usage at the end checkpoint
     *
     * @param bool $raw
     * @param string|null $format The format to display (printf format)
     * @return string|float
     */
    public function getMemoryUsage(bool $raw = false, ?string $format = null): string|float
    {
        return $raw ? $this->memoryUsage : $this->readableSize($this->memoryUsage, $format);
    }

    /**
     * Returns the memory peak, readable or not
     *
     * @param bool $raw
     * @param string|null $format The format to display (printf format)
     * @return string|float
     */
    public function getMemoryPeak(bool $raw = false, ?string $format = null): string|float
    {
        $memory = memory_get_peak_usage(false);

        return $raw ? $memory : $this->readableSize($memory, $format);
    }

    /**
     * Returns the percentage of memory used out of the total amount allocated.
     *
     * @return float
     */
    public function getMemoryUsagePercent(): float
    {
        return round($this->memoryUsage / ((int) ini_get('memory_limit') * 1024 * 1024) * 100, 2);
    }

    /**
     * Wraps a callable with start() and end() calls
     *
     * Additional arguments passed to this method will be passed to
     * the callable.
     *
     * @param callable $callable
     * @param mixed ...$args
     * @return mixed
     */
    public function run(callable $callable, ...$args): mixed
    {
        $this->start();
        $result = $callable(...$args);
        $this->end();

        return $result;
    }

    /**
     * Returns a human-readable memory size
     *
     * @param int $size
     * @param string|null $format The format to display (printf format)
     * @param int $round
     * @return string
     */
    public function readableSize(int $size, ?string $format = null, int $round = 3): string
    {
        $mod = 1024;

        if (is_null($format)) {
            $format = '%.2f%s';
        }

        $units = explode(' ', 'B Kb Mb Gb Tb');

        for ($i = 0; $size > $mod; $i++) {
            $size /= $mod;
        }

        if ($i === 0) {
            $format = preg_replace('/(%.[\d]+f)/', '%d', $format);
        }

        return sprintf($format, round($size, $round), $units[$i]);
    }

    /**
     * Returns a human-readable elapsed time
     *
     * @param float $microtime
     * @return string
     */
    public function readableElapsedTime(float $microtime): string
    {
        $precision = 1;
        if ($microtime < 1) {
            $numberStr = str_replace('.', '', (string) $microtime);
            $precision = strspn($numberStr, '0');
        }

        return rtrim(number_format($microtime, $precision, '.', ''), '0');
    }

    public function hasEnded(): bool
    {
        return isset($this->endTime);
    }

    public function hasStarted(): bool
    {
        return isset($this->startTime);
    }
}
