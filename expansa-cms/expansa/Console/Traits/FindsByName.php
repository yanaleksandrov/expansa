<?php

declare(strict_types=1);

namespace Expansa\Console\Traits;

/**
 * Lookup of an enum case by its markup name, case-insensitive: `slow_blink` finds SlowBlink.
 *
 * @package Expansa\Console
 */
trait FindsByName
{
    /**
     * Get the case by its name, `null` for an unknown one.
     *
     * @param string $name
     * @return static|null
     */
    public static function fromName(string $name): ?static
    {
        $case = self::class . '::' . str_replace('_', '', ucwords(strtolower(trim($name)), '_'));

        return defined($case) ? constant($case) : null;
    }
}
