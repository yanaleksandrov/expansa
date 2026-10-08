<?php

declare(strict_types=1);

namespace Expansa\Debug;

/**
 * Time and memory of the request, the metrics() helper instance.
 * Readable values are compact for the dashboard bar: `12.4ms`, `1.25s`, `4.12MB`.
 *
 * @package Expansa\Debug
 */
final class Metric
{
    /**
     * Start of the measurement: the request start by default, so the time includes PHP startup and autoload.
     *
     * @var float
     */
    private float $start;

    public function __construct()
    {
        $this->start = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);
    }

    /**
     * Restart the timer from now, e.g. to measure a single operation.
     *
     * @return void
     */
    public function start(): void
    {
        $this->start = microtime(true);
    }

    /**
     * Time since the start: seconds or a readable string.
     *
     * @param bool $raw
     * @return float|string
     */
    public function time(bool $raw = false): float|string
    {
        $seconds = microtime(true) - $this->start;

        if ($raw) {
            return $seconds;
        }

        return $seconds < 1 ? round($seconds * 1000, 1) . 'ms' : round($seconds, 2) . 's';
    }

    /**
     * Peak memory used by the script: bytes or a readable string.
     *
     * @param bool $raw
     * @return int|string
     */
    public function memory(bool $raw = false): int|string
    {
        $bytes = memory_get_peak_usage();

        return $raw ? $bytes : $this->size($bytes);
    }

    /**
     * Memory allocated from the system now: bytes or a readable string.
     *
     * @param bool $raw
     * @return int|string
     */
    public function memoryUsage(bool $raw = false): int|string
    {
        $bytes = memory_get_usage(true);

        return $raw ? $bytes : $this->size($bytes);
    }

    /**
     * Peak allocated memory as a percentage of memory_limit; null without a limit.
     *
     * @return float|null
     */
    public function memoryPercent(): ?float
    {
        $limit = $this->limit();

        return $limit > 0 ? round(memory_get_peak_usage(true) / $limit * 100, 2) : null;
    }

    /**
     * Bytes of memory_limit, `128M` or `1G`; -1 when unlimited.
     *
     * @return int
     */
    private function limit(): int
    {
        $limit = trim((string) ini_get('memory_limit'));
        $value = (int) $limit;

        return match (strtolower(substr($limit, -1))) {
            'g'     => $value * 1024 ** 3,
            'm'     => $value * 1024 ** 2,
            'k'     => $value * 1024,
            default => $value,
        };
    }

    /**
     * Readable size: `512B`, `4.12MB`.
     *
     * @param int $bytes
     * @return string
     */
    private function size(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . 'B';
        }

        $units = ['KB', 'MB', 'GB', 'TB'];
        $last  = count($units) - 1;
        $size  = $bytes / 1024;
        $unit  = 0;

        while ($size >= 1024 && $unit < $last) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . $units[$unit];
    }
}
