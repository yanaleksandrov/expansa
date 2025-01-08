<?php

declare(strict_types=1);

namespace Expansa\Database;

use Closure;
use Expansa\Facades\Facade;
use Expansa\Database\Contracts\DatabaseException;

/**
 * Schema facade for database operations.
 *
 * @method static bool   createDatabase(string $name)
 * @method static bool   dropDatabase(string $name)
 * @method static bool   dropDatabaseIfExists(string $name)
 * @method static array  getTables()
 * @method static bool   hasTable(string $table)
 * @method static void   create(string $table, Closure $callback)
 * @method static void   table(string $table, Closure $callback)
 * @method static void   rename(string $from, string $to)
 * @method static void   drop(string $table)
 * @method static void   dropIfExists(string $table)
 * @method static void   dropAllTables()
 * @method static array  getColumns(string $table)
 * @method static string getColumnType(string $table)
 * @method static bool   hasColumn(string $table, string $column):
 * @method static bool   hasColumns(string $table, array $columns)
 * @method static void   renameColumn(string $table, string $from, string $to)
 * @method static void   dropColumn(string $table, string|array $columns)
 * @method static void   dropColumns(string $table, array $columns)
 */
class Schema extends Facade
{
    /**
     * Get the class name of the static accessor.
     *
     * @return string
     * @throws DatabaseException If the driver is not supported.
     */
    protected static function getStaticClassAccessor(): string
    {
        $drivers = [
            'mysql'  => \Expansa\Database\Drivers\MySQL\SchemaBuilder::class,
            'pgsql'  => \Expansa\Database\Drivers\Postgres\SchemaBuilder::class,
            'sqlite' => \Expansa\Database\Drivers\SQLite\SchemaBuilder::class,
        ];

        $driver = EX_DB_DRIVER;
        if (!isset($drivers[$driver])) {
            throw new DatabaseException("Driver [$driver] is not supported");
        }

        return "\\$drivers[$driver]";
    }

    protected static function getConstructorArgs(): array
    {
        $driver      = EX_DB_DRIVER;
        $connections = [
            'mysql'  => \Expansa\Database\Drivers\MySQL\Connection::class,
            'pgsql'  => \Expansa\Database\Drivers\Postgres\Connection::class,
            'sqlite' => \Expansa\Database\Drivers\SQLite\Connection::class,
        ];
        $connectors = [
            'mysql'  => \Expansa\Database\Drivers\MySQL\Connector::class,
            'pgsql'  => \Expansa\Database\Drivers\Postgres\Connector::class,
            'sqlite' => \Expansa\Database\Drivers\SQLite\Connector::class,
        ];

        $config    = [
            'database' => EX_DB_NAME,
            'username' => EX_DB_USERNAME,
            'password' => EX_DB_PASSWORD,
            'host'     => EX_DB_HOST,
            'prefix'   => EX_DB_PREFIX,
            'driver'   => EX_DB_DRIVER,
            'charset'  => EX_DB_CHARSET,
        ];

        return [
            new $connections[$driver](
                (new $connectors[$driver]())->connect($config),
                $config
            )
        ];
    }
}
