<?php

declare(strict_types=1);

namespace Expansa\Facades;

use Expansa\Patterns\Facade;

/**
 * Application lifecycle facade: phases, contexts and the load timeline.
 *
 * @method static \Expansa\Lifecycle\Manager phase(string $name, callable $callback, ?callable $when = null)
 * @method static \Expansa\Lifecycle\Manager context(string $name, callable $when, callable $callback)
 * @method static \Expansa\Lifecycle\Manager fallback(callable $callback)
 * @method static \Expansa\Lifecycle\Manager catch(callable $handler)
 * @method static void        run(?string $uri = null)
 * @method static string|null current()
 * @method static bool        is(string $context)
 * @method static array       timeline()
 */
class Lifecycle extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return '\Expansa\Lifecycle\Manager';
    }
}
