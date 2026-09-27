<?php

declare(strict_types=1);

namespace Expansa\Facades;

use Expansa\Patterns\Facade;

/**
 * Routes of Expansa\Routing\Router.
 *
 * @method static void   before(string $methods, string $pattern, callable|array $fn)
 * @method static void   match(string $methods, string $pattern, callable|array $fn)
 * @method static void   any(string $pattern, callable|array $fn)
 * @method static void   get(string $pattern, callable|array $fn)
 * @method static void   post(string $pattern, callable|array $fn)
 * @method static void   patch(string $pattern, callable|array $fn)
 * @method static void   delete(string $pattern, callable|array $fn)
 * @method static void   put(string $pattern, callable|array $fn)
 * @method static void   options(string $pattern, callable|array $fn)
 * @method static void   register(string $controller, callable $dispatch, ?string $name = null)
 * @method static void   prefix(string $baseRoute, callable $fn)
 * @method static bool   run(callable|object|null $callback = null)
 * @method static void   set404(callable|object|string $matchFn, callable|array|null $fn = null)
 * @method static void   trigger404(mixed $match = null)
 * @method static string uri()
 * @method static string getBasePath()
 * @method static array  getRequestHeaders()
 * @method static string getRequestMethod()
 * @method static void   setBasePath(string $serverBasePath)
 */
class Route extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return \Expansa\Routing\Router::class;
    }
}
