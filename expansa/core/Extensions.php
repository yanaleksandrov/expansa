<?php

declare(strict_types=1);

namespace Expansa;

use Expansa\Facades\Facade;

/**
 * Class Extensions provides a facade for managing including extensions: plugins & themes.
 *
 * @method static void enqueue(callable $callback)
 * @method static void boot(string $type)
 * @method static void activate(string $type)
 * @method static void deactivate(string $type)
 * @method static void install(string $type)
 * @method static void uninstall(string $type)
 */
class Extensions extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return '\Expansa\Extensions\ExtensionsManager';
    }
}
