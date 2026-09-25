<?php

declare(strict_types=1);

namespace Expansa\Facades;

use Expansa\Patterns\Facade;

/**
 * Class Extensions provides a facade for managing including extensions: plugins & themes.
 *
 * @method static void  configure(string $root)
 * @method static array get(string $type)
 * @method static void  load(array $ids)
 * @method static void  register(string $type)
 * @method static void  boot(string $type)
 * @method static void  activate(string $type)
 * @method static void  deactivate(string $type)
 * @method static void  install(string $type)
 * @method static void  uninstall(string $type)
 */
class Extensions extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return \Expansa\Extensions\Manager::class;
    }
}
