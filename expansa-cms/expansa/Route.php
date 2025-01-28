<?php

declare(strict_types=1);

namespace Expansa;

use Expansa\Patterns\Facade;

/**
 * Provides static access to the routing.
 *
 * @method static void before(string $methods, string $pattern, object|callable $callback)
 * @method static void match(string $methods, string $pattern, callable|object $callback)
 * @method static void any(string $pattern, callable|object $callback)
 * @method static void get(string $pattern, callable|object $callback)
 * @method static void post(string $pattern, callable|object $callback)
 * @method static void patch(string $pattern, callable|object $callback)
 * @method static void delete(string $pattern, callable|object $callback)
 * @method static void put(string $pattern, callable|object $callback)
 * @method static void options(string $pattern, callable|object $callback)
 * @method static void middleware(string $baseRoute, callable $callback)
 * @method static void run(object|callable $callback = null)
 * @method static void set404(object|callable|string $matchFn, object|callable $callback = null)
 * @method static void trigger404(mixed $match = null)
 */
class Route extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return '\Expansa\Routing\Router';
    }
}
