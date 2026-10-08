<?php

declare(strict_types=1);

namespace Expansa\Facades;

use Closure;
use Expansa\Patterns\Facade;

/**
 * Debug panel of the request, see Expansa\Debug\Panel.
 *
 * @method static \Expansa\Debug\Panel add(string $title, Closure $rows)
 * @method static \Expansa\Debug\Panel forget(string $title)
 * @method static array                getSections()
 * @method static string               render()
 */
class Panel extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return \Expansa\Debug\Panel::class;
    }
}
