<?php

declare(strict_types=1);

namespace Expansa\Facades;

use Expansa\Lifecycle\Manager;
use Expansa\Patterns\Facade;

/**
 * Application lifecycle facade: phases, contexts, routing and the load timeline.
 *
 * @method static Manager     phase(string $name, bool|callable $when, callable $callback)
 * @method static Manager     context(string $name, bool|callable $when, callable $callback)
 * @method static void        run(?string $uri = null, ?callable $catch = null)
 * @method static string|null current()
 * @method static bool        is(string $context)
 * @method static array       timeline()
 */
class Lifecycle extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return \Expansa\Lifecycle\Manager::class;
    }
}
