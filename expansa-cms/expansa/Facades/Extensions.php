<?php

declare(strict_types=1);

namespace Expansa\Facades;

use Expansa\Patterns\Facade;

/**
 * Class Extensions provides a facade for managing including extensions: plugins & themes.
 *
 * @method static void        configure(string $root, string $quarantine = '', ?\Closure $failed = null)
 * @method static array       get(string $type)
 * @method static array       getInstalled(string $type)
 * @method static array       getQuarantined()
 * @method static bool        forget(string $id)
 * @method static string|null quarantine(\Throwable $error)
 * @method static void        load(array $ids)
 * @method static void        register(string $type)
 * @method static void        boot(string $type)
 * @method static bool        activate(string $id)
 * @method static bool        deactivate(string $id)
 * @method static bool        install(string $id)
 * @method static bool        uninstall(string $id)
 */
class Extensions extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return \Expansa\Extensions\Manager::class;
    }
}
