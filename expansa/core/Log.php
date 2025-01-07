<?php

declare(strict_types=1);

namespace Expansa;

use Stringable;
use Expansa\Facades\Facade;

/**
 * Class Log provides a facade for managing log in the Expansa framework.
 *
 * @method static void emergency(string|Stringable $message, array $context = [])
 * @method static void alert(string|Stringable $message, array $context = [])
 * @method static void critical(string|Stringable $message, array $context = [])
 * @method static void error(string|Stringable $message, array $context = [])
 * @method static void warning(string|Stringable $message, array $context = [])
 * @method static void notice(string|Stringable $message, array $context = [])
 * @method static void info(string|Stringable $message, array $context = [])
 * @method static void debug(string|Stringable $message, array $context = [])
 */
class Log extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return '\Expansa\Log\Logger';
    }
}
