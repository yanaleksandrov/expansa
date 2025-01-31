<?php

declare(strict_types=1);

namespace Expansa\Facades;

use Expansa\Patterns\Facade;

/**
 * Provides a facade for the Debug functionality in the Expansa framework.
 * It allows starting the debug process with a specified view path, callback,
 * and an optional flag to show errors.
 *
 * @method static void start(bool $isShowErrors, string $viewPath, callable $callback)
 */
class Debug extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return '\Expansa\Debug\Debug';
    }
}
