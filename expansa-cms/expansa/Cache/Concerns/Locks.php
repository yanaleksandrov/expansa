<?php

declare(strict_types=1);

namespace Expansa\Cache\Concerns;

/**
 * Suspend-lock bookkeeping shared by every provider. A locked (group, key) pair rejects add()
 * calls for the duration of suspend()'s callback, regardless of which backend is storing the
 * actual value.
 */
trait Locks
{
    /**
     * @var array<string, array<string, true>>
     */
    private static array $locks = [];

    /**
     * Whether $key in $group is currently suspended and must reject add().
     */
    private function isLocked(string $group, string $key): bool
    {
        return isset(self::$locks[ $group ][ $key ]);
    }

    /**
     * Suspends the addition of data to the cache for the duration of $callback. The lock is
     * released even if $callback throws, so a failed populate never leaves the key stuck locked.
     */
    public function suspend(callable $callback, string $key, string $group = 'default'): void
    {
        self::$locks[ $group ][ $key ] = true;

        try {
            $callback();
        } finally {
            unset(self::$locks[ $group ][ $key ]);
        }
    }
}
