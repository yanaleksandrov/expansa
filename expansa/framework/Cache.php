<?php

declare(strict_types=1);

namespace Expansa;

use DateTime;
use Expansa\Facades\Facade;

/**
 * @method static mixed add( string $key, mixed $value, string $group = 'default', ?DateTime $expiry = null )
 * @method static mixed get( string $key, string $group = 'default', ?callable $callback = null )
 * @method static mixed pull( string $key, string $group = 'default' )
 * @method static void suspend( callable $callback, string $key, string $group = 'default' )
 * @method static bool forget( string $key = '', string $group = 'default' )
 * @method static bool increase( string $key, int|float $amount = 1, string $group = 'default' )
 * @method static bool decrease( string $key, int|float $amount = 1, string $group = 'default' )
 */
class Cache extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return 'Expansa\Cache\CacheManager';
    }
}
