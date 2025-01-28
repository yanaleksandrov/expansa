<?php

declare(strict_types=1);

namespace Expansa\Debug;

use LogicException;

class Metric
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
     * Returns the elapsed time, readable or not
     *
     * @param bool $raw
     * @return float|string
     */
    public function time(bool $raw = false): float|string
    {
        if (!isset($this->startTime)) {
            throw new LogicException("You must call start()");
        }
        $this->end();

        $elapsed = $this->endTime - $this->startTime;

        return $raw ? $elapsed : $this->readableElapsedTime($elapsed);
    }

    /**
     * Returns the memory peak, readable or not
     *
     * @param bool $raw
     * @param string|null $format The format to display (printf format)
     * @return string|float
     */
    public function memory(bool $raw = false, ?string $format = null): string|float
    {
        $memory = memory_get_peak_usage(false);

        return $raw ? $memory : $this->readableSize($memory, $format);
    }

    /**
     * Returns the percentage of memory used out of the total amount allocated.
     *
     * @return null|float
     */
    public function memoryPercent(): ?float
    {
        $this->end();

        $memoryLimit = (int) ini_get('memory_limit');
        if ($memoryLimit > 0) {
            return round($this->memoryUsage / ($memoryLimit * 1024 * 1024) * 100, 2);
        }
        return null;
    }

    /**
     * Returns the memory usage at the end checkpoint
     *
     * @param bool $raw
     * @param string|null $format The format to display (printf format)
     * @return string|float
     */
    public function memoryUsage(bool $raw = false, ?string $format = null): string|float
    {
        return $raw ? $this->memoryUsage : $this->readableSize($this->memoryUsage, $format);
    }

    /**
     * Sets end microtime
     *
     * @return void
     * @throws LogicException
     */
    private function end(): void
    {
        if (!isset($this->startTime)) {
            throw new LogicException("You must call start()");
        }

        $this->endTime     = microtime(true);
        $this->memoryUsage = memory_get_usage(true);
    }

    /**
     * Returns a human-readable memory size
     *
     * @param int $size
     * @param string|null $format The format to display (printf format)
     * @param int $round
     * @return string
     */
    private function readableSize(int $size, ?string $format = null, int $round = 3): string
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
    private function readableElapsedTime(float $microtime): string
    {
        $precision = 1;
        if ($microtime < 1) {
            $numberStr = str_replace('.', '', (string) $microtime);
            $precision = strspn($numberStr, '0');
        }

        return rtrim(number_format($microtime, $precision + 1, '.', ''), '0');
    }
}
