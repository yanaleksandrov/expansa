<?php

declare(strict_types=1);

namespace Expansa\Cache\Traits;

/**
 * Suspend locks shared by every provider: a locked key rejects add() while suspend() runs its callback.
 *
 * @package Expansa\Cache\Traits
 */
trait Locks
{
    /**
     * Locked keys by group.
     *
     * @var array<string, array<string, true>>
     */
    private static array $locks = [];

    public function suspend(callable $callback, string $key, string $group = 'default'): void
    {
        self::$locks[$group][$key] = true;

        try {
            $callback();
        } finally {
            unset(self::$locks[$group][$key]);
        }
    }

    private function isLocked(string $group, string $key): bool
    {
        return isset(self::$locks[$group][$key]);
    }
}
