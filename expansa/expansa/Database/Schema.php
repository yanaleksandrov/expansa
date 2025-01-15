<?php

declare(strict_types=1);

namespace Expansa\Database;

use Closure;
use Expansa\Facades\Facade;

/**
 * Schema facade for database operations.
 *
 * @method static void create(string $table, Closure $callback)
 * @method static void drop(string $table)
 * @method static void rename(string $from, string $to)
 */
class Schema extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return '\Expansa\Database\Schema\Builder';
    }

    protected static function getConstructorArgs(): array
    {
        return [
            Db::connection([
                'driver'   => EX_DB_DRIVER,
                'database' => EX_DB_NAME,
                'username' => EX_DB_USERNAME,
                'password' => EX_DB_PASSWORD,
                'host'     => EX_DB_HOST,
                'prefix'   => EX_DB_PREFIX,
                'charset'  => EX_DB_CHARSET,
                'testMode' => EX_DB_LOGGING,
                'port'     => EX_DB_PORT,
                'error'    => EX_DB_ERROR_MODE,
            ])
        ];
    }
}
