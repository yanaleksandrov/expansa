<?php

declare(strict_types=1);

namespace Expansa\Support;

/**
 * Human-readable values: file sizes and numbers.
 *
 * @package Expansa\Support
 */
final class Humanize
{
    private const array SIZES = ['b', 'Kb', 'Mb', 'Gb', 'Tb', 'Pb'];

    /**
     * Convert bytes to a file size: `1536` → `1.5 Kb`, empty string for zero.
     *
     * @param int $bytes
     * @return string
     */
    public static function fromBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '';
        }

        $i = min((int) floor(log($bytes, 1024)), count(self::SIZES) - 1);

        return round($bytes / 1024 ** $i, 2) . ' ' . self::SIZES[$i];
    }

    /**
     * Convert a file size to bytes: `10`, `10b`, `10k`, `10Kb`, ` 10 KB ` (K, M, G, T).
     *
     * @param string $value
     * @return string The number of bytes, or the value unchanged if it is not a size.
     */
    public static function toBytes(string $value): string
    {
        return preg_replace_callback(
            '/^\s*(\d+)\s*(?:([kmgt]?)b?)?\s*$/i',
            fn (array $m): string => (string) ((int) $m[1] * 1024 ** (int) strpos(' kmgt', strtolower($m[2] ?? '') ?: ' ')),
            $value
        );
    }

    /**
     * Format a number with a comma every three digits: `1234567.891` → `1,234,567.89` with two decimals.
     *
     * @param int|float $number
     * @param int       $decimals
     * @return string
     */
    public static function number(int|float $number, int $decimals = 0): string
    {
        return number_format($number, $decimals, '.', ',');
    }
}
