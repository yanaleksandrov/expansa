<?php

declare(strict_types=1);

namespace Expansa;

use Expansa\Patterns\Facade;

/**
 * Asset facade class providing static access to asset management methods.
 *
 * This class allows easy access to various asset management functionalities,
 * such as enqueueing, overriding, dequeuing, and rendering assets html markup.
 *
 * @method static void  enqueue(string $uid, string $src, ...$data)
 * @method static void  override(string $id, string $src, array $data = [], string $version = EX_VERSION)
 * @method static void  dequeue(string $id)
 * @method static void  render(string $pattern = '')
 * @method static array get(string $id = '')
 */
class Asset extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return '\Expansa\Assets\Manager';
    }
}
