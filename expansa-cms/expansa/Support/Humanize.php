<?php

declare(strict_types=1);

namespace Expansa\Support;

/**
 * Human-readable values.
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
}
