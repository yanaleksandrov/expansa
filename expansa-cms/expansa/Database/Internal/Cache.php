<?php

declare(strict_types=1);

namespace Expansa\Database\Internal;

use Closure;

/**
 * Cache of rows and fields, the callbacks come from Model::configure(); without them nothing is cached.
 *
 * @internal
 * @package Expansa\Database\Internal
 */
final class Cache
{
    private static ?Closure $get = null;

    private static ?Closure $forget = null;

    /**
     * Set the callbacks, null turns caching off.
     *
     * @param Closure|null $get    (string $key, string $group, ?Closure $callback): mixed
     * @param Closure|null $forget (string $key, string $group): mixed
     * @return void
     */
    public static function configure(?Closure $get, ?Closure $forget): void
    {
        self::$get    = $get;
        self::$forget = $forget;
    }

    /**
     * Get a cached value; on a miss the callback result is cached and returned.
     *
     * @param string       $key
     * @param string       $group
     * @param Closure|null $callback Without it a miss returns null.
     * @return mixed
     */
    public static function get(string $key, string $group, ?Closure $callback = null): mixed
    {
        if (self::$get === null) {
            return $callback === null ? null : $callback();
        }

        return (self::$get)($key, $group, $callback);
    }

    public static function forget(string $key, string $group): void
    {
        if (self::$forget !== null) {
            (self::$forget)($key, $group);
        }
    }
}
