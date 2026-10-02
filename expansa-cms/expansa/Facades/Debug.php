<?php

declare(strict_types=1);

namespace Expansa\Facades;

use Closure;
use Expansa\Patterns\Facade;
use Throwable;

/**
 * Uncaught errors, see Expansa\Debug\Manager; the debug panel is the Panel facade.
 *
 * @method static void   configure(string $view = '', bool $details = false, ?Closure $report = null, ?Closure $warning = null, ?Closure $context = null, ?Closure $json = null, string $editor = '', array $collapse = [])
 * @method static void   register()
 * @method static void   handle(Throwable $e, array $context = [])
 * @method static void   send(Throwable $e, string $id = '')
 * @method static string report(Throwable $e, array $context = [])
 * @method static array  getContext()
 * @method static bool   isFatal(Throwable $e)
 */
class Debug extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return \Expansa\Debug\Manager::class;
    }
}
