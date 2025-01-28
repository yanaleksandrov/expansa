<?php

declare(strict_types=1);

namespace Expansa;

use Expansa\Hooks\Priority;
use Expansa\Patterns\Facade;

/**
 * Class Hook provides a facade for managing hooks in the Expansa framework.
 *
 * @method static array configure(string $path)
 * @method static void  add(string $name, string|array|callable $function, int $priority = Priority::BASE)
 * @method static bool  has(string $name)
 * @method static array get(?string $name = null)
 * @method static bool  flush(string $name, null|string|array $function = null)
 * @method static mixed call(string $name, mixed $value = null, mixed ...$values)
 */
class Hook extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return '\Expansa\Hooks\Manager';
    }
}
