<?php

declare(strict_types=1);

namespace Expansa\Facades;

use Closure;
use Expansa\Log\Level;
use Expansa\Log\Logger;
use Expansa\Patterns\Facade;
use Stringable;

/**
 * Log facade: channels of Expansa\Log\Manager, the PSR-3 methods write to the default one.
 *
 * @method static void   configure(array $channels, string $default = '')
 * @method static \Expansa\Log\Manager extend(string $driver, Closure $factory)
 * @method static Logger channel(?string $name = null)
 * @method static Logger stack(array $channels, string $name = 'stack')
 * @method static \Expansa\Log\Manager shareContext(array $context)
 * @method static void   emergency(string|Stringable $message, array $context = [])
 * @method static void   alert(string|Stringable $message, array $context = [])
 * @method static void   critical(string|Stringable $message, array $context = [])
 * @method static void   error(string|Stringable $message, array $context = [])
 * @method static void   warning(string|Stringable $message, array $context = [])
 * @method static void   notice(string|Stringable $message, array $context = [])
 * @method static void   info(string|Stringable $message, array $context = [])
 * @method static void   debug(string|Stringable $message, array $context = [])
 * @method static void   log(Level|int|string $level, string|Stringable $message, array $context = [])
 */
class Log extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return \Expansa\Log\Manager::class;
    }
}
