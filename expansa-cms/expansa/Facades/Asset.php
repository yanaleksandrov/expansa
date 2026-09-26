<?php

declare(strict_types=1);

namespace Expansa\Facades;

use Closure;
use Expansa\Patterns\Facade;

/**
 * Asset facade class providing static access to asset management methods.
 * This class allows easy access to various asset management functionalities,
 * such as enqueueing, overriding, dequeuing, and rendering assets html markup.
 *
 * @method static void  enqueue(string $uid, string $src, string $provider, ...$data)
 * @method static void  style(string $uid, string $src, array ...$data)
 * @method static void  script(string $uid, string $src, array ...$data)
 * @method static void  override(string $uid, string $src, ?string $provider = null, ...$data)
 * @method static void  dequeue(string $uid, ?string $provider = null)
 * @method static void  discover(string $file, ?string $uid = null, array $context = [])
 * @method static void  provider(string $extension, string $providerClass)
 * @method static void  configure(Closure $resolver)
 * @method static void  render(array $filter = [], bool $combine = false, bool $minify = false, bool $inline = false)
 * @method static array get(string $uid = '')
 * @method static int   clean(int $maxAge = 604800, ?int $maxBytes = null)
 */
class Asset extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return \Expansa\Assets\Manager::class;
    }
}
