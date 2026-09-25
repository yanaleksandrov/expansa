<?php

declare(strict_types=1);

namespace Expansa\Facades;

use Expansa\Patterns\Facade;
use Throwable;

/**
 * Debug page facade.
 *
 * @method static void render(Throwable $e, string $viewPath)
 */
class Debug extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return \Expansa\Debug\Debug::class;
    }
}
