<?php

declare(strict_types=1);

namespace Expansa\Facades;

use Expansa\Database\Exception\InvalidArgumentException;
use Expansa\Database\Query\Builder;
use Expansa\Database\Query\Raw;
use Expansa\Patterns\Facade;
use PDOStatement;

/**
 * This class provides a facade for database interactions, offering a variety of methods to execute
 * common SQL operations such as querying, creating, dropping tables, and performing CRUD operations.
 * It also includes methods for more advanced operations like schema management and raw SQL execution.
 *
 * @method static PDOStatement query(string $statement, array $map = [])
 * @method static PDOStatement create(string $table, array $columns, ?array $options = null)
 * @method static PDOStatement drop(string $table)
 * @method static PDOStatement rename(string $table, string $to)
 * @method static PDOStatement insert(string $table, array $values, ?string $primaryKey = null)
 * @method static PDOStatement update(string $table, array $data, ?array $where = null)
 * @method static PDOStatement delete(string $table, Raw|array $where)
 * @method static PDOStatement replace(string $table, array $columns, ?array $where = null)
 * @method static mixed        get(string $table, $join = null, array|string|null $columns = null, ?array $where = null)
 * @method static bool         has(string $table, array $join, ?array $where = null)
 * @method static array        rand(string $table, ?array $join = null, array|string|null $columns = null, ?array $where = null)
 * @method static null|int     count(string $table, ?array $join = null, ?string $column = null, ?array $where = null)
 * @method static null|array   select(string $table, $join, array|string|null $columns = null, ?array $where = null)
 * @method static null|string  avg(string $table, array $join, ?string $column = null, ?array $where = null)
 * @method static null|string  max(string $table, array $join, ?string $column = null, ?array $where = null)
 * @method static null|string  min(string $table, array $join, ?string $column = null, ?array $where = null)
 * @method static null|string  sum(string $table, array $join, ?string $column = null, ?array $where = null)
 * @method static null|string  id(?string $name = null)
 * @method static Raw          raw(string $string, array $map = [])
 * @method static string       quote(string $string)
 * @method static string       version()
 * @method static array        schema(?string $col = null)
 * @method static array        updateSchema()
 * @method static array        log()
 * @method static bool         hasTable(string $tableName)
 */
class Db extends Facade
{
    /**
     * @return string
     */
    protected static function getStaticClassAccessor(): string
    {
        return '\Expansa\Database\Query\Builder';
    }

    /**
     * Connection options read from the application's own env config - the one legitimate place
     * this happens; everything downstream (Query\Builder and below) receives them as plain
     * constructor arguments instead of reading the constants itself.
     *
     * @return array{0: array<string, mixed>}
     */
    protected static function getConstructorArgs(): array
    {
        return [
            [
                'driver'    => EX_DB_DRIVER,
                'database'  => EX_DB_NAME,
                'username'  => EX_DB_USERNAME,
                'password'  => EX_DB_PASSWORD,
                'host'      => EX_DB_HOST,
                'prefix'    => EX_DB_PREFIX,
                'charset'   => EX_DB_CHARSET,
                'collation' => EX_DB_COLLATION,
                'testMode'  => EX_DB_LOGGING,
                'port'      => EX_DB_PORT,
                'error'     => EX_DB_ERROR_MODE,
            ],
        ];
    }

    /**
     * Create new database connection.
     *
     * @param array $options
     * @return null|Builder
     */
    public static function connection(array $options): ?Builder
    {
        try {
            return new Builder($options);
        } catch (InvalidArgumentException $e) {
            return null;
        }
    }

    /**
     * The connection Builder this facade itself resolves to - connected on first access, using
     * the exact same application-configured credentials as any other Db:: call. Lets other
     * framework components (e.g. {@see \Expansa\Database\Schema}) reuse this one connection
     * instead of separately reading the application's config and opening a second connection.
     *
     * @return Builder
     */
    public static function instance(): Builder
    {
        return static::getResolvedClassInstance();
    }
}
